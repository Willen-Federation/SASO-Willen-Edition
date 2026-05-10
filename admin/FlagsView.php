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
<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active" aria-current="page">機能フラグ管理</li>
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

<div class="card mb-4">
  <div class="card-header fw-bold">機能フラグ一覧</div>
  <div class="card-body p-0">
    <?php if (empty($flags)): ?>
    <p class="text-muted p-3 mb-0">登録されている機能フラグはありません。</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0" aria-label="機能フラグ一覧">
      <thead class="table-dark">
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
            <form method="post" action="./admin/flags/" class="d-inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo $flag->id; ?>">
              <button type="submit"
                class="btn btn-sm <?php echo $flag->enabled ? 'btn-success' : 'btn-secondary'; ?>"
                title="クリックで<?php echo $flag->enabled ? '無効' : '有効'; ?>化">
                <?php echo $flag->enabled ? '有効' : '無効'; ?>
              </button>
            </form>
          </td>
          <td><?php echo $flag->rolloutPercent; ?>%</td>
          <td>
            <?php if ($flag->errorThreshold === 0): ?>
              <span class="text-muted small">なし</span>
            <?php else: ?>
              <span class="small"><?php echo $flag->errorThreshold; ?>回 / <?php echo $flag->errorWindowMinutes; ?>分</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($flag->autoDisabledAt !== null): ?>
              <span class="badge bg-warning text-dark" title="<?php echo $h($flag->autoDisableReason ?? ''); ?>">
                <?php echo $h($flag->autoDisabledAt->format('Y-m-d H:i')); ?>
              </span>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?php echo $h($flag->updatedAt->format('Y-m-d H:i')); ?></td>
          <td>
            <form method="post" action="./admin/flags/" class="d-inline"
              onsubmit="return confirm('フラグ「<?php echo $h($flag->key->value); ?>」を削除しますか？この操作は取り消せません。')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $flag->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
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

<div class="card">
  <div class="card-header fw-bold">新規フラグ追加</div>
  <div class="card-body">
    <form method="post" action="./admin/flags/" novalidate>
      <input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="flag_key" class="form-label">キー名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="flag_key" name="key_name"
            placeholder="例: feature.new_checkout" pattern="[a-z0-9._]+"
            required aria-describedby="flag_key_help">
          <div id="flag_key_help" class="form-text">小文字英数字・<code>.</code>・<code>_</code> のみ</div>
        </div>
        <div class="col-md-5">
          <label for="flag_desc" class="form-label">説明 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="flag_desc" name="description"
            placeholder="例: 新チェックアウト画面の有効化" required>
        </div>
        <div class="col-md-3">
          <label for="flag_rollout" class="form-label">ロールアウト割合 (%)</label>
          <input type="number" class="form-control" id="flag_rollout" name="rollout_percent"
            value="100" min="0" max="100">
        </div>
        <div class="col-md-3">
          <label for="flag_threshold" class="form-label">エラー閾値 (0=無効)</label>
          <input type="number" class="form-control" id="flag_threshold" name="error_threshold"
            value="0" min="0">
        </div>
        <div class="col-md-3">
          <label for="flag_window" class="form-label">CB ウィンドウ (分)</label>
          <input type="number" class="form-control" id="flag_window" name="error_window_min"
            value="5" min="1">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="flag_enabled" name="enabled" checked>
            <label class="form-check-label" for="flag_enabled">作成時に有効化</label>
          </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">フラグを作成</button>
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
