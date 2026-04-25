<?php
namespace saso\label;

use saso\common\EmptyIO;
use saso\entity\Label;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\GetterAndAnother;
use saso\util\monad\Either;

final class NameController implements Controller, DTO
{
    use DirectInput;
    use GetterAndAnother;
    private Either $name;
    private DTO $another;
    public function __construct(
        array $post,
        ?GettableController $anotherCtrl=null,
    )
    {
        $this->name = Label::nameConstraint($post['labelName']??'');
        $this->another = $anotherCtrl??new EmptyIO();
    }
}