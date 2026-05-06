<?php
namespace saso\installer;

use saso\entity\Member;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class InstallController implements Controller, DTO
{
    use DirectInput;
    use Getter;
    private Either $id;
    private Either $name;
    private Either $password;
    private Either $dbHost;
    private Either $dbPort;
    private Either $dbName;
    private Either $dbUser;
    private Either $dbPassword;
    private Either $dbCharset;
    private Either $httpsEnabled;
    public function __construct(
        array $post,
    )
    {
        $this->id = Either::fromNullable(filter_var(
            $post['id']??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9a-zA-Z-_]{8,20}$/'
                ]
            ]
        ));
        $this->name = Member::nameConstraint($post['name']??'');
        $this->password = Either::fromNullable(filter_var(
            $post['password']??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9a-zA-Z]{8,20}$/'
                ]
            ]
        ))->filter(
            fn($v)=>$v===$post['password_confirm']
        );

        $this->dbHost = Either::fromNullable(filter_var(
            $post['db_host']??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[a-zA-Z0-9.\-_]+$/'
                ]
            ]
        ));

        $rawPort = $post['db_port']??'';
        if ($rawPort === '' || $rawPort === null) {
            $this->dbPort = Either::right('');
        } else {
            $port = filter_var($rawPort, \FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>65535]]);
            $this->dbPort = Either::fromNullable($port !== false ? (string)$port : false);
        }

        $this->dbName = Either::fromNullable(filter_var(
            $post['db_name']??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[a-zA-Z0-9_\-]+$/'
                ]
            ]
        ));

        $dbUser = $post['db_user']??'';
        $this->dbUser = Either::fromNullable($dbUser !== '' ? $dbUser : false);

        $this->dbPassword = Either::right($post['db_password']??'');

        $charset = $post['db_charset']??'utf8mb4';
        $this->dbCharset = Either::fromNullable(
            in_array($charset, ['utf8mb4', 'utf8'], true) ? $charset : false
        );

        $this->httpsEnabled = Either::right(!empty($post['https_enabled']));
    }
}
