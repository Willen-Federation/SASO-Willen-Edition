<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

/**
 * Hint for what the embedding will be used for. Some providers (notably
 * Gemini and certain Cohere models) tune the embedding output for the
 * declared task; providers that don't ignore the hint.
 *
 * Use {@see RetrievalQuery} when embedding the user-supplied search
 * string and {@see RetrievalPassage} when embedding documents that go
 * into the index — these sit on opposite sides of the cosine and the
 * tuning matters.
 */
enum EmbeddingTask: string
{
    case RetrievalQuery   = 'retrieval.query';
    case RetrievalPassage = 'retrieval.passage';
    case Clustering       = 'clustering';
    case Classification   = 'classification';
    case Similarity       = 'similarity';
}
