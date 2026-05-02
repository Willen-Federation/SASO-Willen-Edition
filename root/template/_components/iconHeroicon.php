<?php
/*
 * Inline Heroicon SVG sprite. Args:
 *   - name:   string  (matches a key in the map below)
 *   - class?: string  (extra classes; default 'h-5 w-5')
 *   - title?: string  (when provided, sets aria-label and adds <title>)
 *
 * The icons are vendored as Heroicons (MIT, https://heroicons.com).
 * Outline 24x24 set, slightly simplified for SASO's needs.
 */
$name  = $name ?? '';
$class = $class ?? 'h-5 w-5';
$title = $title ?? null;

$paths = [
    'home'        => '<path d="M3 12 12 3l9 9M5 10v10a1 1 0 0 0 1 1h4v-7h4v7h4a1 1 0 0 0 1-1V10" stroke-linecap="round" stroke-linejoin="round"/>',
    'box'         => '<path d="M3 7v10a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 17V7a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 7Zm0 0 9 5m0 0 9-5m-9 5v10" stroke-linecap="round" stroke-linejoin="round"/>',
    'grid'        => '<path d="M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v6H4zM14 15h6v6h-6z" stroke-linejoin="round"/>',
    'printer'     => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z" stroke-linecap="round" stroke-linejoin="round"/>',
    'tag'         => '<path d="m20 12-7 7a2 2 0 0 1-2.83 0L3 11.83V4h7.83L20 13.17a2 2 0 0 1 0 2.83Z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1" fill="currentColor"/>',
    'toggle'      => '<rect x="3" y="8" width="18" height="8" rx="4"/><circle cx="9" cy="12" r="2.5" fill="currentColor"/>',
    'shield'      => '<path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6l-8-3Z" stroke-linecap="round" stroke-linejoin="round"/>',
    'users'       => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z" stroke-linecap="round" stroke-linejoin="round"/>',
    'key'         => '<circle cx="8" cy="14" r="4"/><path d="m11 11 9-9m-3 3 2 2m-5-5 2 2" stroke-linecap="round" stroke-linejoin="round"/>',
    'check-square'=> '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="m7 12 3 3 7-7" stroke-linecap="round" stroke-linejoin="round"/>',
    'sparkles'    => '<path d="M12 3v3m0 12v3M3 12h3m12 0h3M5 5l2.1 2.1m9.8 9.8L19 19M5 19l2.1-2.1m9.8-9.8L19 5" stroke-linecap="round" stroke-linejoin="round"/>',
    'archive'     => '<rect x="3" y="3" width="18" height="5" rx="1"/><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8M10 12h4" stroke-linecap="round" stroke-linejoin="round"/>',
    'list'        => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-linecap="round" stroke-linejoin="round"/>',
    'map'         => '<path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 0v14m6-12v14" stroke-linecap="round" stroke-linejoin="round"/>',
    'qr'          => '<path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h-3zM18 18h3v3h-3z" stroke-linejoin="round"/>',
    'pencil'      => '<path d="M15.232 5.232l3.536 3.536M9 13l-4 1 1-4L14.768 2.232a2 2 0 0 1 2.828 0l.172.172a2 2 0 0 1 0 2.828L9 13Z" stroke-linecap="round" stroke-linejoin="round"/>',
    'trash'       => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke-linecap="round" stroke-linejoin="round"/>',
    'plus'        => '<path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>',
    'x'           => '<path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>',
    'eye'         => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/>',
    'archive-box' => '<rect x="3" y="3" width="18" height="5" rx="1"/><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8M10 12h4" stroke-linecap="round" stroke-linejoin="round"/>',
    'search'      => '<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round"/>',
    'barcode'     => '<path d="M3 5v14M7 5v14M11 5v14M15 5v14M19 5v14M21 5v14M5 5v14M9 5v14M13 5v14M17 5v14" stroke-linecap="round" stroke-linejoin="round"/>',
];
$svg = $paths[$name] ?? '<circle cx="12" cy="12" r="9"/>';
?>
<svg class="<?php echo ui_attr($class); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     <?php if ($title): ?>role="img" aria-label="<?php echo ui_attr($title); ?>"<?php else: ?>aria-hidden="true"<?php endif; ?>>
  <?php if ($title): ?><title><?php echo ui_text($title); ?></title><?php endif; ?>
  <?php echo $svg; ?>
</svg>
