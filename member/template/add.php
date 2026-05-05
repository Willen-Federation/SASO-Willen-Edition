<?php
$this->title = 'Register New User';
$this->content = function ($v) {
?>
<ol class="breadcrumb mb-3">
  <li class="breadcrumb-item"><a href="./">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="./member/start/">Users</a></li>
  <li class="breadcrumb-item active" aria-current="page">Register</li>
</ol>

<div class="card" style="max-width:36rem;">
  <div class="card-header">
    <h3 class="card-title">User Details</h3>
  </div>
  <div class="card-body">
    <form action="./member/add/" method="POST">
      <?php if (!empty($v->error)): ?>
        <div class="alert alert-danger mb-3" role="alert"><?php echo htmlspecialchars($v->error); ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label for="m-id" class="form-label">User ID</label>
        <input id="m-id" type="text" name="id" class="form-control"
               placeholder="Enter alphanumeric User ID (8-20 chars)"
               required minlength="8" maxlength="20" pattern="[a-zA-Z0-9_-]+">
      </div>

      <div class="mb-3">
        <label for="m-name" class="form-label">Name</label>
        <input id="m-name" type="text" name="userName" class="form-control"
               placeholder="Enter display name" required>
      </div>

      <div class="mb-3">
        <label for="m-pw" class="form-label">Password</label>
        <input id="m-pw" type="password" name="password" class="form-control"
               placeholder="Enter password" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Register User</button>
    </form>
  </div>
</div>
<?php }; ?>
