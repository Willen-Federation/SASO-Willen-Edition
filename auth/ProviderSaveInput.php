<?php

namespace saso\auth;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class ProviderSaveInput implements DTO
{
    use GetterAndAnother;

    public function __construct(
        private string  $template,
        private string  $providerName,
        private string  $type,
        private string  $issuerUrl,
        private string  $clientId,
        private ?string $clientSecret,
        private ?string $scopes,
        private ?DTO    $another = null,
    ) {
        $this->another ??= new EmptyIO();
    }
}
