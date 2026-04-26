<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\HttpRequest;

final class HttpRequestTest extends TestCase
{
    public function testFromGlobalsParsesPathQueryAndHeaders(): void
    {
        $serverBackup            = $_SERVER;
        $_SERVER                 = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI']    = '/api/v1/items?lang=ja&q=widget';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja-JP,ja;q=0.9';
        $_SERVER['HTTP_X_TRACE_ID']      = 'abc-123';

        try {
            $req = HttpRequest::fromGlobals();

            self::assertSame('POST', $req->method);
            self::assertSame('/api/v1/items', $req->path);
            self::assertSame('ja', $req->query['lang'] ?? null);
            self::assertSame('widget', $req->query['q'] ?? null);
            self::assertSame('ja-JP,ja;q=0.9', $req->header('Accept-Language'));
            self::assertSame('abc-123', $req->header('X-Trace-Id'));
        } finally {
            $_SERVER = $serverBackup;
        }
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $req = new HttpRequest(
            method: 'GET',
            path: '/api/v1/health',
            headers: ['accept-language' => 'en'],
        );

        self::assertSame('en', $req->header('Accept-Language'));
        self::assertSame('en', $req->header('ACCEPT-LANGUAGE'));
        self::assertSame('en', $req->header('accept-language'));
    }

    public function testHeaderReturnsNullWhenAbsent(): void
    {
        $req = new HttpRequest(method: 'GET', path: '/health');

        self::assertNull($req->header('X-Missing'));
    }
}
