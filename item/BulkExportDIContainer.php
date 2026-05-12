<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DbFinder;
use saso\repository\item\FindAllForExport;

final class BulkExportDIContainer implements DIContainer
{
    private View $view;

    public function isTopLevel(): bool
    {
        return true;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $finder = new DbFinder();
        $emptyGen = (static function (): \Generator { yield from []; })();
        $rows = $finder->generate(new FindAllForExport(), ['archive' => 0])->getOrElse($emptyGen);
        $this->view = new BulkExportView($rows);
    }

    public function flow(): View
    {
        return $this->view;
    }
}
