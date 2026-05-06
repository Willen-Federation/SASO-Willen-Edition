<?php
namespace saso\mypage;

use saso\framework\Controller;
use saso\framework\DTO;

final class EditProfileController implements Controller
{
    public function __construct(
        private array $post,
    ) {
    }

    public function read(): DTO
    {
        return new EditProfileInput(
            displayName: $this->post['display_name'] ?? null,
            bio: $this->post['bio'] ?? null,
            avatarUrl: $this->post['avatar_url'] ?? null,
        );
    }
}
