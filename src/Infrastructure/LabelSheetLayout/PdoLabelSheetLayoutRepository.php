<?php

declare(strict_types=1);

namespace Saso\Infrastructure\LabelSheetLayout;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\LabelSheetLayout\LabelSheetLayout;
use Saso\Domain\LabelSheetLayout\Repository\LabelSheetLayoutRepository;

final class PdoLabelSheetLayoutRepository implements LabelSheetLayoutRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findById(int $id): ?LabelSheetLayout
    {
        $stmt = $this->pdo->prepare('SELECT * FROM label_sheet_layout WHERE id = :id LIMIT 1');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, scalar|null>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    public function findByCode(string $code): ?LabelSheetLayout
    {
        $stmt = $this->pdo->prepare('SELECT * FROM label_sheet_layout WHERE code = :c LIMIT 1');
        $stmt->bindValue('c', $code);
        $stmt->execute();
        /** @var array<string, scalar|null>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    public function listActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM label_sheet_layout WHERE is_active = 1 ORDER BY vendor ASC, code ASC'
        );
        if ($stmt === false) {
            return [];
        }
        /** @var list<array<string, scalar|null>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out  = [];
        foreach ($rows as $row) {
            $out[] = $this->hydrate($row);
        }
        return $out;
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function hydrate(array $row): LabelSheetLayout
    {
        return new LabelSheetLayout(
            id:              (int) $row['id'],
            code:            (string) $row['code'],
            vendor:          (string) $row['vendor'],
            productNameEn:   (string) $row['product_name_en'],
            productNameJa:   (string) $row['product_name_ja'],
            paperSize:       (string) $row['paper_size'],
            columns:         (int) $row['columns'],
            rows:            (int) $row['rows'],
            labelWidthMm:    (float) $row['label_width_mm'],
            labelHeightMm:   (float) $row['label_height_mm'],
            marginTopMm:     (float) $row['margin_top_mm'],
            marginLeftMm:    (float) $row['margin_left_mm'],
            gapXMm:          (float) $row['gap_x_mm'],
            gapYMm:          (float) $row['gap_y_mm'],
            cornerRadiusMm:  isset($row['corner_radius_mm']) ? (float) $row['corner_radius_mm'] : null,
            isActive:        (bool) $row['is_active'],
            isSeeded:        (bool) $row['is_seeded'],
            isVerified:      (bool) $row['is_verified'],
            createdAt:       new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt:       new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }
}
