<?php
namespace saso\shelf;

use saso\framework\View;
use saso\framework\Setter;

final class MapView implements View
{
    use Setter;

    /** @var list<Saso\Domain\StorageLocation\StorageLocation> */
    public array $pins = [];

    public \Closure $content;

    public function display(): void
    {
        require_once 'shelf/template/map.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return 'Shelf Map';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
