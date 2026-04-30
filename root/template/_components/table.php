<?php
/*
 * Table partial. Args:
 *   - columns: list<array{label: string, scope?: 'col'|'row', class?: string}>
 *   - rows:    iterable<list<string|array{value: string, class?: string}>>
 *   - caption?: string  (visually hidden, screen-reader only)
 *   - class?:  string   (extra wrapper classes)
 *   - empty?:  string   (text shown when rows is empty)
 */
$columns = $columns ?? [];
$rows    = $rows    ?? [];
$caption = $caption ?? null;
$class   = $class   ?? '';
$empty   = $empty   ?? __('ui.table.empty', [], null, 'No records');
?>
<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark overflow-hidden <?php echo ui_attr($class); ?>">
  <div class="w-full overflow-x-auto">
    <table class="w-full table-auto text-left text-sm">
      <?php if ($caption): ?>
        <caption class="sr-only"><?php echo ui_text($caption); ?></caption>
      <?php endif; ?>
      <thead class="bg-gray-2 text-black dark:bg-meta-4 dark:text-white">
        <tr class="border-b border-stroke dark:border-strokedark">
          <?php foreach ($columns as $col): ?>
            <th scope="<?php echo ui_attr($col['scope'] ?? 'col'); ?>" class="px-4 py-3 font-semibold whitespace-nowrap <?php echo ui_attr($col['class'] ?? ''); ?>">
              <?php echo ui_text($col['label']); ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $rendered = 0;
        foreach ($rows as $row):
            $rendered++;
        ?>
          <tr class="border-b border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-meta-4 transition">
            <?php foreach ($row as $cell):
                if (is_array($cell)) {
                    $cellClass = $cell['class'] ?? '';
                    $cellValue = $cell['value'];
                    $isHtml = !empty($cell['html']);
                } else {
                    $cellClass = '';
                    $cellValue = $cell;
                    $isHtml = false;
                }
            ?>
              <td class="px-4 py-3 text-black dark:text-white <?php echo ui_attr($cellClass); ?>">
                <?php echo $isHtml ? $cellValue : ui_text((string) $cellValue); ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        <?php if ($rendered === 0): ?>
          <tr>
            <td colspan="<?php echo count($columns); ?>" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
              <?php echo ui_text($empty); ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
