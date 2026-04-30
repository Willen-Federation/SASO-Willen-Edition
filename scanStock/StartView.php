<?php

declare(strict_types=1);

namespace saso\scanStock;

use saso\framework\Setter;
use saso\framework\View;

/**
 * View for GET /scan/stock/
 */
final class StartView implements View
{
    use Setter;

    private string $title = '';
    private \Closure $content;

    public function display(): void
    {
        $this->title = __('ui.scan_stock.title', [], null, 'Scan to Register Stock');
        require_once 'scanStock/template/start.php';
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
