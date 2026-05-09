<?php $this->title = 'ロール追加'; ?>
<?php $this->content = function ($v) {
    $allPermissions = \saso\entity\Role::PERMISSIONS;
?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">Dashboard</a></li>
    <li aria-hidden="true">/</li>
    <li><a href="./role/start/" class="hover:underline" style="color:var(--saso-text-sub)">ロール管理</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">追加</li>
  </ol>
</nav>

<div class="mx-auto max-w-lg">
  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h3 class="font-semibold" style="color:var(--saso-text)">新しいロールを作成</h3>
    </div>
    <div class="px-5 py-5">
      <form action="./role/add/" method="post" novalidate>
        <?php if (!empty($v->error)): ?>
          <div class="ta-alert ta-alert-danger mb-4" role="alert" aria-live="assertive">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
              <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <?php echo htmlspecialchars($v->error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="mb-4">
          <label for="r-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
            ロール名 <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="r-name" type="text" name="name" class="form-input w-full"
                 placeholder="例: manager（英数字・アンダースコア・ハイフンのみ）"
                 required aria-required="true"
                 pattern="[a-zA-Z0-9_\-]+" maxlength="50">
        </div>

        <div class="mb-4">
          <label for="r-label" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
            表示名 <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="r-label" type="text" name="label" class="form-input w-full"
                 placeholder="例: マネージャー"
                 required aria-required="true" maxlength="100">
        </div>

        <fieldset class="mb-5">
          <legend class="mb-2 text-sm font-medium" style="color:var(--saso-text)">権限</legend>
          <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <?php foreach ($allPermissions as $key => $lbl): ?>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors hover:bg-black/5 dark:hover:bg-white/5"
                   style="border-color:var(--saso-card-bdr);color:var(--saso-text)">
              <input type="checkbox" name="perm_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                     value="1" class="h-4 w-4 rounded border accent-[#3c50e0]">
              <span><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <button type="submit" class="btn btn-primary w-full">作成する</button>
      </form>
    </div>
  </div>
</div>
<?php }; ?>
