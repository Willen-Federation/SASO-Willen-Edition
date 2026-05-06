<?php
namespace saso\featureAdmin;

use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;
use Saso\Domain\Feature\FeatureKey;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use saso\repository\DBConnection;

final class ListDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        if (!empty($post) && isset($post['flag_key'])) {
            try {
                $pdo    = DBConnection::getPdo();
                $repo   = new PdoFeatureFlagRepository($pdo);
                $action = (string) ($post['action'] ?? '');
                $flag   = $repo->findByKey(new FeatureKey((string) $post['flag_key']));
                if ($flag !== null) {
                    $repo->save($flag->withEnabled($action === 'enable'));
                }
            } catch (\Throwable) {}
            \saso\util\Redirect::redirect('featureAdmin/list/');
            exit;
        }

        $this->ctrl    = new ListController();
        $this->usecase = new EmptyUsecase(
            new ListPresenter(
                new ListView(),
            ),
        );
    }
}
