<?php
namespace saso\installer;

use saso\entity\Member;
use saso\framework\Setter;
use saso\framework\View;
use saso\util;

final class InstallView implements View
{
    use Setter;
    private Member $member;
    public function display(): void
    {
        require_once 'installer/createTables.php';
        util\Redirect::redirect('installer/installed');
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return fn()=>null;
    }
}

