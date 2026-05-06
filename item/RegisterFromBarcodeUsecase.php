<?php
namespace saso\item;

use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\framework\DTO;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\framework\View;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;
use saso\util\monad\Left;

final class RegisterFromBarcodeUsecase implements Usecase
{
    private Either $result;

    public function __construct(
        private readonly Finder $finder,
        private readonly Updater $updater,
        private readonly TransactionInterface $transaction,
        private readonly BarcodeRepository $barcodes,
        private readonly Presenter $presenter,
    ) {
        $this->result = Either::left('not yet run');
    }

    public function handle(DTO $data): void
    {
        /** @var RegisterFromBarcodeController $data */

        // Capture the item path produced by RegisterUsecase
        $capture = new class implements Presenter {
            public Either $captured;
            public function complete(Either $output): View
            {
                $this->captured = $output;
                return new \saso\common\RegisterSuccessView();
            }
        };
        $capture->captured = Either::left('Item registration did not produce output.');

        $registerUsecase = new RegisterUsecase(
            $this->finder,
            $this->updater,
            $this->transaction,
            $capture,
        );
        $registerUsecase->handle($data->registerData);
        $registerUsecase->output(); // triggers $capture->complete()

        if ($capture->captured instanceof Left) {
            $this->result = $capture->captured;
            return;
        }

        // Extract item ID from path "item/start/item/<id>"
        $itemPath = $capture->captured->getOrElse('');
        $itemId   = basename($itemPath);

        // Link the barcode to the new item (runs outside the item txn — auto-commit)
        try {
            $barcodeCode = new BarcodeCode($data->barcodeId);
            $barcode     = $this->barcodes->findByCode($barcodeCode);

            if ($barcode === null) {
                $this->result = Either::left('Barcode not found in pool: ' . $data->barcodeId);
                return;
            }

            $linkedBarcode = $barcode->link($itemId, new \DateTimeImmutable());
            $this->barcodes->save($linkedBarcode);
            $this->result = $capture->captured;
        } catch (\Exception $e) {
            $this->result = Either::left($e->getMessage());
        }
    }

    public function output(): View
    {
        return $this->presenter->complete($this->result);
    }
}
