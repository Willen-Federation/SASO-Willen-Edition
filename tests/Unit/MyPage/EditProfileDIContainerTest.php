<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\MyPage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\mypage\EditProfileController;
use saso\mypage\EditProfileDIContainer;
use saso\mypage\MyPageErrorUsecase;
use saso\util\CSRFtoken;

/*
 * Guards the CSRF check on POST /mypage/editProfile/.
 *
 * Pre-fix the container blindly dispatched to EditProfileSaveUsecase
 * whenever $_POST was non-empty, so a malicious cross-site form could
 * silently rewrite the logged-in member's display_name / bio / avatar_url.
 *
 * The reflection on `usecase` is deliberate — Flow trait declares it
 * private, and the rest of the framework treats it as an opaque handle.
 */
#[CoversClass(EditProfileDIContainer::class)]
final class EditProfileDIContainerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testPostWithoutCsrfTokenIsRejectedWithErrorUsecase(): void
    {
        $_SESSION['id'] = 'mallory_001';
        // No CSRFtoken::current() pre-seed → submitted token cannot match.

        $container = new EditProfileDIContainer();
        $container->di(
            static fn () => null,
            [],
            ['display_name' => 'Pwn', 'bio' => 'x', 'avatar_url' => 'https://e/avatar.png'],
            [],
            new \DateTime(),
        );

        self::assertInstanceOf(MyPageErrorUsecase::class, $this->extractUsecase($container));
    }

    public function testPostWithWrongCsrfTokenIsRejected(): void
    {
        $_SESSION['id']                  = 'mallory_001';
        $_SESSION['__saso_csrftoken']    = bin2hex(random_bytes(32));

        $container = new EditProfileDIContainer();
        $container->di(
            static fn () => null,
            [],
            ['csrftoken' => 'not-the-real-token', 'display_name' => 'Pwn'],
            [],
            new \DateTime(),
        );

        self::assertInstanceOf(MyPageErrorUsecase::class, $this->extractUsecase($container));
    }

    public function testPostWithValidCsrfTokenDispatchesToSaveController(): void
    {
        $_SESSION['id']               = 'alice_001';
        $token                        = CSRFtoken::current();

        $container = new EditProfileDIContainer();
        $container->di(
            static fn () => null,
            [],
            [
                'csrftoken'    => $token,
                'display_name' => 'Alice',
                'bio'          => 'hi',
                'avatar_url'   => '',
            ],
            [],
            new \DateTime(),
        );

        $ctrl = $this->extractCtrl($container);
        self::assertInstanceOf(EditProfileController::class, $ctrl);
    }

    public function testUnauthenticatedPostShortCircuitsBeforeCsrfCheck(): void
    {
        // No session id at all → must surface MyPageErrorUsecase('Not authenticated')
        // before we even look at the CSRF input. This keeps the "you are signed out"
        // banner working regardless of the cross-site form.
        $container = new EditProfileDIContainer();
        $container->di(
            static fn () => null,
            [],
            ['csrftoken' => 'anything', 'display_name' => 'x'],
            [],
            new \DateTime(),
        );

        self::assertInstanceOf(MyPageErrorUsecase::class, $this->extractUsecase($container));
    }

    private function extractUsecase(EditProfileDIContainer $container): mixed
    {
        $ref = new \ReflectionClass($container);
        $prop = $ref->getProperty('usecase');
        $prop->setAccessible(true);
        return $prop->getValue($container);
    }

    private function extractCtrl(EditProfileDIContainer $container): mixed
    {
        $ref = new \ReflectionClass($container);
        $prop = $ref->getProperty('ctrl');
        $prop->setAccessible(true);
        return $prop->getValue($container);
    }
}
