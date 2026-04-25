<?php
namespace saso\category;

use saso\common\EmptyIO;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\GetterAndAnother;
use saso\util\monad\Either;

final class IdController implements Controller, DTO
{
    use DirectInput;
    use GetterAndAnother;
    private Either $id;
    private DTO $another;
    public function __construct(
        private array $request,
        ?GettableController $anotherCtrl=null
    )
    {
        $this->id = Either::fromNullable(filter_var(
            $this->request['id']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>false,
                ],
            ],
        ));
        $this->another = $anotherCtrl??new EmptyIO();
    }
}
