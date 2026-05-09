<?php $this->title = 'ロール管理'; ?>
<?php $this->content = function ($v) {
    $allPermissions = \saso\entity\Role::PERMISSIONS;
?>

<div class="mb-5 flex items-center justify-between gap-3">
  <h2 class="text-lg font-semibold" style="color:var(--saso-text)">ロール管理</h2>
  <a href="./role/add/" class="btn btn-primary btn-sm">
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <span>ロールを追加</span>
  </a>
</div>

<div class="rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold" style="color:var(--saso-text)">カスタムロール一覧</h3>
  </div>

  <div class="overflow-x-auto">
    <table class="ta-table" aria-label="カスタムロール一覧">
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
          <td colspan="4" class="py-8 text-center" style="color:var(--saso-text-sub)">
            ロールが登録されていません。
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($v->roles as $r): ?>
        <tr>
          <td>
            <code class="rounded px-1.5 py-0.5 font-mono text-xs"
                  style="background:rgba(60,80,224,0.08);color:#3c50e0">
              <?php echo htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8'); ?>
            </code>
          </td>
          <td><?php echo htmlspecialchars($r->label, ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <div class="flex flex-wrap gap-1">
              <?php foreach ($allPermissions as $key => $lbl): ?>
                <?php if ($r->hasPermission($key)): ?>
                  <span class="ta-badge ta-badge-primary"><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if (empty($r->permissions)): ?>
                <span class="text-xs" style="color:var(--saso-text-sub)">（なし）</span>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <div class="flex items-center justify-center gap-2">
              <a href="./role/edit/name/<?php echo urlencode($r->name); ?>/"
                 class="btn btn-secondary btn-sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>編集</span>
              </a>
              <?php if (!in_array($r->name, ['admin', 'operator'], true)): ?>
              <form method="post" action="./role/delete/"
                    onsubmit="return confirm('このロールを削除しますか？');">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                        aria-label="<?php echo htmlspecialchars('ロール削除: ' . $r->name, ENT_QUOTES, 'UTF-8'); ?>">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  <span>削除</span>
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
