<?php
namespace saso\itemAttribute;

use saso\framework\Setter;
use saso\framework\View;

final class EditView implements View
{
    use Setter;
    private \Closure $content;

    public function display(): void
    {
        require_once 'itemAttribute/template/edit.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return __('ui.item_attribute.title', [], null, '商品ステータス項目管理');
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
