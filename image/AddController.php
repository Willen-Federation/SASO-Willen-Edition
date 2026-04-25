<?php
namespace saso\image;

use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class AddController implements GettableController, DTO
{
    use Getter;
    private string $fileName;
    private Either $imageType;
    public function __construct(
        array $image,
    )
    {
        $this->fileName = $image['tmp_name'];
        $this->imageType = Either::of($image['type'])->filter(
            fn($v)=>in_array($v, [
                'image/gif',
                'image/jpeg',
                'image/png',
            ])
        );
    }
}