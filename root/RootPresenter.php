<?php
namespace saso\root;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class RootPresenter implements Presenter
{
    public function __construct(
        private View $success
    )
    {
    }
    public function complete(Either $output): View
    {
        $output->flatMap(
            $this->success->baseUrl(fn($v)=>$v->url)
        )->flatMap(
            $this->success->version(fn($v)=>$v->version)
        )->flatMap(
            $this->success->authed(fn($v)=>$v->authed)
        )->flatMap(
            $this->success->matter(fn($v)=>$v->matter)
        )->flatMap(
            $this->success->action(fn($v)=>$v->action)
        )->flatMap(
            $this->success->currentLocale(fn($v)=>$v->currentLocale)
        )->flatMap(
            $this->success->supportedLocales(fn($v)=>$v->supportedLocales)
        );

        return $this->success;
    }
}