<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

/**
 * Speaker role for a {@see ChatMessage}. Values match the OpenAI /
 * Anthropic / Gemini chat schemas verbatim so adapters can pass them
 * through without translation.
 */
enum ChatRole: string
{
    case System    = 'system';
    case User      = 'user';
    case Assistant = 'assistant';
    case Tool      = 'tool';
}
