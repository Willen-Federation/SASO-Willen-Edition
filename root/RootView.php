<?php
namespace saso\root;

use saso\framework\Setter;
use saso\framework\View;

final class RootView implements View
{
    use Setter;
    private \Closure $content;
    private View $insideView;
    private string $baseUrl;
    private string $version;
    private bool $authed;
    private string $matter;
    private string $action;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        $this->insideView = ($this->inside)($this->matter, $this->action);
        $this->insideView->display();
        require_once 'root/template/root.php';
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
        if(!$this->insideView->onRoot()) return fn()=>null;
        return $this->content;
    }
}