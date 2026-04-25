<?php
namespace saso\shelf;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\label\NameController;
use saso\label\SizePresenter;
use saso\label\SizeUsecase;
use saso\repository\DbFinder;

final class PdfDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailView();
        $this->ctrl = new NameController($post);
        $this->usecase = new SizeUsecase(
            new DbFinder(),
            new SizePresenter(
                new PdfView($inside),
                new common\FailView(),
            )
        );
    }
}

