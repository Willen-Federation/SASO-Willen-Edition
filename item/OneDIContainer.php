<?php
namespace saso\item;

use saso\common;
use saso\feature\FeatureController;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class OneDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        // 商品コード未指定でキーワード検索のみ行われた場合は、
        // 専用の検索結果ページ（search/start）に転送する。
        // 例: /item/start/search/テスト → /search/start/search/テスト/
        if (empty($query['item']) && !empty($query['search'])) {
            $term = (string)$query['search'];
            $programDir = trim((string)($config['programDir'] ?? ''), '/');
            $base = '/' . ($programDir !== '' ? $programDir . '/' : '');
            header('Location: ' . $base . 'search/start/search/' . rawurlencode($term) . '/', true, 302);
            exit;
        }
        $this->ctrl = new FeatureController($query, new OneController($query, $config));
        $this->usecase = new OneUsecase(
            new DbFinder(),
            new OnePresenter(
                new OneView($inside),
                new common\FailView(),
            )
        );
    }
}
