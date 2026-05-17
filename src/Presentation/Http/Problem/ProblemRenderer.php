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
     *
     * For Bearer-auth failures (401/403 on `SASO-MOBILE-2…`) we additionally
     * emit an RFC 6750 `WWW-Authenticate` header so mobile clients can
     * distinguish "missing token" from "wrong scope" without parsing the
     * problem body.
     */
    public function emit(ProblemDetails $problem): void
    {
        if (!headers_sent()) {
            http_response_code($problem->status);
            header('Content-Type: application/problem+json; charset=utf-8');
            $challenge = $this->bearerChallenge($problem);
            if ($challenge !== null) {
                header('WWW-Authenticate: '.$challenge);
            }
        }
        echo $this->encode($problem);
    }

    /**
     * Returns the RFC 6750 `WWW-Authenticate` challenge string for the given
     * Problem, or `null` if no Bearer challenge should be emitted.
     *
     * Public so unit tests can lock the wire contract without exercising
     * the side-effectful {@see emit()} path.
     */
    public function bearerChallenge(ProblemDetails $problem): ?string
    {
        if ($problem->code === 'SASO-MOBILE-2008') {
            $scope     = is_string($problem->extensions['requiredScope'] ?? null)
                ? (string) $problem->extensions['requiredScope']
                : '';
            $scopePart = $scope !== '' ? sprintf(', scope="%s"', $this->escape($scope)) : '';

            return sprintf('Bearer realm="api", error="insufficient_scope"%s', $scopePart);
        }

        if ($problem->status === 401 && str_starts_with($problem->code, 'SASO-MOBILE-')) {
            return 'Bearer realm="api", error="invalid_token"';
        }

        return null;
    }

    private function escape(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_:\-\.]/', '', $value) ?? '';
    }
}
