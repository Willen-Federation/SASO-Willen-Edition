<?php

declare(strict_types=1);

namespace Saso\Presentation\Http\Problem;

use Saso\Domain\Shared\ErrorCode;

/**
 * RFC 7807 Problem Details value object with SASO vendor extensions
 * (`code`, `traceId`).
 *
 * The wire format is fixed by ADR 0004:
 *
 *     Content-Type: application/problem+json
 *
 *     {
 *       "type":     "<canonical-url>#<code>",
 *       "title":    "<short, human-readable summary>",
 *       "status":   <int>,
 *       "detail":   "<request-specific message>",
 *       "instance": "<request URI>",
 *       "code":     "SASO-<DOMAIN>-<NNNN>",
 *       "traceId":  "<UUIDv4>"
 *     }
 *
 * Clients should branch on `code`. `title` and `detail` are localised
 * strings whose wording may change between releases.
 */
final readonly class ProblemDetails
{
    public const DEFAULT_TYPE_BASE_URL = 'https://docs.willen-federation.org/error-codes#';

    /**
     * @param array<string, mixed> $extensions further vendor extensions
     */
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        public string $instance,
        public string $code,
        public string $traceId,
        public array $extensions = [],
    ) {
    }

    public static function fromError(
        ErrorCode $code,
        string $title,
        string $detail,
        string $instance,
        string $traceId,
        ?string $typeBaseUrl = null,
    ): self {
        return new self(
            type: ($typeBaseUrl ?? self::DEFAULT_TYPE_BASE_URL).$code->value,
            title: $title,
            status: $code->httpStatus(),
            detail: $detail,
            instance: $instance,
            code: $code->value,
            traceId: $traceId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'type'     => $this->type,
                'title'    => $this->title,
                'status'   => $this->status,
                'detail'   => $this->detail,
                'instance' => $this->instance,
                'code'     => $this->code,
                'traceId'  => $this->traceId,
            ],
            $this->extensions,
        );
    }
}
