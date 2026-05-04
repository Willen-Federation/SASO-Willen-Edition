<?php
namespace saso\barcode;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use Saso\Infrastructure\Barcode\PdoBarcodeRepository;
use saso\repository\DBConnection;

final class PrintSheetDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailView();
        
        $pdo = DBConnection::getPdo();
        $barcodeRepo = new PdoBarcodeRepository($pdo);
        
        $this->ctrl = new PrintSheetController($post);
        $this->usecase = new PrintSheetUsecase(
            $barcodeRepo,
            new PrintSheetView($inside)
        );
    }
}
