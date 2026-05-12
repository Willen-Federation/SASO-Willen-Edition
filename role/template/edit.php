<?php $this->title = 'ロール編集'; ?>
<?php $this->content = function ($v) {
    $allPermissions = \saso\entity\Role::PERMISSIONS;
    $isBuiltin = in_array($v->role->name, ['admin', 'operator'], true);
?>


<div class="mx-auto max-w-lg">
  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h3 class="font-semibold" style="color:var(--saso-text)">
        ロール編集:
        <code class="ml-1 rounded px-1.5 py-0.5 font-mono text-xs"
              style="background:rgba(60,80,224,0.08);color:#3c50e0">
          <?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?>
        </code>
      </h3>
    </div>
    <div class="px-5 py-5">
      <form action="./role/edit/" method="post" novalidate>
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?>">

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
          <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">ロール名</label>
          <input type="text" value="<?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?>"
                 class="form-input w-full opacity-60" disabled aria-readonly="true">
        </div>

        <div class="mb-4">
          <label for="r-label" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
            表示名 <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="r-label" type="text" name="label" class="form-input w-full"
                 value="<?php echo htmlspecialchars($v->role->label, ENT_QUOTES, 'UTF-8'); ?>"
                 required aria-required="true" maxlength="100">
        </div>

        <fieldset class="mb-5">
          <legend class="mb-2 text-sm font-medium" style="color:var(--saso-text)">権限</legend>
          <?php if ($isBuiltin): ?>
            <div class="ta-alert ta-alert-info mb-3" role="note">
              <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                <path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              組み込みロールの権限は編集できません。
            </div>
          <?php endif; ?>
          <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <?php foreach ($allPermissions as $key => $lbl): ?>
            <label class="flex <?php echo $isBuiltin ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'; ?> items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors <?php echo $isBuiltin ? '' : 'hover:bg-black/5 dark:hover:bg-white/5'; ?>"
                   style="border-color:var(--saso-card-bdr);color:var(--saso-text)">
              <input type="checkbox" name="perm_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                     value="1" class="h-4 w-4 rounded border accent-[#3c50e0]"
                     <?php echo $v->role->hasPermission($key) ? 'checked' : ''; ?>
                     <?php echo $isBuiltin ? 'disabled' : ''; ?>>
              <span><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <button type="submit" class="btn btn-primary w-full"<?php echo $isBuiltin ? ' disabled aria-disabled="true"' : ''; ?>>
          保存する
        </button>
      </form>
    </div>
  </div>
</div>
<?php }; ?>
