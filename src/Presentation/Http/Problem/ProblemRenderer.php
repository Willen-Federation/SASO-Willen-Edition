<?php

declare(strict_types=1);

namespace Saso\Presentation\Http\Problem;

/**
 * Serialises and emits `application/problem+json` responses.
 *
 * `encode()` is pure (returns the JSON body) and is the only path the unit
 * tests exercise. `emit()` performs the actual HTTP side effects and is
 * exercised end-to-end through the integration suite.
 */
final class ProblemRenderer
{
    /**
     * Pure encoder — produces the JSON body for a Problem Details object.
     *
     * Flags are chosen to keep `type` URLs and Japanese text legible in the
     * wire payload and the log: forward slashes are not escaped, multibyte
     * characters are not converted to `\uXXXX`.
     */
    public function encode(ProblemDetails $problem): string
    {
        $json = json_encode(
            $problem->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        // json_encode with this input shape never fails (all keys are
        // strings, no resources/closures). The cast keeps PHPStan happy
        // without an unreachable error branch.
        return (string) $json;
    }

    /**
     * Sends the response: status line, Content-Type, body. Skips header
     * emission if headers were already sent (e.g. by a partial Web view
     * before an exception).
     */
    public function emit(ProblemDetails $problem): void
    {
        if (!headers_sent()) {
            http_response_code($problem->status);
            header('Content-Type: application/problem+json; charset=utf-8');
        }
        echo $this->encode($problem);
    }
}
