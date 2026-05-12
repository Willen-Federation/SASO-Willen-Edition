<?php
namespace saso\itemAttribute;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\itemAttribute\Delete;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class DeleteUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;

    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private Presenter $presenter,
    ) {
        $this->output = Either::of(true);
    }

    public function handle(DTO $data): void
    {
        try {
            $this->transaction->begin();

            $data->defId->flatMap(
                fn($id) => $this->updater->exec(new Delete(), ['id' => $id])
            )->getOrElseThrow('id is required.');

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
