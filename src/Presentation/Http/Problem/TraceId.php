<?php

declare(strict_types=1);

namespace Saso\Presentation\Http\Problem;

/**
 * UUIDv4 generator for request correlation.
 *
 * The same identifier appears in the response (`traceId` extension on the
 * RFC 7807 body) and in the Monolog log line for the failure. It is the
 * only token operators need to attach to a support request to let an
 * engineer locate the full server-side trace.
 */
final class TraceId
{
    public static function generate(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);  // version 4
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);  // variant 10

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
