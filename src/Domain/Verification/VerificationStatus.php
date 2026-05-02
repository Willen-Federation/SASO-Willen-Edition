<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

enum VerificationStatus: string
{
    case Active     = 'active';
    case Completed  = 'completed';
    case Abandoned  = 'abandoned';
}
