<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use saso\util\CSRFtoken;

final class BulkImportDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $uploadedFile = $_FILES['csv'] ?? null;
        $hasFile      = $uploadedFile !== null
            && isset($uploadedFile['tmp_name'])
            && is_uploaded_file($uploadedFile['tmp_name'])
            && $uploadedFile['error'] === UPLOAD_ERR_OK;
        $isPost = !empty($post);

        if (!$isPost || !$hasFile) {
            $this->ctrl    = new common\EmptyController();
            $this->usecase = new common\EmptyUsecase(
                new common\EmptyPresenter(new BulkImportView())
            );
            return;
        }

        if (!CSRFtoken::verify($post['csrftoken'] ?? '')) {
            $this->ctrl    = new common\EmptyController();
            $this->usecase = new common\EmptyUsecase(
                new common\EmptyPresenter(new common\FailView())
            );
            return;
        }

        $this->ctrl    = new BulkImportController($uploadedFile['tmp_name'], $now);
        $this->usecase = new BulkImportUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            new BulkImportPresenter(new BulkImportResultView()),
        );
    }
}
