<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;

final class RegisterFailView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private string $errorMessage;
    public function __construct(
        private string $start='',
    )
    {
    }
    public function display(): void
    {
        header("HTTP/1.1 400 Bad Request");
        require_once 'common/template/registerFail.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
