<?php
namespace saso\label;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\framework\DirectInput;
use saso\framework\GettableController;
use saso\util\monad\Either;

/**
 * @property Either<float> $width
 * @property Either<float> $height
 * @property Either<float> $marginLeft
 * @property Either<float> $marginTop
 * @property Either<float> $intervalColumn
 * @property Either<float> $intervalRow
 */
final class SizeController implements GettableController, DTO
{
    use Getter;
    private Either $width;
    private Either $height;
    private Either $marginLeft;
    private Either $marginTop;
    private Either $intervalColumn;
    private Either $intervalRow;
    public function __construct(
        private array $post,
    )
    {
        $validateLength = fn($name)=>Either::fromNullable(filter_var(
            $this->post[$name]??'',
            \FILTER_VALIDATE_FLOAT,
            [
                'options'=>[
                    'default'=>false,
                    'min_range'=>0,
                    'max_range'=>999.9,
                ]
            ],
        ))
        ->flatMap(fn($v)=>filter_var(
            floor($v*10),
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>false,
                    'min_range'=>0,
                    'max_range'=>9999,
                ]
            ],
        ))
        ->flatMap(fn($v)=>($v/10));
        $this->width = $validateLength('width');
        $this->height = $validateLength('height');
        $this->marginLeft = $validateLength('marginLeft');
        $this->marginTop = $validateLength('marginTop');
        $this->intervalColumn = $validateLength('intervalColumn');
        $this->intervalRow = $validateLength('intervalRow');
    }
}