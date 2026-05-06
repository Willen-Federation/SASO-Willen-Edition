<?php $this->title = 'ロール編集'; ?>
<?php $this->content = function ($v) {
    $allPermissions = \saso\entity\Role::PERMISSIONS;
?>

<ol class="breadcrumb mb-3">
  <li class="breadcrumb-item"><a href="./">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="./role/start/">ロール管理</a></li>
  <li class="breadcrumb-item active"><?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?></li>
</ol>

<div class="card mx-auto" style="max-width:40rem;">
  <div class="card-header">
    <h3 class="card-title">ロール編集:
      <code class="ms-1"><?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?></code>
    </h3>
  </div>
  <div class="card-body">
    <form action="./role/edit/" method="post">
      <input type="hidden" name="name" value="<?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?>">

      <?php if (!empty($v->error)): ?>
        <div class="alert alert-danger mb-3" role="alert"><?php echo htmlspecialchars($v->error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">ロール名</label>
        <input type="text" value="<?php echo htmlspecialchars($v->role->name, ENT_QUOTES, 'UTF-8'); ?>"
               class="form-control bg-light" disabled aria-readonly="true">
      </div>

      <div class="mb-3">
        <label for="r-label" class="form-label">表示名 <span class="text-danger">*</span></label>
        <input id="r-label" type="text" name="label" class="form-control"
               value="<?php echo htmlspecialchars($v->role->label, ENT_QUOTES, 'UTF-8'); ?>"
               required maxlength="100">
      </div>

      <div class="mb-3">
        <label class="form-label">権限</label>
        <?php if (in_array($v->role->name, ['admin', 'operator'], true)): ?>
          <div class="alert alert-info py-2 mb-2">
            <i class="bi bi-info-circle me-1"></i>
            組み込みロールの権限は編集できません。
          </div>
        <?php endif; ?>
        <div class="row g-2">
          <?php foreach ($allPermissions as $key => $lbl): ?>
          <div class="col-6 col-md-4">
            <label class="form-check">
              <input class="form-check-input" type="checkbox"
                     name="perm_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="1"
                     <?php echo $v->role->hasPermission($key) ? 'checked' : ''; ?>
                     <?php echo in_array($v->role->name, ['admin', 'operator'], true) ? 'disabled' : ''; ?>>
              <span class="form-check-label"><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100">保存する</button>
    </form>
  </div>
</div>
<?php }; ?>
