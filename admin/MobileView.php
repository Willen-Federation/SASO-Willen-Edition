<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;

final class MobileView implements View
{
    use Setter;

    private string $title = '';
    private \Closure $content;

    public function display(): void
    {
        $pdo  = \saso\repository\DBConnection::getPdo();
        $repo = new PdoDeviceTokenRepository($pdo, new \DateTimeZone('Asia/Tokyo'));

        $flashMsg  = null;
        $flashType = 'success';

        if (!empty($_POST)) {
            $action = $_POST['action'] ?? null;

            if ($action === 'revoke') {
                $id    = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
                $token = ($id !== false) ? $repo->findById((int) $id) : null;
                if ($token !== null && !$token->revoked) {
                    $repo->save($token->revoke());
                    $flashMsg = 'デバイストークンを失効させました。';
                }
            }
        }

        $tokens = $repo->listAll();
        $now    = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Tokyo'));

        $this->title   = 'モバイルデバイス管理';
        $this->content = function ($v) use ($tokens, $flashMsg, $flashType, $now): void {
            $h = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            ?>
<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active" aria-current="page">モバイルデバイス管理</li>
</ol>
</nav>

<?php if ($flashMsg !== null): ?>
<div class="alert alert-<?php echo $flashType; ?> fade show mb-4" role="alert" x-data="{ show: true }" x-show="show">
  <div class="flex items-start justify-between gap-3">
    <span><?php echo $flashMsg; ?></span>
    <button type="button" class="btn-close" @click="show = false" aria-label="閉じる"></button>
  </div>
</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header fw-bold d-flex justify-content-between align-items-center">
    <span>ペアリング済みデバイス一覧</span>
    <span class="badge bg-secondary"><?php echo count($tokens); ?> 件</span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($tokens)): ?>
    <p class="text-muted p-3 mb-0">ペアリング済みのデバイスはありません。</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0" aria-label="デバイス一覧">
      <thead class="table-dark">
        <tr>
          <th scope="col">デバイス名</th>
          <th scope="col">状態</th>
          <th scope="col">最終利用日時</th>
          <th scope="col">有効期限</th>
          <th scope="col">登録日時</th>
          <th scope="col">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tokens as $token): ?>
        <?php
            $expired  = $token->isExpired($now);
            $active   = !$token->revoked && !$expired;
            $rowClass = $token->revoked ? 'table-secondary' : ($expired ? 'table-warning' : '');
        ?>
        <tr class="<?php echo $rowClass; ?>">
          <td><?php echo $h($token->deviceName); ?></td>
          <td>
            <?php if ($token->revoked): ?>
              <span class="badge bg-secondary">失効済み</span>
            <?php elseif ($expired): ?>
              <span class="badge bg-warning text-dark">期限切れ</span>
            <?php else: ?>
              <span class="badge bg-success">有効</span>
            <?php endif; ?>
          </td>
          <td class="small">
            <?php echo $token->lastUsedAt !== null
                ? $h($token->lastUsedAt->format('Y-m-d H:i'))
                : '<span class="text-muted">未使用</span>'; ?>
          </td>
          <td class="small <?php echo $expired ? 'text-danger fw-bold' : ''; ?>">
            <?php echo $h($token->expiresAt->format('Y-m-d H:i')); ?>
          </td>
          <td class="small text-muted">
            <?php echo $h($token->createdAt->format('Y-m-d H:i')); ?>
          </td>
          <td>
            <?php if ($active): ?>
            <form method="post" action="./admin/mobile/" class="d-inline"
              onsubmit="return confirm('デバイス「<?php echo $h($token->deviceName); ?>」のトークンを失効させますか？\nこのデバイスは再ペアリングが必要になります。')">
              <input type="hidden" name="action" value="revoke">
              <input type="hidden" name="id" value="<?php echo $token->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">失効</button>
            </form>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h6 class="card-title">モバイルペアリングについて</h6>
    <p class="card-text small text-muted mb-0">
      新しいデバイスのペアリングは、モバイルアプリから QR コードをスキャンして行います。
      ペアリングコードは API エンドポイント <code>POST /api/v1/mobile/pair/qr</code> で発行されます。
      デバイストークンの有効期限は 365 日です。失効したデバイスは再度 QR ペアリングが必要です。
    </p>
  </div>
</div>
            <?php
        };
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
