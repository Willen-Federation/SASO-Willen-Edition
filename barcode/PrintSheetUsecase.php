<?php
namespace saso\barcode;

use saso\framework\DTO;
use saso\framework\Usecase;
use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\framework\Presenter;

final class PrintSheetUsecase implements Usecase
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
        private readonly Presenter $presenter,
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
        );

        // Pass the minted codes and layout info to the presenter/view
        $this->presenter->present([
            'codes'  => $result['codes'],
            'layout' => [
                'cols' => $data->cols,
                'rows' => $data->rows,
                'w'    => $data->wMm,
                'h'    => $data->hMm,
            ]
        ]);
    }
}
