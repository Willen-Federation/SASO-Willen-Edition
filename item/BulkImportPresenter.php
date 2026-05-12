<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class BulkImportPresenter implements Presenter
{
    public function __construct(private View $view)
    {
    }

    public function complete(Either $output): View
    {
        $output->flatMap($this->view->results(fn($v) => $v->results))
               ->flatMap($this->view->successCount(fn($v) => $v->successCount))
               ->flatMap($this->view->errorCount(fn($v) => $v->errorCount));
        return $this->view;
    }
}
