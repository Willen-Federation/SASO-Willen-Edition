<?php $this->content = function ($v) {
    $tiles = [
        [
            'href'  => './item/add/',
            'icon'  => 'box',
            'label' => __('ui.sidebar.item_register', [], null, 'Register product'),
            'help'  => __('ui.dashboard.item_register_help', [], null, 'Add a new SKU to the catalogue.'),
            'tone'  => 'primary',
        ],
        [
            'href'  => './barcode/sheet/',
            'icon'  => 'printer',
            'label' => __('ui.sidebar.barcode_sheet', [], null, 'Barcode sheet'),
            'help'  => __('ui.dashboard.barcode_sheet_help', [], null, 'Print a sheet of unique barcodes.'),
            'tone'  => 'success',
        ],
        [
            'href'  => './shelf/simple/',
            'icon'  => 'grid',
            'label' => __('ui.sidebar.shelf_simple', [], null, 'Shelf setup'),
            'help'  => __('ui.dashboard.shelf_simple_help', [], null, 'Quickly configure shelf ranges.'),
            'tone'  => 'success',
        ],
        [
            'href'  => './verify/start/',
            'icon'  => 'check-circle',
            'label' => __('ui.sidebar.verify', [], null, 'Data verify'),
            'help'  => __('ui.dashboard.verify_help', [], null, 'Run stocktakes or spot-checks.'),
            'tone'  => 'primary',
        ],
        [
            'href'  => './shelf/start/',
            'icon'  => 'grid',
            'label' => __('ui.sidebar.shelf_create', [], null, 'Shelf labels'),
            'help'  => __('ui.dashboard.shelf_help', [], null, 'Detailed shelf management and labels.'),
            'tone'  => 'success',
        ],
        [
            'href'  => './label/features/',
            'icon'  => 'printer',
            'label' => __('ui.sidebar.label_print', [], null, 'Print labels'),
            'help'  => __('ui.dashboard.label_help', [], null, 'Generate barcode labels for items.'),
            'tone'  => 'warning',
        ],
        [
            'href'  => './archive/list/',
            'icon'  => 'archive',
            'label' => __('ui.sidebar.item_archive', [], null, 'Archive list'),
            'help'  => __('ui.dashboard.archive_help', [], null, 'Browse archived items.'),
            'tone'  => 'gray',
        ],
        [
            'href'  => './item/archivingAll/',
            'icon'  => 'archive',
            'label' => __('ui.sidebar.item_archiveAll', [], null, 'Archive all'),
            'help'  => __('ui.dashboard.archive_all_help', [], null, 'Archive every active item in one step.'),
            'tone'  => 'gray',
        ],
        [
            'href'  => './category/start/',
            'icon'  => 'tag',
            'label' => __('ui.sidebar.category', [], null, 'Categories'),
            'help'  => __('ui.dashboard.category_help', [], null, 'Manage the category tree.'),
            'tone'  => 'primary',
        ],
        [
            'href'  => './label/start/',
            'icon'  => 'list',
            'label' => __('ui.sidebar.label_size', [], null, 'Label sizes'),
            'help'  => __('ui.dashboard.label_size_help', [], null, 'Configure A4 sheet layouts.'),
            'tone'  => 'primary',
        ],
        [
            'href'  => './start/password/',
            'icon'  => 'key',
            'label' => __('ui.sidebar.password', [], null, 'Password'),
            'help'  => __('ui.dashboard.password_help', [], null, 'Change your sign-in credentials.'),
            'tone'  => 'gray',
        ],
    ];

    $toneToBadge = [
        'primary' => 'ta-badge ta-badge-primary',
        'success' => 'ta-badge ta-badge-success',
        'warning' => 'ta-badge ta-badge-warning',
        'gray'    => 'ta-badge ta-badge-gray',
    ];
?>

<section aria-labelledby="dashboard-quick-links" class="mb-6">
  <h2 id="dashboard-quick-links" class="sr-only"><?php echo ui_text(__('ui.dashboard.quick_links', [], null, 'Quick links')); ?></h2>
  <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($tiles as $tile): ?>
      <li>
        <a href="<?php echo ui_attr($tile['href']); ?>"
           class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition-shadow hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]">
          <div class="mb-3 flex items-center justify-between">
            <span class="<?php echo ui_attr($toneToBadge[$tile['tone']] ?? 'ta-badge ta-badge-gray'); ?>">
              <?php ui('iconHeroicon', ['name' => $tile['icon'], 'class' => 'h-3.5 w-3.5']); ?>
            </span>
          </div>
          <p class="text-base font-semibold text-gray-800 dark:text-white/90"><?php echo ui_text($tile['label']); ?></p>
          <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400"><?php echo ui_text($tile['help']); ?></p>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php }; ?>
