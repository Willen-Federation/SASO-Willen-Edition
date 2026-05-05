<?php
namespace saso\verify;

use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\common\FailView;
use saso\repository\DBConnection;
use Saso\Application\Verification\VerificationService;
use Saso\Domain\Verification\VerificationMode;
use Saso\Infrastructure\Verification\PdoVerificationRepository;

final class CreateDIContainer implements DIContainer
{
    use OnlyPostFlow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view    = new FailView();

        if ($this->notPost) {
            return;
        }

        $modeRaw = strtolower(trim((string) ($post['mode'] ?? '')));
        $mode    = VerificationMode::tryFrom($modeRaw) ?? VerificationMode::Stocktake;
        $area    = trim((string) ($post['areaCode'] ?? '')) ?: null;

        try {
            $pdo     = DBConnection::getPdo();
            $repo    = new PdoVerificationRepository($pdo);
            $service = new VerificationService($repo);
            $service->start($mode, $area, null, null);
        } catch (\Throwable) {
        }

        \saso\util\Redirect::redirect('verify/start/');
        exit;
    }
}
