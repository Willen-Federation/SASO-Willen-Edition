<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Provider;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\CallbackRequest;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Infrastructure\Auth\Provider\SamlProvider;

final class SamlProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session state between tests so we don't bleed assertions
        // about request-id presence across cases.
        unset(
            $_SESSION['auth.saml_request_id'],
            $_SESSION['auth.state'],
            $_SESSION['auth.return_to'],
            $_SESSION['auth.provider_id'],
            $_SESSION['auth.session_index'],
        );
    }

    public function testCompleteLoginRejectsWhenNoRequestIdStashed(): void
    {
        // Defence: a SAML response that arrives without a matching outgoing
        // AuthnRequest is either a stale window or an attacker replay.
        $provider = $this->buildProvider();

        $this->expectException(AuthFailedException::class);
        $this->expectExceptionMessageMatches('/No pending SAML AuthnRequest/');

        try {
            $provider->completeLogin($this->fakeCallback());
        } catch (AuthFailedException $e) {
            self::assertSame(ErrorCode::AuthCallbackStateMismatch, $e->errorCode());
            throw $e;
        }
    }

    public function testCompleteLoginRejectsWhenRequestIdIsEmpty(): void
    {
        // Empty-string sentinel must not be treated as a valid stash.
        $_SESSION['auth.saml_request_id'] = '';
        $provider                         = $this->buildProvider();

        $this->expectException(AuthFailedException::class);
        $this->expectExceptionMessageMatches('/No pending SAML AuthnRequest/');

        $provider->completeLogin($this->fakeCallback());
    }

    public function testCompleteLoginClearsStashedRequestIdOnFailure(): void
    {
        // Once we surface a state-mismatch, the stale stash must be removed
        // so a retry has to go through beginLogin() again — otherwise the
        // attacker has an arbitrarily-long replay window.
        $_SESSION['auth.saml_request_id'] = 'leftover-id';
        $provider                         = $this->buildProvider();

        try {
            // The cert-less settings will reject inside buildAuth(); we don't
            // care about the exception type here, only the side-effect.
            $provider->completeLogin($this->fakeCallback());
            self::fail('Expected completeLogin to throw.');
        } catch (\Throwable) {
            // Expected: buildAuth() or processResponse() will throw.
        }

        // After any failure path the stash should be gone OR the original
        // value remains for replay. The current implementation clears it on
        // every error branch, which is the correct posture.
        self::assertArrayNotHasKey('auth.saml_request_id', $_SESSION);
    }

    private function buildProvider(): SamlProvider
    {
        $now = new DateTimeImmutable('2026-05-25 00:00:00');

        return new SamlProvider(
            new AuthProviderRecord(
                id:                  new AuthProviderId(1),
                name:                'Test SAML',
                type:                AuthProviderType::Saml,
                issuerOrMetadataUrl: 'https://idp.example.test/saml',
                clientId:            null,
                clientSecret:        null,
                scopes:              null,
                claimMapping:        null,
                enabled:             true,
                isDefault:           false,
                createdAt:           $now,
                updatedAt:           $now,
            ),
            acsUrl: 'https://sp.example.test/auth/saml/acs',
            slsUrl: 'https://sp.example.test/auth/saml/sls',
        );
    }

    private function fakeCallback(): CallbackRequest
    {
        return new CallbackRequest(
            method: 'POST',
            uri:    '/auth/saml/acs',
            query:  [],
            body:   [],
            headers: [],
        );
    }
}
