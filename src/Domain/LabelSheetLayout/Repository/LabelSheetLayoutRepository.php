<?php

declare(strict_types=1);

namespace Saso\Domain\LabelSheetLayout\Repository;

use Saso\Domain\LabelSheetLayout\LabelSheetLayout;

interface LabelSheetLayoutRepository
{
    public function findById(int $id): ?LabelSheetLayout;

    public function findByCode(string $code): ?LabelSheetLayout;

    /**
     * @return list<LabelSheetLayout>
     */
    public function listActive(): array;
}
