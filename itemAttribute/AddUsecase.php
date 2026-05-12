<?php
namespace saso\itemAttribute;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\itemAttribute\Insert;
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
    ) {
        $this->output = Either::of(true);
    }

    public function handle(DTO $data): void
    {
        try {
            $this->transaction->begin();

            $data->definition->flatMap(
                fn($def) => $this->updater->exec(
                    new Insert($def),
                    ['now' => (new \DateTime())->format('Y-m-d H:i:s')]
                )
            )->getOrElseThrow('definition data is invalid.');

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
