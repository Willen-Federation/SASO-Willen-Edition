<?php
namespace saso\barcode;

use saso\framework\DTO;
use saso\framework\Usecase;
use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
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
        
        // Mint the barcodes in the database
        $result = $this->barcodes->mintBatch(
            requestedCount:     $data->count,
            labelSheetLayoutId: is_numeric($data->layoutId) ? (int) $data->layoutId : null,
            createdBy:          'legacy:user:' . ($_SESSION['id'] ?? 'unknown'),
            origin:             BarcodeBatchOrigin::Web,
            prefix:             $data->prefix,
            startNo:            $data->startNo,
            codeType:           $data->codeType,
        );

        // Pass the minted codes and layout info to the presenter/view
        $this->view->present([
            'codes'  => $result['codes'],
            'layout' => [
                'cols' => $data->cols,
                'rows' => $data->rows,
                'w'    => $data->wMm,
                'h'    => $data->hMm,
                'codeType' => $data->codeType,
            ]
        ]);
    }

    public function output(): View
    {
        return $this->view;
    }
}
