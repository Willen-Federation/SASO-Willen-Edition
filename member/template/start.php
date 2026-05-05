<?php $this->title = __('ui.member.title', [], null, 'Users Management'); ?>
<?php $this->content = function ($v) { ?>

<div class="mb-3 d-flex align-items-center justify-content-between">
  <?php ui('button', [
    'label'   => __('ui.member.add_button', [], null, 'Register New User'),
    'href'    => './member/add/',
    'type'    => 'link',
    'variant' => 'primary',
    'icon'    => '<i class="ti ti-plus me-1"></i>',
  ]); ?>
</div>

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <?php echo ui_text(__('ui.member.list_title', [], null, 'User List')); ?>
    </h4>
  </div>
  <div class="table-responsive">
    <table class="table table-vcenter card-table">
      <thead>
        <tr>
          <th scope="col"><?php echo ui_text(__('ui.member.col_id', [], null, 'User ID')); ?></th>
          <th scope="col"><?php echo ui_text(__('ui.member.col_name', [], null, 'Name')); ?></th>
          <th scope="col"><?php echo ui_text(__('ui.member.col_role', [], null, 'Role')); ?></th>
          <th scope="col" class="text-center"><?php echo ui_text(__('ui.member.col_actions', [], null, 'Actions')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($v->members)): ?>
        <tr>
          <td colspan="4" class="text-center text-muted py-4">
            <?php echo ui_text(__('ui.member.empty', [], null, 'No users registered.')); ?>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($v->members as $m): ?>
        <tr>
          <td><code class="font-monospace small"><?php echo ui_text($m->id); ?></code></td>
          <td><?php echo ui_text($m->name); ?></td>
          <td>
            <span class="badge bg-primary-subtle text-primary">
              <?php echo ui_text($m->role ?? 'user'); ?>
            </span>
          </td>
          <td class="text-center">
            <div class="d-inline-flex gap-2">
              <a href="./member/edit/?id=<?php echo urlencode($m->id); ?>"
                 class="btn btn-sm btn-outline-secondary"
                 title="<?php echo ui_attr(__('ui.member.edit', [], null, 'Edit')); ?>">
                <i class="ti ti-pencil me-1" aria-hidden="true"></i><?php echo ui_text(__('ui.member.edit', [], null, 'Edit')); ?>
              </a>
              <form method="post" action="./member/delete/" class="d-inline m-0"
                    onsubmit="return confirm('<?php echo ui_attr(__('ui.member.confirm_delete', [], null, 'Delete this user?')); ?>');">
                <input type="hidden" name="id" value="<?php echo ui_attr($m->id); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        title="<?php echo ui_attr(__('ui.member.delete', [], null, 'Delete')); ?>">
                  <i class="ti ti-trash me-1" aria-hidden="true"></i><?php echo ui_text(__('ui.member.delete', [], null, 'Delete')); ?>
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
