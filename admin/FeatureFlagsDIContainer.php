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

        if (!empty($post) && isset($query['toggle'])) {
            try {
                $flag = $repo->findByKey(new FeatureKey((string) $query['toggle']));
                if ($flag !== null) {
                    $repo->save($flag->withEnabled(!empty($post['enabled'])));
                }
            } catch (\Throwable) {}
            \saso\util\Redirect::redirect('admin/feature-flags/');
            exit;
        }

        if (!empty($post) && !isset($query['delete']) && !isset($query['toggle'])) {
            $key  = preg_replace('/[^a-z0-9_.]/', '', strtolower(trim((string) ($post['key'] ?? ''))));
            $desc = trim((string) ($post['description'] ?? '')) ?: '-';
            if ($key !== '') {
                try {
                    $nowStr = $now->format('Y-m-d H:i:s');
                    $stmt = $pdo->prepare(
                        'INSERT INTO feature_flag
                            (key_name, description, enabled, rollout_percent, error_threshold, error_window_min, created_at, updated_at)
                         VALUES (:key, :desc, :enabled, 0, 0, 60, :ca, :ua)',
                    );
                    $stmt->execute([
                        'key'     => $key,
                        'desc'    => $desc,
                        'enabled' => !empty($post['enabled']) ? 1 : 0,
                        'ca'      => $nowStr,
                        'ua'      => $nowStr,
                    ]);
                } catch (\Throwable) {}
            }
            \saso\util\Redirect::redirect('admin/feature-flags/');
            exit;
        }

        $view        = new FeatureFlagsView();
        $view->flags = $repo->listAll();

        $this->ctrl    = new EmptyController();
        $this->usecase = new EmptyUsecase(new EmptyPresenter($view));
    }
}
