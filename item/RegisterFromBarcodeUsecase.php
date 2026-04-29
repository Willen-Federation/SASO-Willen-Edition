<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Usecase;
use saso\repository\Finder;
use saso\repository\Updater;
use saso\repository\TransactionInterface;
use saso\framework\Presenter;
use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use saso\util\monad\Either;

final class RegisterFromBarcodeUsecase implements Usecase
{
    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private BarcodeRepository $barcodes,
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        /** @var RegisterFromBarcodeController $data */
        
        try {
            $this->transaction->begin();
            
            // 1. Create the item using existing RegisterUsecase logic
            // We'll use a local presenter to capture the result
            $capturePresenter = new class implements Presenter {
                public $result;
                public function present(mixed $data): void { $this->result = $data; }
            };
            
            $registerUsecase = new RegisterUsecase(
                $this->finder,
                $this->updater,
                $this->transaction,
                $capturePresenter
            );
            
            $registerUsecase->handle($data->registerData);
            
            // Check if registration was successful
            $itemPath = $capturePresenter->result;
            if ($itemPath instanceof Either && $itemPath->isLeft()) {
                throw new \Exception($itemPath->getLeft());
            }
            
            // The itemPath is something like "item/start/item/2604290001"
            $itemId = basename($itemPath);
            
            // 2. Link the barcode to this item
            $barcodeCode = new BarcodeCode($data->barcodeId);
            $barcode = $this->barcodes->findByCode($barcodeCode);
            
            if ($barcode === null) {
                throw new \Exception("Barcode not found in pool: " . $data->barcodeId);
            }
            
            $linkedBarcode = $barcode->link($itemId, new \DateTimeImmutable());
            $this->barcodes->save($linkedBarcode);
            
            $this->transaction->commit();
            
            // 3. Success redirect
            $this->presenter->present($itemPath);
            
        } catch (\Exception $e) {
            if ($this->transaction->inTransaction()) {
                $this->transaction->rollBack();
            }
            $this->presenter->present(Either::left($e->getMessage()));
        }
    }
}
