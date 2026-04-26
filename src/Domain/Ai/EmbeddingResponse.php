<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use InvalidArgumentException;

/**
 * Result of {@see AiAssistant::embed()}.
 *
 * `vectors` carries one float vector per input (text inputs first,
 * then image inputs, in the same order). Each vector is a `list<float>`
 * — adapters may downcast from `float64` to `float32` if the provider
 * does so natively, but the in-memory shape stays floats.
 */
final readonly class EmbeddingResponse
{
    /**
     * @param list<list<float>> $vectors
     */
    public function __construct(
        public array $vectors,
        public AiUsage $usage,
        public string $model,
    ) {
        if ($vectors === []) {
            throw new InvalidArgumentException('EmbeddingResponse.vectors must not be empty.');
        }
        $expectedDim = count($vectors[0]);
        if ($expectedDim < 1) {
            throw new InvalidArgumentException('EmbeddingResponse.vectors must carry non-empty vectors.');
        }
        foreach ($vectors as $i => $v) {
            if (count($v) !== $expectedDim) {
                throw new InvalidArgumentException(sprintf(
                    'EmbeddingResponse.vectors must all have the same dimension (vector %d has %d, expected %d).',
                    $i,
                    count($v),
                    $expectedDim,
                ));
            }
        }
    }

    public function dimensions(): int
    {
        return count($this->vectors[0]);
    }
}
