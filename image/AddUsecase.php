<?php
namespace saso\image;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\color\FindOneByCodeAndItem;
use saso\repository\color\UploadImage;
use saso\repository\item\FindOneById;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class AddUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        try {
            $this->transaction->begin();

            // M1: $data->fileName / $data->imageType are Either values produced
            // by UploadValidator. Extracting them here means a failed upload
            // short-circuits the whole transaction via the catch below, so the
            // file content never reaches the DB.
            $fileName  = $data->fileName->getOrElseThrow('invalid image upload.');
            $imageType = $data->imageType->getOrElseThrow('invalid image upload.');

            $color = $this->finder->current(new FindOneById(), [
                'id'=>$data->id->getOrElseThrow('invalid input.')
            ])->flatMap(
                fn($v)=>$this->finder->current(new FindOneByCodeAndItem($v), [
                    'code'=>$data->color->getOrElseThrow('invalid input.')
                ])
            );
            $color->flatMap(
                fn($v)=>$this->updater->exec(new UploadImage($v), [
                    'fileName'=>$fileName,
                    'imageType'=>$imageType,
                ])
            );
            $this->output = $color->flatMap(
                fn($v)=>'image/start/item/'. $v->item->id.'/color/'.$v->code
            )->orElse(
                fn($v)=>throw new \Exception('item or color not found.')
            );

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
