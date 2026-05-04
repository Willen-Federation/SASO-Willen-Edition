<?php
namespace saso\repository\label;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindSheetLayoutById implements DbPrepare
{
    public function __construct(private int $id) {}

    public function getQuery(): string
    {
        return '
            SELECT id, code, vendor, product_name_ja, `columns`, `rows`,
                   label_width_mm, label_height_mm, is_verified
              FROM label_sheet_layout
             WHERE id = :id AND is_active = 1
             LIMIT 1
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $this->id, \PDO::PARAM_INT);
    }

    public function map(): \Closure
    {
        return Each::tf(fn($v) => $v);
    }
}
