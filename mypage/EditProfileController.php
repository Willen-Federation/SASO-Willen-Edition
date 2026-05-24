<?php
namespace saso\mypage;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class EditProfileController implements Controller
{
    use Input;

    private DTO $data;

    public function __construct(array $post)
    {
        $this->data = new EditProfileInput(
            displayName: $post['display_name'] ?? null,
            bio: $post['bio'] ?? null,
            avatarUrl: $post['avatar_url'] ?? null,
        );
    }
}
