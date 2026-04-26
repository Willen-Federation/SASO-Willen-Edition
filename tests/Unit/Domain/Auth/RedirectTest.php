<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\Redirect;

final class RedirectTest extends TestCase
{
    public function testStoresUrlAndDefaultStatus(): void
    {
        $r = new Redirect('https://idp.example/authorize?...');

        self::assertSame('https://idp.example/authorize?...', $r->url);
        self::assertSame(302, $r->status);
    }

    public function testAllows303(): void
    {
        $r = new Redirect('https://idp.example/sso', 303);

        self::assertSame(303, $r->status);
    }

    public function testRejectsEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Redirect('');
    }

    public function testRejectsNon3xxStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Redirect('https://x', 200);
    }
}
