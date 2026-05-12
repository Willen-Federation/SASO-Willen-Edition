<?php
namespace saso\item;

use saso\framework\View;

final class BulkTemplateView implements View
{
    public function display(): void
    {
        $rows = [
            ['商品名', '分類ID', '価格', '色（|区切り）', 'サイズ（|区切り）', 'プラ', 'プラ付記', '紙', '紙付記'],
            ['サンプル商品A', '', '1000', '赤|青|白', 'S|M|L', '', '', '', ''],
            ['サンプル商品B', '', '2500', '黒', 'フリーサイズ', '1', 'ポリ袋', '', ''],
        ];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="item_bulk_template.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
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
        return function () {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, ['商品名', '分類ID', '価格', '色', 'サイズ', 'プラ', 'プラ付記', '紙', '紙付記']);
            fputcsv($out, ['サンプル商品A', '', '1000', '赤,青,白', 'S,M,L', '0', '', '0', '']);
            fputcsv($out, ['サンプル商品B', '', '2500', 'ブラック', 'フリーサイズ', '1', 'ポリ袋', '1', '台紙あり']);
            fclose($out);
            exit;
        };
    }
}
