<?php

namespace saso\auth;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;

final class ProviderTestUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        $template = (string) $data->template;
        $url      = (string) $data->issuerUrl;

        if ($url === '') {
            $this->output = new ProviderTestOutput(
                ok: false,
                message: 'No discovery / metadata URL could be derived from the form.',
            );
            return;
        }

        if ($template === 'saml') {
            [$ok, $message, $details] = $this->probeSaml($url);
        } else {
            [$ok, $message, $details] = $this->probeOidc($url);
        }

        $this->output = new ProviderTestOutput(
            ok: $ok,
            message: $message,
            details: $details,
        );
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }

    /**
     * @return array{bool, string, array<string, mixed>|null}
     */
    private function probeOidc(string $issuer): array
    {
        $discoveryUrl = rtrim($issuer, '/');
        if (!str_ends_with($discoveryUrl, '/openid-configuration')) {
            $discoveryUrl .= '/.well-known/openid-configuration';
        }

        [$body, $httpStatus, $err] = $this->fetch($discoveryUrl);
        if ($body === false) {
            return [false, 'Could not reach '.$discoveryUrl.($err !== '' ? ' — '.$err : ''), null];
        }

        $doc = json_decode($body, true);
        if (!is_array($doc)) {
            return [
                false,
                'Discovery endpoint did not return valid JSON ('.$httpStatus.').',
                ['preview' => substr($body, 0, 200)],
            ];
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
                'issuer'                 => $doc['issuer'],
                'authorization_endpoint' => $doc['authorization_endpoint'],
                'token_endpoint'         => $doc['token_endpoint'],
                'end_session_endpoint'   => $doc['end_session_endpoint'] ?? null,
            ],
        ];
    }

    /**
     * @return array{bool, string, array<string, mixed>|null}
     */
    private function probeSaml(string $url): array
    {
        [$body, $httpStatus, $err] = $this->fetch($url);
        if ($body === false) {
            return [false, 'Could not reach '.$url.($err !== '' ? ' — '.$err : ''), null];
        }
        if (!str_contains($body, 'EntityDescriptor')) {
            return [
                false,
                'Response is not SAML metadata — no EntityDescriptor element ('.$httpStatus.').',
                ['preview' => substr($body, 0, 200)],
            ];
        }
        return [true, 'SAML metadata reachable ('.$httpStatus.', '.strlen($body).' bytes).', null];
    }

    /**
     * @return array{0: string|false, 1: string, 2: string}
     */
    private function fetch(string $url): array
    {
        $err = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$err): bool {
            $err = $errstr;
            return true;
        });

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 10,
                'ignore_errors' => true,
                'header'        => "Accept: application/json, application/xml, */*\r\nUser-Agent: SASO-Auth-Probe/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        restore_error_handler();

        $status = '';
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            $status = (string) $http_response_header[0];
        }

        return [$body, $status, $err];
    }
}
