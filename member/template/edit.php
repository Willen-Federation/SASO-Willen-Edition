<?php
$this->title = 'Edit User';

$this->content = function ($v) {
?>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <h2 class="text-title-md2 font-semibold text-black dark:text-white">
    Edit User
  </h2>
  <nav>
    <ol class="flex items-center gap-2">
      <li><a class="font-medium" href="./">Dashboard /</a></li>
      <li><a class="font-medium" href="./member/start/">Users /</a></li>
      <li class="font-medium text-primary">Edit</li>
    </ol>
  </nav>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
  <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
    <h3 class="font-medium text-black dark:text-white">Edit User: <?php echo htmlspecialchars($v->member->id); ?></h3>
  </div>
  <form action="./member/edit/" method="POST">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($v->member->id); ?>">
    <div class="p-6.5">
      <?php if (!empty($v->error)): ?>
        <div class="mb-5 text-danger font-medium"><?php echo htmlspecialchars($v->error); ?></div>
      <?php endif; ?>

      <div class="mb-4.5">
        <label class="mb-2.5 block text-black dark:text-white">User ID</label>
        <input type="text" value="<?php echo htmlspecialchars($v->member->id); ?>" disabled class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input">
      </div>

      <div class="mb-4.5">
        <label class="mb-2.5 block text-black dark:text-white">Name</label>
        <input type="text" name="userName" value="<?php echo htmlspecialchars($v->member->name); ?>" placeholder="Enter display name" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" required>
      </div>

      <button type="submit" class="flex w-full justify-center rounded bg-primary p-3 font-medium text-gray hover:bg-opacity-90">
        Update User
      </button>
    </div>
  </form>
</div>
<?php }; ?>
