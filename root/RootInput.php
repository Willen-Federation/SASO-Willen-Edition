<?php
namespace saso\root;

use saso\framework\DTO;
use saso\framework\Getter;

final class RootInput implements DTO
{
    use Getter;
    public function __construct(
        private bool $protocol,
        private string $programDir,
        private string $version,
        private bool $authed,
        private string $matter,
        private string $action,
        private string $currentLocale,
        /** @var list<string> */
        private array $supportedLocales,
    )
    {
    }
}
