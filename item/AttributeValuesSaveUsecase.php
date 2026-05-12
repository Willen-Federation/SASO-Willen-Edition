<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\item\FindOneById;
use saso\repository\itemAttribute\FindAll;
use saso\repository\itemAttribute\UpsertValue;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\Each;
use saso\util\monad\Either;

final class AttributeValuesSaveUsecase implements Usecase
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

            $itemId = $data->id->getOrElseThrow('item id is required.');
            $attrValues = $data->attrValues->getOrElse([]);

            $definitions = $this->finder->generate(new FindAll())->flatMap(
                fn($gen) => iterator_to_array($gen)
            )->getOrElse([]);

            $now = (new \DateTime())->format('Y-m-d H:i:s');

            foreach ($definitions as $def) {
                $code = $def['code'];
                if (!array_key_exists($code, $attrValues)) {
                    continue;
                }

                $rawValue = $attrValues[$code];
                $valueString = null;
                $valueInt    = null;
                $valueFloat  = null;
                $valueBool   = null;

                switch ($def['value_type']) {
                    case 'int':
                        $valueInt = $rawValue !== '' ? (int) $rawValue : null;
                        break;
                    case 'float':
                        $valueFloat = $rawValue !== '' ? (float) $rawValue : null;
                        break;
                    case 'bool':
                        $valueBool = $rawValue !== '' ? (bool) $rawValue : null;
                        break;
                    default:
                        $valueString = $rawValue !== '' ? (string) $rawValue : null;
                        break;
                }

                $this->updater->exec(new UpsertValue(), [
                    'item_id'        => (int) $itemId,
                    'attribute_code' => $code,
                    'value_string'   => $valueString,
                    'value_int'      => $valueInt,
                    'value_float'    => $valueFloat,
                    'value_bool'     => $valueBool,
                    'now'            => $now,
                ]);
            }

            $this->transaction->commit();
            $this->output = Either::of('item/edit/item/' . $itemId);
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
