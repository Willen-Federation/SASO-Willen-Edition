<?php
namespace saso\barcode;

use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\framework\DTO;
use saso\framework\Usecase;
use saso\framework\View;

final class PrintSheetUsecase implements Usecase
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
        private readonly PrintSheetView $view,
    ) {
    }

    public function handle(DTO $data): void
    {
        /** @var PrintSheetController $data */
        $result = $this->barcodes->mintBatch(
            requestedCount:     $data->count,
            labelSheetLayoutId: is_numeric($data->layoutId) ? (int) $data->layoutId : null,
            createdBy:          'legacy:user:' . ($_SESSION['id'] ?? 'unknown'),
            origin:             BarcodeBatchOrigin::Web,
        );

        $this->view->codes  = $result['codes'];
        $this->view->layout = [
            'cols' => $data->cols,
            'rows' => $data->rows,
            'w'    => $data->wMm,
            'h'    => $data->hMm,
        ];
    }

    public function output(): View
    {
        return $this->view;
    }
}
