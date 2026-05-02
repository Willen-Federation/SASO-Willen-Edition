<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use Saso\Infrastructure\Barcode\PdoBarcodeRepository;
use saso\repository\DBConnection;

final class RegisterFromBarcodeDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailView();
        
        $pdo = DBConnection::getPdo();
        $barcodeRepo = new PdoBarcodeRepository($pdo);
        
        $this->ctrl = new RegisterFromBarcodeController($post, $now);
        $this->usecase = new RegisterFromBarcodeUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            $barcodeRepo,
            new common\RedirectOrErrorPresenter(
                new common\RegisterSuccessView(),
                new common\RegisterFailView('item/fromBarcode'),
            ),
        );
    }
}
