<?php
namespace saso\member;

use saso\repository\Finder;
use saso\framework\View;
use saso\repository\member\FindAll;

final class StartUsecase
{
    public function __construct(
        private Finder $finder,
        private StartPresenter $presenter
    ) {}
    public function exec(): View
    {
        $members = $this->finder->generate(new FindAll(), [])->getOrElse([]);
        $arr = is_array($members) ? $members : iterator_to_array($members, false);
        return $this->presenter->view($arr);
    }
}

