<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class PaginationView implements View
{
    use Setter;
    private \Closure $content;
    private int $pageAmount;
    private string $sortBy;
    private string $direction;
    private string $search;
    private string $page;
    public function __construct(
        private string $request,
    )
    {
    }
    public function display(): void
    {
        require_once 'item/template/pagination.php';
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
