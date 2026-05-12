<?php
namespace saso\root;

use saso\framework\DTO;
use saso\framework\Getter;

final class RootOutput implements DTO
{
    use Getter;
    public function __construct(
        private string $url,
        private string $version,
        private bool $authed,
        private string $matter,
        private string $action,
        private string $currentLocale,
        private array $supportedLocales,
        /** @var list<string> */
        private array $permissions = [],
    ) {
    }
}
