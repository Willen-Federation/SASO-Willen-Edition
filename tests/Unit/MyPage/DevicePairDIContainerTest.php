<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\MyPage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\mypage\DevicePairDIContainer;

/*
 * Regression guard for the `/mypage/devicePair/` AJAX endpoint.
 *
 * The pairing modal in mypage/template/mypage.php POSTs to the endpoint and
 * parses the response with `await res.json()`. If `isTopLevel()` returns
 * `false`, framework/Loader.php wraps the call in RootDIContainer and the
 * root layout HTML is appended to the JSON body — breaking the client-side
 * parse and surfacing as a generic "Failed to generate code" toast even
 * though the row was successfully written to `pairing_code`.
 *
 * Keep this contract in lockstep with the sibling category/PathDIContainer
 * and the AJAX branch of auth/ProviderNewDIContainer, which document the
 * same "must not be wrapped in the application layout" requirement.
 */
#[CoversClass(DevicePairDIContainer::class)]
final class DevicePairDIContainerTest extends TestCase
{
    public function testIsTopLevelSoJsonResponseIsNotWrappedInHtml(): void
    {
        $container = new DevicePairDIContainer();

        self::assertTrue(
            $container->isTopLevel(),
            'DevicePairDIContainer must be top-level so the JSON body is not '
            .'concatenated with root/template/root.php output.',
        );
    }
}
