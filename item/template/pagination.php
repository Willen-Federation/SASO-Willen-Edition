<?php $this->content = function($v) {
  if (($v->pageAmount ?? 0) <= 1) {
    return;
  }
?>

<nav aria-label="<?php echo ui_attr(__('ui.common.pagination', [], null, 'Page navigation')); ?>"
     class="flex justify-center">
  <ul class="inline-flex items-center gap-1">
    <?php for($i = 1; $i <= $v->pageAmount; $i++): ?>
      <?php $isCurrent = ($i == $v->page) || ($v->page == null && $i == 1); ?>
      <li>
        <a href="<?php echo ui_attr('./'. $v->request .'/sortby/'. $v->sortBy .'/direction/'. $v->direction . $v->search . '/page/'.$i); ?>"
           class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-md px-3 text-sm font-medium transition-colors <?php echo $isCurrent
              ? 'bg-primary text-white'
              : 'text-body hover:bg-gray-100 hover:text-primary dark:text-bodydark dark:hover:bg-meta-4'; ?>"
           <?php if ($isCurrent): ?>aria-current="page"<?php endif; ?>>
          <?php echo (int) $i; ?>
        </a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>

<?php }; ?>
