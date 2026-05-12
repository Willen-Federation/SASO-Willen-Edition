<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\item\FindOneById;
use saso\repository\itemAttribute\FindAllWithValues;
use saso\repository\Finder;

final class AttributeValuesUsecase implements Usecase
{
    use Output;
    private DTO $output;

    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        $item = $data->id->flatMap(
            fn($v) => $this->finder->current(new FindOneById(), ['id' => $v])
        );

        $attributes = $data->id->flatMap(
            fn($v) => $this->finder->generate(
                new FindAllWithValues(),
                ['item_id' => (int) $v]
            )
        )->flatMap(
            fn($gen) => iterator_to_array($gen)
        );

        $this->output = new AttributeValuesOutput($item, $attributes);
    }
}
