<?php

namespace saso\mypage;

use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;
use saso\repository\DBConnection;
use saso\util\monad\Either;

final class EditProfileSaveUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private DbFinder $finder,
        private DbUpdater $updater,
        private Presenter $presenter,
        private string $memberId,
    ) {
    }

    public function handle(DTO $data): void
    {
        // Validate input
        $displayName = $data->displayName;
        $bio = $data->bio;
        $avatarUrl = $data->avatarUrl;

        // Validate constraints
        $validatedDisplayName = Member::displayNameConstraint($displayName);
        $validatedBio = Member::bioConstraint($bio);
        $validatedAvatarUrl = Member::avatarUrlConstraint($avatarUrl);

        $result = $validatedDisplayName
            ->flatMap(fn($dn) => $validatedBio
                ->map(fn($b) => [$dn, $b])
            )
            ->flatMap(fn($arr) => $validatedAvatarUrl
                ->map(fn($au) => [...$arr, $au])
            );

        if ($result->isLeft()) {
            $this->output = new MyPageErrorOutput('Validation failed');
            return;
        }

        if ($this->profileColumnsExist()) {
            $this->updater->exec(
                new EditProfileUpdateRepository(),
                [
                    'id' => $this->memberId,
                    'display_name' => $displayName,
                    'bio' => $bio,
                    'avatar_url' => $avatarUrl,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        }

        // Fetch updated Member
        $member = $this->finder->current(
            new \saso\repository\member\FindOne(),
            ['id' => $this->memberId]
        )->getOrElse(null);

        if ($member === null) {
            $this->output = new MyPageErrorOutput('Member not found after update');
            return;
        }

        $this->output = new EditProfileOutput(
            member: $member,
        );
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }

    private function profileColumnsExist(): bool
    {
        $stmt = DBConnection::pdo()->query("SHOW COLUMNS FROM Member LIKE 'display_name'");
        return $stmt !== false && $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }
}
