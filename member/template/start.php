<?php $this->title = __('ui.member.title', [], null, 'Users Management'); ?>
<?php $this->content = function ($v) { ?>

<div class="mb-4 flex items-center justify-between">
  <?php
  ob_start();
  ui('iconHeroicon', ['name' => 'plus', 'class' => 'h-5 w-5']);
  $plusIcon = ob_get_clean();
  ui('button', [
    'label'   => __('ui.member.add_button', [], null, 'Register New User'),
    'href'    => './member/add/',
    'type'    => 'link',
    'variant' => 'primary',
    'icon'    => $plusIcon,
  ]); ?>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
  <div class="py-4 px-4 md:px-6">
    <h4 class="text-lg font-semibold text-black dark:text-white">
      <?php echo ui_text(__('ui.member.list_title', [], null, 'User List')); ?>
    </h4>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full table-auto">
      <thead>
        <tr class="bg-gray-2 text-left dark:bg-meta-4">
          <th class="py-3 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.member.col_id', [], null, 'User ID')); ?></th>
          <th class="py-3 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.member.col_name', [], null, 'Name')); ?></th>
          <th class="py-3 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.member.col_role', [], null, 'Role')); ?></th>
          <th class="py-3 px-4 text-center font-medium text-black dark:text-white"><?php echo ui_text(__('ui.member.col_actions', [], null, 'Actions')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($v->members)): ?>
        <tr>
          <td colspan="4" class="py-6 px-4 text-center text-gray-500 dark:text-gray-400">
            <?php echo ui_text(__('ui.member.empty', [], null, 'No users registered.')); ?>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($v->members as $m): ?>
        <tr class="border-t border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-meta-4">
          <td class="py-3 px-4">
            <span class="font-mono text-sm text-black dark:text-white"><?php echo ui_text($m->id); ?></span>
          </td>
          <td class="py-3 px-4">
            <span class="text-sm text-black dark:text-white"><?php echo ui_text($m->name); ?></span>
          </td>
          <td class="py-3 px-4">
            <span class="inline-block rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
              <?php echo ui_text($m->role ?? 'user'); ?>
            </span>
          </td>
          <td class="py-3 px-4 text-center">
            <div class="inline-flex items-center gap-3">
              <a href="./member/edit/?id=<?php echo urlencode($m->id); ?>"
                 class="inline-flex items-center gap-1 rounded border border-stroke px-3 py-1 text-xs hover:border-primary hover:text-primary dark:border-strokedark"
                 title="<?php echo ui_attr(__('ui.member.edit', [], null, 'Edit')); ?>">
                <?php ui('iconHeroicon', ['name' => 'pencil', 'class' => 'h-4 w-4']); ?>
                <?php echo ui_text(__('ui.member.edit', [], null, 'Edit')); ?>
              </a>
              <form method="post" action="./member/delete/" class="inline m-0"
                    onsubmit="return confirm('<?php echo ui_attr(__('ui.member.confirm_delete', [], null, 'Delete this user?')); ?>');">
                <input type="hidden" name="id" value="<?php echo ui_attr($m->id); ?>">
                <button type="submit"
                        class="inline-flex items-center gap-1 rounded border border-stroke px-3 py-1 text-xs hover:border-danger hover:text-danger dark:border-strokedark"
                        title="<?php echo ui_attr(__('ui.member.delete', [], null, 'Delete')); ?>">
                  <?php ui('iconHeroicon', ['name' => 'trash', 'class' => 'h-4 w-4']); ?>
                  <?php echo ui_text(__('ui.member.delete', [], null, 'Delete')); ?>
                </button>
              </form>
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
