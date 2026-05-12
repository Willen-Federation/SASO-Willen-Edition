<?php
namespace saso\item;

use saso\framework\View;

final class BulkTemplateView implements View
{
    public function __call(string $name, array $args): mixed
    {
        return null;
    }

    public function display(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="item_import_template.csv"');
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
