<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;

final class FlagsView implements View
{
    use Setter;

    private string $title = '';
    private \Closure $content;

    public function display(): void
    {
        $pdo  = \saso\repository\DBConnection::getPdo();
        $repo = new \Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository(
            $pdo,
            new \DateTimeZone('Asia/Tokyo'),
        );

        $flashMsg  = null;
        $flashType = 'success';

        $action = empty($_POST) ? null : ($_POST['action'] ?? null);

        switch ($action) {
            case 'toggle':
                $id   = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
                $flag = ($id !== false) ? $repo->findById((int) $id) : null;
                if ($flag !== null) {
                    $repo->save($flag->withEnabled(!$flag->enabled));
                    $flashMsg = $flag->enabled ? 'フラグを無効にしました。' : 'フラグを有効にしました。';
                }
                break;

            case 'delete':
                $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
                if ($id !== false) {
                    $repo->delete((int) $id);
                    $flashMsg = 'フラグを削除しました。';
                }
                break;

            case 'create':
                $key         = trim($_POST['key_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $rollout     = max(0, min(100, (int) ($_POST['rollout_percent'] ?? 100)));
                $threshold   = max(0, (int) ($_POST['error_threshold'] ?? 0));
                $window      = max(1, (int) ($_POST['error_window_min'] ?? 5));
                $enabled     = !empty($_POST['enabled']);
                $createErrors = [];

                if ($key === '') {
                    $createErrors[] = 'キー名を入力してください。';
                } elseif (preg_match('/^[a-z0-9._]+$/', $key) !== 1) {
                    $createErrors[] = 'キー名は小文字英数字・ピリオド・アンダースコアのみ使用できます。';
                }
                if ($description === '') {
                    $createErrors[] = '説明を入力してください。';
                }

                if (empty($createErrors)) {
                    $nextIdStmt = $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM feature_flag');
                    $nextId     = (int) ($nextIdStmt ? $nextIdStmt->fetchColumn() : 1);
                    $now        = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Tokyo'));
                    try {
                        $repo->save(new \Saso\Domain\Feature\FeatureFlag(
                            id: $nextId,
                            key: new \Saso\Domain\Feature\FeatureKey($key),
                            description: $description,
                            enabled: $enabled,
                            rolloutPercent: $rollout,
                            conditions: null,
                            errorThreshold: $threshold,
                            errorWindowMinutes: $window,
                            autoDisabledAt: null,
                            autoDisableReason: null,
                            createdAt: $now,
                            updatedAt: $now,
                        ));
                        $flashMsg = '機能フラグを作成しました。';
                    } catch (\InvalidArgumentException $e) {
                        $flashMsg  = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                        $flashType = 'danger';
                    }
                } else {
                    $flashMsg  = implode(' ', array_map(
                        fn ($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'),
                        $createErrors,
                    ));
                    $flashType = 'danger';
                }
                break;
        }

        $flags = $repo->listAll();

        $this->title   = '機能フラグ管理';
        $this->content = function ($v) use ($flags, $flashMsg, $flashType): void {
            $h = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            ?>
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <li class="breadcrumb-item active" aria-current="page">機能フラグ管理</li>
  </ol>
</nav>

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
  <div class="px-6 py-4 border-b font-semibold text-base" style="color:var(--saso-text);border-color:var(--saso-card-bdr)">機能フラグ一覧</div>
  <div>
    <?php if (empty($flags)): ?>
    <p class="text-sm p-4" style="color:var(--saso-text-sub)">登録されている機能フラグはありません。</p>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="ta-table" aria-label="機能フラグ一覧">
      <thead>
        <tr>
          <th scope="col">キー</th>
          <th scope="col">説明</th>
          <th scope="col">状態</th>
          <th scope="col">ロールアウト</th>
          <th scope="col">CB設定</th>
          <th scope="col">自動無効</th>
          <th scope="col">更新日時</th>
          <th scope="col">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($flags as $flag): ?>
        <tr>
          <td><code><?php echo $h($flag->key->value); ?></code></td>
          <td><?php echo $h($flag->description); ?></td>
          <td>
            <form method="post" action="./admin/flags/" style="display:inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo $flag->id; ?>">
              <button type="submit"
                class="btn btn-sm <?php echo $flag->enabled ? 'btn-primary' : 'btn-secondary'; ?>"
                title="クリックで<?php echo $flag->enabled ? '無効' : '有効'; ?>化">
                <?php echo $flag->enabled ? '有効' : '無効'; ?>
              </button>
            </form>
          </td>
          <td><?php echo $flag->rolloutPercent; ?>%</td>
          <td>
            <?php if ($flag->errorThreshold === 0): ?>
              <span class="text-xs" style="color:var(--saso-text-sub)">なし</span>
            <?php else: ?>
              <span class="text-sm"><?php echo $flag->errorThreshold; ?>回 / <?php echo $flag->errorWindowMinutes; ?>分</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($flag->autoDisabledAt !== null): ?>
              <span class="ta-badge ta-badge-warning" title="<?php echo $h($flag->autoDisableReason ?? ''); ?>">
                <?php echo $h($flag->autoDisabledAt->format('Y-m-d H:i')); ?>
              </span>
            <?php else: ?>
              <span class="text-xs" style="color:var(--saso-text-sub)">—</span>
            <?php endif; ?>
          </td>
          <td class="text-sm" style="color:var(--saso-text-sub)"><?php echo $h($flag->updatedAt->format('Y-m-d H:i')); ?></td>
          <td>
            <form method="post" action="./admin/flags/" style="display:inline"
              onsubmit="return confirm('フラグ「<?php echo $h($flag->key->value); ?>」を削除しますか？この操作は取り消せません。')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $flag->id; ?>">
              <button type="submit" class="btn btn-danger btn-sm">削除</button>
            </form>
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
  <div class="px-6 py-4 border-b font-semibold text-base" style="color:var(--saso-text);border-color:var(--saso-card-bdr)">新規フラグ追加</div>
  <div class="px-6 py-5">
    <form method="post" action="./admin/flags/" novalidate>
      <input type="hidden" name="action" value="create">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label for="flag_key" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">キー名 <span class="text-error-500">*</span></label>
          <input type="text" class="form-input w-full" id="flag_key" name="key_name"
            placeholder="例: feature.new_checkout" pattern="[a-z0-9._]+"
            required aria-describedby="flag_key_help">
          <p id="flag_key_help" class="mt-1 text-xs" style="color:var(--saso-text-sub)">小文字英数字・<code>.</code>・<code>_</code> のみ</p>
        </div>
        <div class="sm:col-span-2 lg:col-span-1">
          <label for="flag_desc" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">説明 <span class="text-error-500">*</span></label>
          <input type="text" class="form-input w-full" id="flag_desc" name="description"
            placeholder="例: 新チェックアウト画面の有効化" required>
        </div>
        <div>
          <label for="flag_rollout" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">ロールアウト割合 (%)</label>
          <input type="number" class="form-input w-full" id="flag_rollout" name="rollout_percent"
            value="100" min="0" max="100">
        </div>
        <div>
          <label for="flag_threshold" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">エラー閾値 (0=無効)</label>
          <input type="number" class="form-input w-full" id="flag_threshold" name="error_threshold"
            value="0" min="0">
        </div>
        <div>
          <label for="flag_window" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">CB ウィンドウ (分)</label>
          <input type="number" class="form-input w-full" id="flag_window" name="error_window_min"
            value="5" min="1">
        </div>
        <div class="flex items-end gap-4 sm:col-span-2 lg:col-span-3">
          <label class="flex items-center gap-2 text-sm cursor-pointer" style="color:var(--saso-text)">
            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" id="flag_enabled" name="enabled" checked>
            作成時に有効化
          </label>
          <button type="submit" class="btn btn-primary">フラグを作成</button>
        </div>
      </div>
    </form>
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
