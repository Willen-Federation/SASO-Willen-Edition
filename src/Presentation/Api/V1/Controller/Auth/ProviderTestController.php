<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Auth;

use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Throwable;

/**
 * POST /api/v1/auth/providers/{id}/test
 *
 * Probes the provider's discovery / metadata endpoint to verify
 * connectivity and basic configuration.
 *
 * For OIDC providers: performs an HTTP GET against the
 * `issuer_or_metadata_url` and checks the JSON contains the
 * required `issuer` and `authorization_endpoint` keys.
 *
 * For SAML providers: verifies the metadata URL is reachable.
 *
 * Secrets are never sent over the network in this probe.
 */
final class ProviderTestController
{
    public function __construct(
        private readonly AuthProviderRepository $providers,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $id = (int) ($request->pathParams['id'] ?? 0);
        if ($id <= 0) {
            throw new class ('Invalid provider ID.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

        $record = $this->providers->findById(new AuthProviderId($id));
        if ($record === null) {
            throw new class ('Auth provider not found.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::InfraRouteNotFound, $msg);
                }
            };
        }

        $url = $record->issuerOrMetadataUrl;
        if ($url === null || $url === '') {
            return new JsonResponse(
                status: 422,
                body: [
                    'ok'      => false,
                    'message' => 'Provider has no discovery/metadata URL configured.',
                ],
            );
        }

        try {
            [$ok, $message, $details] = $this->probe($url, $record->type->value);
        } catch (Throwable $e) {
            return new JsonResponse(
                status: 502,
                body: [
                    'ok'      => false,
                    'message' => 'Probe error: '.$e->getMessage(),
                ],
            );
        }

        return new JsonResponse(
            status: $ok ? 200 : 502,
            body: array_filter([
                'ok'      => $ok,
                'message' => $message,
                'details' => $details,
            ], static fn ($v): bool => $v !== null),
        );
    }

    /**
     * @return array{bool, string, array<string, mixed>|null}
     */
    private function probe(string $url, string $type): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => 10,
                'ignore_errors'   => true,
                'follow_location' => 1,
                'max_redirects'   => 5,
                'header'          => 'Accept: application/json, application/xml, */*',
            ],
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            return [false, 'Could not reach the discovery URL: '.$url, null];
        }

        if ($type === 'oidc') {
            $doc = json_decode($body, true);
            if (!is_array($doc)) {
                return [false, 'Discovery endpoint did not return valid JSON.', null];
            }

            $missing = [];
            foreach (['issuer', 'authorization_endpoint', 'token_endpoint'] as $key) {
                if (empty($doc[$key])) {
                    $missing[] = $key;
                }
            }

            if ($missing !== []) {
                return [
                    false,
                    'Discovery document is missing required fields: '.implode(', ', $missing),
                    ['issuer' => $doc['issuer'] ?? null],
                ];
            }

            return [
                true,
                'OIDC discovery endpoint reachable. Issuer: '.$doc['issuer'],
                [
                    'issuer'                  => $doc['issuer'],
                    'authorization_endpoint'  => $doc['authorization_endpoint'],
                    'token_endpoint'          => $doc['token_endpoint'],
                    'userinfo_endpoint'       => $doc['userinfo_endpoint'] ?? null,
                    'end_session_endpoint'    => $doc['end_session_endpoint'] ?? null,
                ],
            ];
        }

        // SAML: just verify the URL is reachable
        return [true, 'SAML metadata endpoint reachable ('.strlen($body).' bytes).', null];
    }
}
