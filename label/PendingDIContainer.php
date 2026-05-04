<?php
namespace saso\label;

use saso\common\EmptyController;
use saso\framework\DIContainer;
use saso\framework\Flow;
use Saso\Infrastructure\Barcode\PdoBarcodeRepository;
use saso\repository\DBConnection;

final class PendingDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $pdo  = DBConnection::getPdo();
        $view = new PendingView();

        $this->ctrl    = new EmptyController();
        $this->usecase = new PendingUsecase(
            new PdoBarcodeRepository($pdo),
            $view,
        );
    }
}
