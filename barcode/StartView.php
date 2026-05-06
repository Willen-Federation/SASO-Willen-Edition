<?php
namespace saso\barcode;

use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public function __construct(
    )
    {
    }
    public function display(): void
    {
        require_once 'barcode/template/start.php';
    }
    public function onRoot(): bool
    {
        return false;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
