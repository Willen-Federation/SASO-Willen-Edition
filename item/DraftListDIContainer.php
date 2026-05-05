<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;

final class DraftListDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
    }

    public function flow(): View
    {
        $view = new DraftListView();
        $view->drafts = [];

        // Defensive read: if the migration for item_draft hasn't run on this
        // host (older schema, fresh deploy, etc.), the SELECT throws a PDO
        // exception and the page goes blank. Catch it, log, and render the
        // empty-state UI instead — the user sees "保留中のドラフトはありません。"
        // rather than a whiteout.
        try {
            $pdo = DBConnection::pdo();
            $stmt = $pdo->prepare(
                "SELECT id, image_path, barcode_hint, user_data, ai_result, status, created_at, updated_at
                 FROM item_draft
                 WHERE status IN ('queued', 'processing', 'ready', 'failed')
                 ORDER BY created_at DESC"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $view->drafts = array_map(static function (array $row): array {
                $row['user_data'] = $row['user_data'] !== null
                    ? json_decode($row['user_data'], true) ?? []
                    : [];
                $row['ai_result'] = $row['ai_result'] !== null
                    ? json_decode($row['ai_result'], true) ?? []
                    : [];
                return $row;
            }, $rows);
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-draftList] failed to load drafts: ' . $e->getMessage());
            }
        }

        return $view;
    }
}
