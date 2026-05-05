<?php
$this->title = 'Edit User';
$this->content = function ($v) {
?>
<ol class="breadcrumb mb-3">
  <li class="breadcrumb-item"><a href="./">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="./member/start/">Users</a></li>
  <li class="breadcrumb-item active" aria-current="page">Edit</li>
</ol>

<div class="card" style="max-width:36rem;">
  <div class="card-header">
    <h3 class="card-title">Edit User: <?php echo htmlspecialchars($v->member->id, ENT_QUOTES, 'UTF-8'); ?></h3>
  </div>
  <div class="card-body">
    <form action="./member/edit/" method="post">
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($v->member->id, ENT_QUOTES, 'UTF-8'); ?>">

      <?php if (!empty($v->error)): ?>
        <div class="alert alert-danger mb-3" role="alert"><?php echo htmlspecialchars($v->error); ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">User ID</label>
        <input type="text" value="<?php echo htmlspecialchars($v->member->id, ENT_QUOTES, 'UTF-8'); ?>"
               class="form-control bg-light" disabled aria-readonly="true">
      </div>

      <div class="mb-3">
        <label for="m-name" class="form-label">Name</label>
        <input id="m-name" type="text" name="userName"
               value="<?php echo htmlspecialchars($v->member->name, ENT_QUOTES, 'UTF-8'); ?>"
               class="form-control" placeholder="Enter display name" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Update User</button>
    </form>
  </div>
</div>
<?php }; ?>
