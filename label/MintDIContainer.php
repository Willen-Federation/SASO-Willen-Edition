<?php
namespace saso\label;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use Saso\Infrastructure\Barcode\PdoBarcodeRepository;
use saso\repository\DBConnection;
use saso\repository\DbFinder;

final class MintDIContainer implements DIContainer
{
    use OnlyPostFlow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view    = new common\FailView();

        $pdo  = DBConnection::getPdo();
        $view = new MintView();

        $this->ctrl    = new MintController($post);
        $this->usecase = new MintUsecase(
            new PdoBarcodeRepository($pdo),
            new DbFinder(),
            $view,
        );
    }
}
