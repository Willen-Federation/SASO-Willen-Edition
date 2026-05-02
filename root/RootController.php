<?php
namespace saso\root;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class RootController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct(
        array $config,
        bool $authed,
        string $matter,
        string $action
    )
    {
        $protocol = filter_var($config['https']??'', \FILTER_VALIDATE_BOOL, [
            'options'=>[
                'default'=>false,
            ],
        ]);
        $programDir = filter_var($config['programDir']??'', \FILTER_VALIDATE_DOMAIN, [
            'options'=>[
                'default'=>'',
            ],
        ]);
        $version = filter_var($config['version']??'', \FILTER_VALIDATE_REGEXP, [
            'options'=>[
                'default'=>'',
                'regexp'=>'/^(\d){1,3}\.?(\d){0,3}\.?(\d){0,3}$/',
            ],
        ]);
        $this->data = new RootInput(
            $protocol,
            $programDir,
            $version,
            $authed,
            $matter,
            $action,
        );
    }
}