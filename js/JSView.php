<?php
namespace saso\js;

use saso\framework\Setter;
use saso\framework\View;

final class JSView implements View
{
    use Setter;
    private \Closure $content;
    public function __construct(
        private string $action,
        private string $csrftoken,
    )
    {
    }
    public function display(): void
    {
        header('Content-Type: text/javascript');
        require_once 'js/template/'.$this->action.'.js.php';
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

