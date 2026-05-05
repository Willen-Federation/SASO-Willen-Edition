<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Messaging\ProcessItemDraftDIContainer;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use Saso\Infrastructure\ItemDraft\PdoItemDraftRepository;
use Saso\Infrastructure\Messaging\MessageBusFactory;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class DraftRetryDIContainer implements DIContainer
{
    private array $query = [];
    private array $post  = [];

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->query = $query;
        $this->post  = $post;
    }

    public function flow(): View
    {
        if (empty($this->post)) {
            return new \saso\common\FailView();
        }

        $pdo = DBConnection::pdo();

        $draftId = (int) ($this->query['id'] ?? $this->post['id'] ?? 0);

        if ($draftId < 1) {
            $_SESSION['flash_error'] = 'Invalid draft ID.';
            \saso\util\Redirect::redirect('item/drafts/');
            exit;
        }

        // Re-queue the failed draft
        $stmt = $pdo->prepare(
            "UPDATE item_draft
             SET status = 'queued', error_detail = NULL, updated_at = NOW()
             WHERE id = :id AND status = 'failed'"
        );
        $stmt->execute(['id' => $draftId]);

        if ($stmt->rowCount() === 0) {
            $_SESSION['flash_error'] = 'Draft cannot be retried (not in failed state).';
            \saso\util\Redirect::redirect('item/drafts/');
            exit;
        }

        // Re-dispatch
        try {
            $appKeyRaw   = (string) (getenv('APP_KEY') ?: '');
            $appKeyBytes = $appKeyRaw !== '' ? base64_decode($appKeyRaw, true) : false;
            if ($appKeyBytes === false || strlen($appKeyBytes) !== 32) {
                throw new \RuntimeException('APP_KEY not configured or invalid; cannot dispatch draft processing.');
            }
            $draftRepository = new PdoItemDraftRepository($pdo);
            $settingService = new PdoSystemSettingService($pdo, new SecretEncryptor($appKeyBytes));
            $flagRepository = new PdoFeatureFlagRepository($pdo);
            $handler = ProcessItemDraftDIContainer::createHandler($draftRepository, $settingService, $flagRepository);

            $bus = MessageBusFactory::create([
                ProcessItemDraft::class => [$handler],
            ]);
            $bus->dispatch(new ProcessItemDraft($draftId));
        } catch (\Throwable $e) {
            error_log('[saso-draft] retry dispatch failed: ' . $e->getMessage());
        }

        $_SESSION['flash_success'] = 'Draft queued for retry.';
        \saso\util\Redirect::redirect('item/drafts/');
        exit;
    }
}
