<?php
namespace saso\admin;

use saso\common\EmptyController;
use saso\common\EmptyPresenter;
use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;
use Saso\Domain\Feature\FeatureKey;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use saso\repository\DBConnection;

final class FeatureFlagsDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $pdo  = DBConnection::getPdo();
        $repo = new PdoFeatureFlagRepository($pdo);

        if (!empty($post) && isset($query['delete'])) {
            try {
                $flag = $repo->findByKey(new FeatureKey((string) $query['delete']));
                if ($flag !== null) {
                    $repo->delete($flag->id);
                }
            } catch (\Throwable) {}
            \saso\util\Redirect::redirect('admin/feature-flags/');
            exit;
        }

        $view        = new FeatureFlagsView();
        $view->flags = $repo->listAll();

        $this->ctrl    = new EmptyController();
        $this->usecase = new EmptyUsecase(new EmptyPresenter($view));
    }
}
