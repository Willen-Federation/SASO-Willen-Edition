<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;

final class DraftConfirmDIContainer implements DIContainer
{
    private array $query = [];

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->query = $query;
    }

    public function flow(): View
    {
        $pdo = DBConnection::pdo();

        $draftId = (int) ($this->query['id'] ?? 0);

        if ($draftId < 1) {
            $_SESSION['flash_error'] = 'Invalid draft ID.';
            \saso\util\Redirect::redirect('item/drafts/');
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id, image_path, barcode_hint, user_data, ai_result, status, created_at, updated_at
             FROM item_draft
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $draftId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            $_SESSION['flash_error'] = 'Draft not found.';
            \saso\util\Redirect::redirect('item/drafts/');
            exit;
        }

        if ($row['status'] !== 'ready') {
            $_SESSION['flash_error'] = 'This draft is not ready for confirmation yet (status: ' . $row['status'] . ').';
            \saso\util\Redirect::redirect('item/drafts/');
            exit;
        }

        $draft = $row;
        $draft['user_data'] = $draft['user_data'] !== null
            ? json_decode($draft['user_data'], true) ?? []
            : [];
        $draft['ai_result'] = $draft['ai_result'] !== null
            ? json_decode($draft['ai_result'], true) ?? []
            : [];

        $view = new DraftConfirmView();
        $view->draft = $draft;
        return $view;
    }
}
