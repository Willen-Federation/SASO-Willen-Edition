<?php
namespace saso\root;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;

final class RootUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $protocol = $data->protocol?'https://':'http://';
        $programDir = trim($data->programDir, '/');
        $path = '/'.(empty($programDir)?'':($programDir.'/'));
        $this->output = new RootOutput(
            $protocol.$_SERVER['HTTP_HOST'].$path,
            $data->version,
            $data->authed,
            $data->matter,
            $data->action,
            $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'),
            ['en', 'ja'],
        );
    }
}
