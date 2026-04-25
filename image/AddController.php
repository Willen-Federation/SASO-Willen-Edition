<?php
namespace saso\image;

use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;
use saso\util\UploadValidator;

final class AddController implements GettableController, DTO
{
    use Getter;
    private const ALLOWED_MIMES = [
        'image/gif',
        'image/jpeg',
        'image/png',
    ];
    private const MAX_BYTES = 5 * 1024 * 1024;

    private Either $upload;
    private Either $fileName;
    private Either $imageType;
    public function __construct(
        array $image,
    )
    {
        // Validate the uploaded file by its actual bytes — never by the
        // client-supplied $image['type']. The validator returns an Either
        // carrying {tmp_name, mimeType, size, extension} on success or a
        // descriptive failure message that the usecase surfaces to the user.
        $this->upload = UploadValidator::validateImageUpload(
            $image,
            self::ALLOWED_MIMES,
            self::MAX_BYTES,
        );
        $this->fileName  = $this->upload->map(fn($v) => (string)$v['tmp_name']);
        $this->imageType = $this->upload->map(fn($v) => (string)$v['mimeType']);
    }
}
