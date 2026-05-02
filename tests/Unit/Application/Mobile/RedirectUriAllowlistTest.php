<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Mobile;

use PHPUnit\Framework\TestCase;
use Saso\Application\Mobile\RedirectUriAllowlist;

final class RedirectUriAllowlistTest extends TestCase
{
    public function testExactCustomSchemeMatch(): void
    {
        $a = new RedirectUriAllowlist(['jp.willen.saso://callback']);
        self::assertTrue($a->isAllowed('jp.willen.saso://callback'));
        self::assertFalse($a->isAllowed('jp.willen.saso://callback/extra'));
        self::assertFalse($a->isAllowed('jp.willen.evil://callback'));
    }

    public function testWildcardPortAcceptsAnyPort(): void
    {
        $a = new RedirectUriAllowlist(['http://localhost:*']);
        self::assertTrue($a->isAllowed('http://localhost:5000'));
        self::assertTrue($a->isAllowed('http://localhost:65535'));
        self::assertTrue($a->isAllowed('http://localhost:5000/foo'));
        self::assertFalse($a->isAllowed('http://localhost'));        // missing port
        self::assertFalse($a->isAllowed('http://localhost:abcd'));   // non-digit
        self::assertFalse($a->isAllowed('http://example.com:5000')); // wrong host
        self::assertFalse($a->isAllowed('https://localhost:5000'));  // wrong scheme
    }

    public function testWildcardPortWithSuffix(): void
    {
        $a = new RedirectUriAllowlist(['http://localhost:*/auth/callback']);
        self::assertTrue($a->isAllowed('http://localhost:5000/auth/callback'));
        self::assertFalse($a->isAllowed('http://localhost:5000/other'));
    }

    public function testEmptyAndOversizedRejected(): void
    {
        $a = new RedirectUriAllowlist(['jp.willen.saso://callback']);
        self::assertFalse($a->isAllowed(''));
        self::assertFalse($a->isAllowed(str_repeat('a', 2049)));
    }

    public function testEnvOverrideAppendsToConfig(): void
    {
        putenv('MOBILE_REDIRECT_URI_ALLOWLIST=foo://bar, http://demo.local:*');
        try {
            $a = RedirectUriAllowlist::fromConfig([
                'mobile' => ['redirect_uri_allowlist' => ['jp.willen.saso://callback']],
            ]);
            self::assertTrue($a->isAllowed('jp.willen.saso://callback'));
            self::assertTrue($a->isAllowed('foo://bar'));
            self::assertTrue($a->isAllowed('http://demo.local:8080'));
        } finally {
            putenv('MOBILE_REDIRECT_URI_ALLOWLIST');
        }
    }
}
