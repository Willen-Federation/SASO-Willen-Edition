<?php
namespace saso\item;

use saso\entity;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use saso\repository\item as itemRepo;
use saso\repository\color as colorRepo;
use saso\repository\size as sizeRepo;
final class DraftConfirmSaveDIContainer implements DIContainer
{
    private array $query = [];
    private array $post  = [];
    private \DateTime $now;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->query = $query;
        $this->post  = $post;
        $this->now   = $now;
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

        // Load draft
        $stmt = $pdo->prepare(
            'SELECT id, image_path, barcode_hint, user_data, ai_result, status
             FROM item_draft WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $draftId]);
        $draft = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($draft === false || $draft['status'] !== 'ready') {
            $_SESSION['flash_error'] = 'Draft is not available for confirmation.';
            \saso\util\Redirect::redirect('item/drafts/');
            exit;
        }

        // Extract confirmed values from POST (form), merging with AI result
        $aiResult = $draft['ai_result'] !== null ? (json_decode($draft['ai_result'], true) ?? []) : [];
        $post = $this->post;

        $itemName   = trim((string) ($post['item_name']    ?? $aiResult['item_name']    ?? ''));
        $priceRaw   = trim((string) ($post['price']        ?? $aiResult['price']        ?? ''));
        $colorRaw   = trim((string) ($post['colorName']    ?? $aiResult['color_name']   ?? 'standard'));
        $sizeRaw    = trim((string) ($post['sizeName']      ?? $aiResult['size_name']    ?? 'standard'));
        $categoryId = trim((string) ($post['categoryId']   ?? ''));
        $pla        = !empty($post['pla']);
        $plaNote    = trim((string) ($post['plaNote']      ?? ''));
        $paper      = !empty($post['paper']);
        $paperNote  = trim((string) ($post['paperNote']    ?? ''));

        if ($itemName === '') {
            $_SESSION['flash_error'] = 'Product name is required.';
            \saso\util\Redirect::redirect('item/draftConfirm/id/' . $draftId . '/');
            exit;
        }

        try {
            $finder      = new DbFinder();
            $updater     = new DbUpdater();
            $transaction = new DbTransaction();
            $transaction->begin();

            $now = $this->now;

            // Compute next serial
            $lastSerial = $finder->current(new itemRepo\FindLastSerialByDateCode(), [
                'now' => entity\Item::makeDateCode($now),
            ])->getOrElse(0);

            $serial = $lastSerial + 1;

            $priceInt = $priceRaw !== '' ? (int) str_replace(',', '', $priceRaw) : null;

            // Build item entity
            $itemEntity = new entity\Item(
                (string) $serial,
                $itemName,
                $pla,
                $plaNote ?: null,
                $paper,
                $paperNote ?: null,
                $now,
            );

            // Insert item row — DbUpdater calls map() internally
            $updater->exec(new itemRepo\Insert($itemEntity));

            // Update item_var columns (price, category) — uses UPDATE on same row
            $categoryIdVal = $categoryId !== '' ? (int) $categoryId : null;
            $itemVarEntity = new entity\ItemVar(
                $itemEntity,
                $categoryIdVal,
                $priceInt,
                $now,
            );
            $updater->exec(new \saso\repository\itemVar\Insert($itemVarEntity));

            // Insert color(s)
            $colors = array_values(array_filter(array_map(
                fn($c) => trim($c),
                explode(',', $colorRaw !== '' ? $colorRaw : 'standard'),
            )));
            foreach ($colors as $idx => $colorName) {
                $colorCode = entity\Feature::validateCode($idx)
                    ->getOrElse(sprintf('%02d', $idx));
                $colorEntity = new entity\Color($itemEntity, $colorCode, $colorName);
                $updater->exec(new colorRepo\Insert($colorEntity));
            }

            // Insert size(s)
            $sizes = array_values(array_filter(array_map(
                fn($s) => trim($s),
                explode(',', $sizeRaw !== '' ? $sizeRaw : 'standard'),
            )));
            foreach ($sizes as $idx => $sizeName) {
                $sizeCode = entity\Feature::validateCode($idx)
                    ->getOrElse(sprintf('%02d', $idx));
                $sizeEntity = new entity\Size($itemEntity, $sizeCode, $sizeName, $idx + 1);
                $updater->exec(new sizeRepo\Insert($sizeEntity));
            }

            // Mark draft as confirmed
            $stmtUpdate = $pdo->prepare(
                "UPDATE item_draft SET status = 'confirmed', updated_at = NOW() WHERE id = :id"
            );
            $stmtUpdate->execute(['id' => $draftId]);

            $transaction->commit();

            $itemId = $itemEntity->id;

        } catch (\Exception $e) {
            try { $transaction->rollBack(); } catch (\Throwable $_) {}
            $_SESSION['flash_error'] = 'Registration failed: ' . $e->getMessage();
            \saso\util\Redirect::redirect('item/draftConfirm/id/' . $draftId . '/');
            exit;
        }

        \saso\util\Redirect::redirect('item/start/item/' . $itemId . '/');
        exit;
    }
}
