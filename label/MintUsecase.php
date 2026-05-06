<?php
namespace saso\label;

use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\framework\DTO;
use saso\framework\Usecase;
use saso\repository\Finder;
use saso\repository\label\FindSheetLayoutById;

final class MintUsecase implements Usecase
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
        private readonly Finder $finder,
        private readonly MintView $view,
    ) {}

    public function handle(DTO $data): void
    {
        /** @var MintController $data */
        $layoutResult = $this->finder->current(new FindSheetLayoutById($data->sheetLayoutId));
        $layout = $layoutResult->getOrElse(null);

        $result = $this->barcodes->mintBatch(
            requestedCount:     $data->count,
            labelSheetLayoutId: $layout ? (int) $layout->id : null,
            createdBy:          'web:' . ($_SESSION['id'] ?? 'unknown'),
            origin:             BarcodeBatchOrigin::Web,
        );

        $this->view->codes  = $result['codes'];
        $this->view->layout = [
            'w' => $layout ? (float) $layout->label_width_mm  : 70.0,
            'h' => $layout ? (float) $layout->label_height_mm : 37.0,
        ];
    }

    public function output(): \saso\framework\View
    {
        return $this->view;
    }
}
