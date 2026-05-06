<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;

final class DraftDiscardDIContainer implements DIContainer
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

        $stmt = $pdo->prepare(
            "UPDATE item_draft
             SET status = 'discarded', updated_at = NOW()
             WHERE id = :id
               AND status NOT IN ('confirmed', 'discarded')"
        );
        $stmt->execute(['id' => $draftId]);

        if ($stmt->rowCount() === 0) {
            $_SESSION['flash_error'] = 'Draft could not be discarded (already confirmed or discarded).';
        } else {
            $_SESSION['flash_success'] = 'Draft discarded.';
        }

        \saso\util\Redirect::redirect('item/drafts/');
        exit;
    }
}
