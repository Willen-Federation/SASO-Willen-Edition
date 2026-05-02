<?php
namespace saso\label;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class WizardPresenter implements Presenter
{
    public function __construct(private WizardView $success)
    {
    }

    public function complete(Either $output): View
    {
        $result = $output->getOrElse(null);
        if ($result instanceof \Generator) {
            $this->success->sheets = iterator_to_array($result, false);
        } elseif (is_array($result)) {
            $this->success->sheets = $result;
        }
        return $this->success;
    }
}
