<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

enum ShelfDimensionType: string
{
    case Letter = 'letter';
    case Numeric = 'numeric';
}
