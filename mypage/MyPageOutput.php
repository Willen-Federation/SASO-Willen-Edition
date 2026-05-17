<?php

namespace saso\mypage;

use saso\common\EmptyIO;
use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class MyPageOutput implements DTO
{
    use GetterAndAnother;

    private ?DTO $another;

    public function __construct(
        private readonly Member $member,
        private readonly array $authMethods = [],
        private readonly array $availableProviders = [],
        private readonly array $passkeys = [],
        private readonly array $devices = [],
        private readonly string $apiBaseUrl = '',
        private readonly string $apiDocsUrl = '',
        private readonly string $openApiUrl = '',
        private readonly array $defaultScopes = [],
    ) {
        $this->another = new EmptyIO();
    }
}
