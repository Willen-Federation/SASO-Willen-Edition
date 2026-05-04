<?php
$this->title = 'Register New User';

$this->content = function ($v) {
?>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <h2 class="text-title-md2 font-semibold text-black dark:text-white">
    Register New User
  </h2>
  <nav>
    <ol class="flex items-center gap-2">
      <li><a class="font-medium" href="./">Dashboard /</a></li>
      <li><a class="font-medium" href="./member/start/">Users /</a></li>
      <li class="font-medium text-primary">Register</li>
    </ol>
  </nav>
</div>

<div class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
  <div class="border-b border-gray-200 py-4 px-6.5 dark:border-gray-800">
    <h3 class="font-medium text-black dark:text-white">User Details</h3>
  </div>
  <form action="./member/add/" method="POST">
    <div class="p-6.5">
      <?php if (!empty($v->error)): ?>
        <div class="mb-5 text-danger font-medium"><?php echo htmlspecialchars($v->error); ?></div>
      <?php endif; ?>

      <div class="mb-4.5">
        <label class="mb-2.5 block text-black dark:text-white">User ID</label>
        <input type="text" name="id" placeholder="Enter alphanumeric User ID (8-20 chars)" class="w-full rounded border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-gray-800 dark:bg-form-input dark:focus:border-primary" required minlength="8" maxlength="20" pattern="[a-zA-Z0-9_-]+">
      </div>

      <div class="mb-4.5">
        <label class="mb-2.5 block text-black dark:text-white">Name</label>
        <input type="text" name="userName" placeholder="Enter display name" class="w-full rounded border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-gray-800 dark:bg-form-input dark:focus:border-primary" required>
      </div>

      <div class="mb-4.5">
        <label class="mb-2.5 block text-black dark:text-white">Password</label>
        <input type="password" name="password" placeholder="Enter password" class="w-full rounded border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-gray-800 dark:bg-form-input dark:focus:border-primary" required>
      </div>

      <button type="submit" class="flex w-full justify-center rounded bg-primary p-3 font-medium text-gray hover:bg-opacity-90">
        Register User
      </button>
    </div>
  </form>
</div>
<?php }; ?>
