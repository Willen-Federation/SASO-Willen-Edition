<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\MyPage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\entity\Member;
use saso\mypage\EditProfileController;
use saso\mypage\EditProfileSavePresenter;
use saso\mypage\EditProfileSaveUsecase;
use saso\mypage\EditProfileSaveView;
use saso\mypage\MyPageErrorOutput;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;

/*
 * Verifies that input that fails the Member constraints (e.g. a malformed
 * avatar URL or an over-length bio) never reaches the database — the usecase
 * must short-circuit to MyPageErrorOutput before profileColumnsExist() runs.
 *
 * The success path can only be tested with a live PDO (profileColumnsExist
 * issues `SHOW COLUMNS FROM Member`), so we exercise only the failure paths
 * here and assert the resulting Output is the error shape.
 */
#[CoversClass(EditProfileSaveUsecase::class)]
final class EditProfileSaveUsecaseTest extends TestCase
{
    public function testRejectsAvatarUrlWithNonHttpScheme(): void
    {
        $usecase = new EditProfileSaveUsecase(
            new DbFinder(),
            new DbUpdater(),
            new EditProfileSavePresenter(new EditProfileSaveView()),
            'alice_001',
        );

        $ctrl = new EditProfileController([
            'display_name' => 'Alice',
            'bio'          => 'hi',
            'avatar_url'   => 'ftp://evil.example/avatar.png',
        ]);
        $ctrl->input($usecase);

        self::assertInstanceOf(MyPageErrorOutput::class, $this->extractOutput($usecase));
    }

    public function testRejectsBioOverFiveHundredChars(): void
    {
        $usecase = new EditProfileSaveUsecase(
            new DbFinder(),
            new DbUpdater(),
            new EditProfileSavePresenter(new EditProfileSaveView()),
            'alice_001',
        );

        $ctrl = new EditProfileController([
            'display_name' => 'Alice',
            'bio'          => str_repeat('a', 501),
            'avatar_url'   => '',
        ]);
        $ctrl->input($usecase);

        self::assertInstanceOf(MyPageErrorOutput::class, $this->extractOutput($usecase));
    }

    public function testRejectsDisplayNameOverHundredChars(): void
    {
        $usecase = new EditProfileSaveUsecase(
            new DbFinder(),
            new DbUpdater(),
            new EditProfileSavePresenter(new EditProfileSaveView()),
            'alice_001',
        );

        $ctrl = new EditProfileController([
            'display_name' => str_repeat('a', 101),
            'bio'          => '',
            'avatar_url'   => '',
        ]);
        $ctrl->input($usecase);

        self::assertInstanceOf(MyPageErrorOutput::class, $this->extractOutput($usecase));
    }

    private function extractOutput(EditProfileSaveUsecase $usecase): mixed
    {
        $ref = new \ReflectionClass($usecase);
        $prop = $ref->getProperty('output');
        $prop->setAccessible(true);
        return $prop->getValue($usecase);
    }
}
