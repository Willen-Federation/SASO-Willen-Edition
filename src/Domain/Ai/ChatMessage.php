<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use InvalidArgumentException;

/**
 * One turn of a chat conversation. Vendor adapters translate this into
 * the provider-specific message shape (OpenAI's `messages` array,
 * Anthropic's `content` blocks, Gemini's `contents`).
 */
final readonly class ChatMessage
{
    public function __construct(
        public ChatRole $role,
        public string $content,
    ) {
        if ($content === '') {
            throw new InvalidArgumentException('ChatMessage.content must not be empty.');
        }
    }

    public static function system(string $content): self
    {
        return new self(ChatRole::System, $content);
    }

    public static function user(string $content): self
    {
        return new self(ChatRole::User, $content);
    }

    public static function assistant(string $content): self
    {
        return new self(ChatRole::Assistant, $content);
    }
}
