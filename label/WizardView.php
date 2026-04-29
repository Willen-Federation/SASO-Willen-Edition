<?php
namespace saso\label;

use saso\framework\Setter;
use saso\framework\View;

final class WizardView implements View
{
    use Setter;
    private string $title = '';
    private \Closure $content;
    public array $sheets = [];

    public function display(): void
    {
        require_once 'label/template/wizard.php';
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
