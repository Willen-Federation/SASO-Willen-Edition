<?php
namespace saso\item;

use saso\framework\View;

final class BulkExportView implements View
{
    public function __construct(private \Generator $rows)
    {
    }

    public function __call(string $name, array $args): mixed
    {
        return null;
    }

    public function display(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="items_export.csv"');
        header('Cache-Control: no-cache, no-store');
        header('Pragma: no-cache');
    }

    public function onRoot(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getContent(): \Closure
    {
        $rows = $this->rows;
        return function () use ($rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, ['商品番号', '商品名', '分類ID', '価格', '色', 'サイズ', 'プラ', 'プラ付記', '紙', '紙付記', '登録日']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->concatId,
                    $row->itemName,
                    $row->categoryId ?? '',
                    $row->price ?? '',
                    $row->colors ?? '',
                    $row->sizes ?? '',
                    $row->pla ? '1' : '0',
                    $row->plaNote ?? '',
                    $row->paper ? '1' : '0',
                    $row->paperNote ?? '',
                    $row->createAt,
                ]);
            }
            fclose($out);
            exit;
        };
    }
}
