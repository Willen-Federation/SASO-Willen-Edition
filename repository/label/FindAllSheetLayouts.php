<?php
namespace saso\repository\label;

use saso\repository\DbPrepare;
use saso\util\Each;

/**
 * Fetches all active label_sheet_layout rows ordered by vendor + code.
 * Returns plain stdClass objects so the wizard template can access
 * ->code, ->vendor, ->product_name_ja, ->columns, ->rows, ->is_verified.
 */
final class FindAllSheetLayouts implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT id, code, vendor, product_name_ja, `columns`, `rows`,
                   label_width_mm, label_height_mm, is_verified
              FROM label_sheet_layout
             WHERE is_active = 1
             ORDER BY vendor, code
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void {}
    public function map(): \Closure
    {
        return Each::tf(fn($v) => $v);
    }
}
