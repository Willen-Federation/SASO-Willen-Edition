<?php

declare(strict_types=1);

namespace saso\installer;

/**
 * Outcome of the post-install self-test. Stored on the wizard's "installed"
 * page so the template can render the failure list inline.
 */
final class SelfTestResult
{
    /**
     * @param list<array{key:string, message:string}>             $failures
     * @param array<string, array{ok:bool, status:int, error:?string}> $httpResults
     */
    private function __construct(
        public readonly bool $ok,
        public readonly array $failures,
        public readonly array $httpResults,
    ) {
    }

    /** @param array<string, array{ok:bool, status:int, error:?string}> $httpResults */
    public static function ok(array $httpResults = []): self
    {
        return new self(true, [], $httpResults);
    }

    /**
     * @param list<array{key:string, message:string}>             $failures
     * @param array<string, array{ok:bool, status:int, error:?string}> $httpResults
     */
    public static function failed(array $failures, array $httpResults = []): self
    {
        return new self(false, $failures, $httpResults);
    }
}
