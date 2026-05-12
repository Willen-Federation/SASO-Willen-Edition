<?php
namespace saso\repository\item;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAllForExport implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT
                i.concatId,
                i.itemName,
                i.categoryId,
                i.price,
                GROUP_CONCAT(DISTINCT c.colorName ORDER BY c.colorCode SEPARATOR \',\') AS colors,
                GROUP_CONCAT(DISTINCT s.sizeName ORDER BY s.orderNumber SEPARATOR \',\') AS sizes,
                i.pla,
                i.plaNote,
                i.paper,
                i.paperNote,
                i.createAt
            FROM Item i
            LEFT JOIN Color c ON c.concatId = i.concatId
            LEFT JOIN Size s ON s.concatId = i.concatId
            WHERE i.archive = :archive
            GROUP BY
                i.concatId, i.itemName, i.categoryId, i.price,
                i.pla, i.plaNote, i.paper, i.paperNote, i.createAt
            ORDER BY i.createAt DESC
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':archive', $input['archive'], \PDO::PARAM_INT);
    }

    public function map(): \Closure
    {
        return Each::tf(fn($v) => $v);
    }
}
