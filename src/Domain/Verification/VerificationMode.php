<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

enum VerificationMode: string
{
    case Stocktake = 'stocktake';
    case Spot      = 'spot';
}
