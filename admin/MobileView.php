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
<?php if ($flashMsg !== null): ?>
<div class="ta-alert ta-alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?> mb-4"
     x-data="{ open: true }" x-show="open" role="alert">
  <div class="flex-1"><?php echo $flashMsg; ?></div>
  <button type="button" @click="open = false" class="ml-auto shrink-0 p-1 rounded hover:bg-black/10 dark:hover:bg-white/10" aria-label="閉じる">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
  </button>
</div>
<?php endif; ?>

<div class="rounded-2xl border shadow-sm mb-4 overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold text-base" style="color:var(--saso-text)">ペアリング済みデバイス一覧</h3>
    <span class="ta-badge ta-badge-secondary"><?php echo count($tokens); ?> 件</span>
  </div>
  <div>
    <?php if (empty($tokens)): ?>
    <p class="text-sm p-4" style="color:var(--saso-text-sub)">ペアリング済みのデバイスはありません。</p>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="ta-table" aria-label="デバイス一覧">
      <thead>
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
            $rowBg    = $token->revoked ? 'bg-gray-50 dark:bg-gray-800/50' : ($expired ? 'bg-amber-50 dark:bg-amber-900/20' : '');
        ?>
        <tr class="<?php echo $rowBg; ?>">
          <td><?php echo $h($token->deviceName); ?></td>
          <td>
            <?php if ($token->revoked): ?>
              <span class="ta-badge ta-badge-secondary">失効済み</span>
            <?php elseif ($expired): ?>
              <span class="ta-badge ta-badge-warning">期限切れ</span>
            <?php else: ?>
              <span class="ta-badge ta-badge-primary">有効</span>
            <?php endif; ?>
          </td>
          <td class="text-sm" style="color:var(--saso-text-sub)">
            <?php echo $token->lastUsedAt !== null
                ? $h($token->lastUsedAt->format('Y-m-d H:i'))
                : '<span style="color:var(--saso-text-sub)">未使用</span>'; ?>
          </td>
          <td class="text-sm <?php echo $expired ? 'text-error-500 font-semibold' : ''; ?>" style="<?php echo $expired ? '' : 'color:var(--saso-text-sub)'; ?>">
            <?php echo $h($token->expiresAt->format('Y-m-d H:i')); ?>
          </td>
          <td class="text-sm" style="color:var(--saso-text-sub)">
            <?php echo $h($token->createdAt->format('Y-m-d H:i')); ?>
          </td>
          <td>
            <?php if ($active): ?>
            <form method="post" action="./admin/mobile/" style="display:inline"
              onsubmit="return confirm('デバイス「<?php echo $h($token->deviceName); ?>」のトークンを失効させますか？\nこのデバイスは再ペアリングが必要になります。')">
              <input type="hidden" name="action" value="revoke">
              <input type="hidden" name="id" value="<?php echo $token->id; ?>">
              <button type="submit" class="btn btn-danger btn-sm">失効</button>
            </form>
            <?php else: ?>
              <span class="text-sm" style="color:var(--saso-text-sub)">—</span>
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

<div class="rounded-2xl border shadow-sm" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="px-6 py-5">
    <p class="font-semibold text-sm mb-2" style="color:var(--saso-text)">モバイルペアリングについて</p>
    <p class="text-xs" style="color:var(--saso-text-sub)">
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
