<?php
namespace saso\image;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive\FindOneByItem;
use saso\repository\color\FindOneWithImageTypeByCodeAndItem;
use saso\repository\item\FindOneById;
use saso\repository\Finder;

final class StartUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $color = $data->id->flatMap(
            fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
        )->flatMap(
            fn($v)=>$data->color->flatMap(
            fn($c)=>$this->finder->current(new FindOneWithImageTypeByCodeAndItem($v), [
                'code'=>$c
            ])
        ));
        $archive = $color->flatMap(
            fn($v)=>$this->finder->current(
                new FindOneByItem($v->item)
            )
        );
        $this->output = new StartOutput(
            $color,
            $archive,
        );
    }
}
