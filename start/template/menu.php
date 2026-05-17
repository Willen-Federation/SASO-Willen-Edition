<?php $this->content = function ($v) {
    $groups = [
        [
            'label' => __('ui.sidebar.group.inventory', [], null, 'Inventory'),
            'tiles' => [
                [
                    'href'  => './item/add/',
                    'icon'  => 'plus-circle',
                    'label' => __('ui.sidebar.item_register', [], null, 'Register product'),
                    'help'  => __('ui.dashboard.item_register_help', [], null, 'Add a new SKU to the catalogue.'),
                    'tone'  => 'primary',
                ],
                [
                    'href'  => './verify/start/',
                    'icon'  => 'check-circle',
                    'label' => __('ui.sidebar.verify', [], null, 'Data verify'),
                    'help'  => __('ui.dashboard.verify_help', [], null, 'Run stocktakes or spot-checks.'),
                    'tone'  => 'primary',
                ],
                [
                    'href'  => './archive/list/',
                    'icon'  => 'archive',
                    'label' => __('ui.sidebar.item_archive', [], null, 'Archive list'),
                    'help'  => __('ui.dashboard.archive_help', [], null, 'Browse archived items.'),
                    'tone'  => 'gray',
                ],
            ]
        ],
        [
            'label' => __('ui.sidebar.group.label', [], null, 'Labels'),
            'tiles' => [
                [
                    'href'  => './label/features/',
                    'icon'  => 'printer',
                    'label' => __('ui.sidebar.label_print', [], null, 'Print item labels'),
                    'help'  => __('ui.dashboard.label_help', [], null, 'Generate barcode labels for registered items.'),
                    'tone'  => 'warning',
                ],
                [
                    'href'  => './barcode/sheet/',
                    'icon'  => 'qr',
                    'label' => __('ui.sidebar.barcode_sheet', [], null, 'Create barcodes'),
                    'help'  => __('ui.dashboard.barcode_sheet_help', [], null, 'Create, print, then attach barcode labels to items.'),
                    'tone'  => 'success',
                ],
            ]
        ],
        [
            'label' => __('ui.sidebar.group.master', [], null, 'Master data'),
            'tiles' => [
                [
                    'href'  => './shelf/start/',
                    'icon'  => 'grid',
                    'label' => __('ui.sidebar.shelf_create', [], null, 'Shelf labels'),
                    'help'  => __('ui.dashboard.shelf_help', [], null, 'Detailed shelf management and labels.'),
                    'tone'  => 'success',
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
                    'href'  => './itemAttribute/start/',
                    'icon'  => 'adjustments',
                    'label' => __('ui.sidebar.item_attribute', [], null, 'Status columns'),
                    'help'  => __('ui.dashboard.item_attribute_help', [], null, 'Add custom status columns to items.'),
                    'tone'  => 'success',
                ],
            ]
        ],
        [
            'label' => __('ui.sidebar.group.system', [], null, 'System'),
            'tiles' => [
                [
                    'href'  => './start/password/',
                    'icon'  => 'key',
                    'label' => __('ui.sidebar.password', [], null, 'Password'),
                    'help'  => __('ui.dashboard.password_help', [], null, 'Change your sign-in credentials.'),
                    'tone'  => 'gray',
                ],
            ]
        ],
    ];

    $toneIcon = [
        'primary' => 'text-primary bg-primary bg-opacity-10',
        'success' => 'text-success bg-success bg-opacity-10',
        'warning' => 'text-warning bg-warning bg-opacity-10',
        'gray'    => 'text-body bg-stroke bg-opacity-50',
    ];
    $toneAccent = [
        'primary' => 'saso-tile-accent-primary',
        'success' => 'saso-tile-accent-success',
        'warning' => 'saso-tile-accent-warning',
        'gray'    => 'saso-tile-accent-gray',
    ];
?>

<div class="space-y-10">
  <?php foreach ($groups as $group): ?>
    <section aria-labelledby="dashboard-group-<?php echo ui_attr(strtolower((string)$group['label'])); ?>">
      <h2 id="dashboard-group-<?php echo ui_attr(strtolower((string)$group['label'])); ?>"
          class="mb-4 text-xs font-bold uppercase tracking-widest text-body dark:text-bodydark2">
        <?php echo ui_text($group['label']); ?>
      </h2>
      <ul class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($group['tiles'] as $tile): ?>
          <li>
            <a href="<?php echo ui_attr($tile['href']); ?>"
               class="saso-tile <?php echo ui_attr($toneAccent[$tile['tone']] ?? 'saso-tile-accent-gray'); ?>">
              <!-- Icon badge -->
              <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl <?php echo ui_attr($toneIcon[$tile['tone']] ?? $toneIcon['gray']); ?>">
                <?php ui('iconHeroicon', ['name' => $tile['icon'], 'class' => 'h-5 w-5']); ?>
              </div>
              <!-- Label -->
              <p class="text-base font-semibold text-black dark:text-white">
                <?php echo ui_text($tile['label']); ?>
              </p>
              <!-- Description -->
              <p class="mt-1 text-sm text-body dark:text-bodydark2">
                <?php echo ui_text($tile['help']); ?>
              </p>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endforeach; ?>
</div>
<?php }; ?>
