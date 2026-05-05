<?php $this->title = 'ロール追加'; ?>
<?php $this->content = function ($v) { ?>
<?php use saso\entity\Role; ?>

<ol class="breadcrumb mb-3">
  <li class="breadcrumb-item"><a href="./">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="./role/start/">ロール管理</a></li>
  <li class="breadcrumb-item active">追加</li>
</ol>

<div class="card mx-auto" style="max-width:40rem;">
  <div class="card-header">
    <h3 class="card-title">新しいロールを作成</h3>
  </div>
  <div class="card-body">
    <form action="./role/add/" method="post">
      <?php if (!empty($v->error)): ?>
        <div class="alert alert-danger mb-3" role="alert"><?php echo htmlspecialchars($v->error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label for="r-name" class="form-label">ロール名 <span class="text-danger">*</span></label>
        <input id="r-name" type="text" name="name" class="form-control"
               placeholder="例: manager（英数字・アンダースコア・ハイフンのみ）" required
               pattern="[a-zA-Z0-9_\-]+" maxlength="50">
      </div>

      <div class="mb-3">
        <label for="r-label" class="form-label">表示名 <span class="text-danger">*</span></label>
        <input id="r-label" type="text" name="label" class="form-control"
               placeholder="例: マネージャー" required maxlength="100">
      </div>

      <div class="mb-3">
        <label class="form-label">権限</label>
        <div class="row g-2">
          <?php foreach (Role::PERMISSIONS as $key => $lbl): ?>
          <div class="col-6 col-md-4">
            <label class="form-check">
              <input class="form-check-input" type="checkbox" name="perm_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="1">
              <span class="form-check-label"><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100">作成する</button>
    </form>
  </div>
</div>
<?php }; ?>
