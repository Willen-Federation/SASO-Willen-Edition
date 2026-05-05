<?php
namespace saso\role;

use saso\framework\View;
use saso\repository\Finder;
use saso\repository\role\FindAll;

final class StartUsecase
{
    public function __construct(
        private Finder $finder,
        private StartPresenter $presenter,
    ) {}

    public function exec(): View
    {
        $roles = $this->finder->generate(new FindAll(), [])->getOrElse([]);
        $arr   = is_array($roles) ? $roles : iterator_to_array($roles, false);
        return $this->presenter->view($arr);
    }
}
