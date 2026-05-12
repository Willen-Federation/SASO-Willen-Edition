<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\Finder;
use saso\repository\Updater;
use saso\repository\TransactionInterface;
use saso\repository\item;
use saso\repository\itemVar;
use saso\repository\color;
use saso\repository\size;

final class BulkImportUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        $results      = [];
        $successCount = 0;
        $errorCount   = 0;

        foreach ($data->rows as $index => $row) {
            $rowNum   = $index + 2;
            $itemName = $row['商品名'] ?? '';

            try {
                $name = entity\Item::nameConstraint($itemName);
                if ($name->isLeft()) {
                    throw new \Exception('商品名が無効です（50文字以内・必須）');
                }

                $colorNames = array_values(array_filter(
                    array_map('trim', explode(',', $row['色'] ?? ''))
                ));
                $sizeNames = array_values(array_filter(
                    array_map('trim', explode(',', $row['サイズ'] ?? ''))
                ));

                if (empty($colorNames)) {
                    throw new \Exception('色は必須です');
                }
                if (empty($sizeNames)) {
                    throw new \Exception('サイズは必須です');
                }
                if (count($colorNames) * count($sizeNames) > 100) {
                    throw new \Exception('色数×サイズ数が100を超えています');
                }

                $pla       = ($row['プラ'] ?? '0') === '1';
                $plaNote   = entity\Item::caseNoteConstraint($row['プラ付記'] ?? '')->getOrElse('');
                $paper     = ($row['紙'] ?? '0') === '1';
                $paperNote = entity\Item::caseNoteConstraint($row['紙付記'] ?? '')->getOrElse('');

                $rawPrice = str_replace(',', '', $row['価格'] ?? '');
                $price    = ($rawPrice !== '' && preg_match('/^\d{1,9}$/', $rawPrice))
                    ? (int) $rawPrice
                    : null;

                $rawCategory = trim($row['分類ID'] ?? '');
                $categoryId  = ($rawCategory !== '' && ctype_digit($rawCategory))
                    ? (int) $rawCategory
                    : null;

                $this->transaction->begin();

                $lastSerial = $this->finder->current(
                    new item\FindLastSerialByDateCode(),
                    ['now' => entity\Item::makeDateCode($data->now)]
                )->getOrElse(0);

                $newItem = new entity\Item(
                    $lastSerial + 1,
                    $name->getOrElse(''),
                    $pla,
                    $pla ? $plaNote : '',
                    $paper,
                    $paper ? $paperNote : '',
                    $data->now,
                );

                $this->updater->exec(new item\Insert($newItem));

                $itemVar = new entity\ItemVar($newItem, $categoryId, $price, $data->now);
                $this->updater->exec(new itemVar\Insert($itemVar));

                foreach ($colorNames as $i => $colorNameRaw) {
                    $code      = entity\Feature::validateCode($i)->getOrElseThrow('色コードが無効です（最大100色）');
                    $validated = entity\Color::nameConstraint($colorNameRaw)->getOrElseThrow('色名が無効です: ' . $colorNameRaw);
                    $this->updater->exec(new color\Insert(new entity\Color($newItem, $code, $validated)));
                }

                foreach ($sizeNames as $i => $sizeNameRaw) {
                    $code      = entity\Feature::validateCode($i)->getOrElseThrow('サイズコードが無効です（最大100サイズ）');
                    $validated = entity\Size::nameConstraint($sizeNameRaw)->getOrElseThrow('サイズ名が無効です: ' . $sizeNameRaw);
                    $this->updater->exec(new size\Insert(new entity\Size($newItem, $code, $validated, $i)));
                }

                $this->transaction->commit();

                $results[] = [
                    'row'    => $rowNum,
                    'status' => 'success',
                    'itemId' => $newItem->id,
                    'name'   => $itemName,
                ];
                $successCount++;
            } catch (\Exception $e) {
                $this->transaction->rollBack();
                $results[] = [
                    'row'     => $rowNum,
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'name'    => $itemName,
                ];
                $errorCount++;
            }
        }

        $this->output = new BulkImportOutputData($results, $successCount, $errorCount);
    }
}
