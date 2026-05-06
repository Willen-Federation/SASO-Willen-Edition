<?php $this->title = 'ロール管理'; ?>
<?php $this->content = function ($v) {
    $allPermissions = \saso\entity\Role::PERMISSIONS;
?>

<div class="mb-3 d-flex align-items-center justify-content-between">
  <?php ui('button', [
    'label'   => 'ロールを追加',
    'href'    => './role/add/',
    'type'    => 'link',
    'variant' => 'primary',
    'icon'    => '<i class="bi bi-plus me-1"></i>',
  ]); ?>
</div>

<div class="card">
  <div class="card-header">
    <h4 class="card-title">カスタムロール一覧</h4>
  </div>
  <div class="table-responsive">
    <table class="table table-vcenter card-table">
      <thead>
        <tr>
          <th scope="col">ロール名</th>
          <th scope="col">表示名</th>
          <th scope="col">権限</th>
          <th scope="col" class="text-center">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($v->roles)): ?>
        <tr>
          <td colspan="4" class="text-center text-muted py-4">ロールが登録されていません。</td>
        </tr>
        <?php else: ?>
        <?php foreach ($v->roles as $r): ?>
        <tr>
          <td><code class="font-monospace small"><?php echo htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><?php echo htmlspecialchars($r->label, ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <div class="d-flex flex-wrap gap-1">
              <?php foreach ($allPermissions as $key => $lbl): ?>
                <?php if ($r->hasPermission($key)): ?>
                  <span class="badge bg-blue-lt"><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if (empty($r->permissions)): ?>
                <span class="text-muted small">（なし）</span>
              <?php endif; ?>
            </div>
          </td>
          <td class="text-center">
            <div class="d-inline-flex gap-2">
              <a href="./role/edit/name/<?php echo urlencode($r->name); ?>/"
                 class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>編集
              </a>
              <?php if (!in_array($r->name, ['admin', 'operator'], true)): ?>
              <form method="post" action="./role/delete/" class="d-inline m-0"
                    onsubmit="return confirm('このロールを削除しますか？');">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash me-1"></i>削除
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php }; ?>
