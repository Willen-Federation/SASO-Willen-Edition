<?php
namespace saso\label;

use Saso\Domain\Barcode\BarcodeStatus;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\framework\DTO;
use saso\framework\Usecase;

final class PendingUsecase implements Usecase
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
        private readonly PendingView $view,
    ) {}

    public function handle(DTO $data): void
    {
        $this->view->codes = $this->barcodes->listByStatus(BarcodeStatus::Pending, limit: 200);
    }

    public function output(): \saso\framework\View
    {
        return $this->view;
    }
}
