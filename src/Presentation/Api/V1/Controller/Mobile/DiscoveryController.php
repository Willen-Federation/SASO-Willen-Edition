<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/mobile/discovery (public, no auth).
 *
 * Discovery endpoint that the SASO mobile app calls after the user enters
 * a server URL. The response tells the app:
 *
 *   - `mobileSetupUrl` — where to open the in-app browser to start the
 *     pairing flow. The app appends `?redirect_uri=jp.willen.saso://callback&state=…`.
 *   - `providers` — list of enabled `auth_provider` rows that can be used
 *     for login. Includes the type and a stable id for chooser UIs.
 *   - `authStrategy` — `"default-only"`, `"user-choice"`, or `"local-only"`.
 *     Lets the app skip the chooser when the server has marked one IdP
 *     as default (or when no IdPs are configured at all).
 *
 * Secrets (`client_secret`, `private_key`) are never included.
 */
final class DiscoveryController
{
    public function __construct(
        private readonly AuthProviderRepository $providers,
        private readonly string $serverName,
        private readonly string $version,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $baseUrl = $this->resolveBaseUrl($request);

        $records = $this->providers->listEnabled();

        $providerList = [];
        $hasDefault   = false;
        $hasNonLocal  = false;
        foreach ($records as $rec) {
            if ($rec->isDefault) {
                $hasDefault = true;
            }
            if ($rec->type !== AuthProviderType::Local) {
                $hasNonLocal = true;
            }
            $providerList[] = [
                'id'        => $rec->id->value,
                'name'      => $rec->name,
                'type'      => $rec->type->value,
                'isDefault' => $rec->isDefault,
                'enabled'   => $rec->enabled,
            ];
        }

        if (!$hasNonLocal) {
            $strategy = 'local-only';
        } elseif ($hasDefault) {
            $strategy = 'default-only';
        } else {
            $strategy = 'user-choice';
        }

        return new JsonResponse(
            status: 200,
            body: [
                'serverName'     => $this->serverName,
                'version'        => $this->version,
                'mobileSetupUrl' => $baseUrl.'/m/setup',
                'authStrategy'   => $strategy,
                'providers'      => $providerList,
            ],
        );
    }

    private function resolveBaseUrl(HttpRequest $request): string
    {
        $proto = $request->header('x-forwarded-proto')
            ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host  = $request->header('x-forwarded-host')
            ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $proto.'://'.$host;
    }
}
