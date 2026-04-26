<?php

declare(strict_types=1);

namespace Saso\Domain\Shared;

/**
 * Bounded-context grouping for SASO error codes (cf. ADR 0004).
 *
 * The string value matches the second segment of the canonical identifier:
 * `SASO-<DOMAIN>-<NNNN>`. Values are stable contracts — adding a domain is
 * an append-only operation; renames are forbidden.
 */
enum ErrorDomain: string
{
    case Auth    = 'AUTH';
    case Item    = 'ITEM';
    case Label   = 'LABEL';
    case Shelf   = 'SHELF';
    case Install = 'INSTALL';
    case Config  = 'CONFIG';
    case Flag    = 'FLAG';
    case Infra   = 'INFRA';
}
