<?php $this->title = '商品一括登録'; ?>
<?php $this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $step = $v->step ?? 'upload';
    $validRows = $v->validRows ?? [];
    $errorRows = $v->errorRows ?? [];
    $token = $v->token ?? '';
    $flashSuccess = $v->flashSuccess ?? null;
    $flashError   = $v->flashError   ?? null;
?>

<?php if ($flashSuccess): ?>
    <?php ui('alert', ['variant' => 'success', 'body' => $flashSuccess, 'dismissible' => true]); ?>
<?php endif; ?>
<?php if ($flashError): ?>
    <?php ui('alert', ['variant' => 'danger', 'body' => $flashError, 'dismissible' => true]); ?>
<?php endif; ?>

<?php if ($step === 'upload'): ?>

  <!-- Upload form -->
  <?php
    ui('card', [
      'title' => $lang === 'ja' ? 'CSVファイルで一括登録' : 'Bulk Register via CSV',
      'body'  => function () use ($lang) { ?>
        <p class="mb-4 text-sm" style="color:var(--saso-text-sub)">
          <?php echo $lang === 'ja'
            ? 'CSVテンプレートをダウンロードして内容を記入し、アップロードしてください。色とサイズは <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">|</code>（パイプ）で区切ってください。'
            : 'Download the CSV template, fill it in, and upload it. Separate multiple colours/sizes with <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">|</code> (pipe).'; ?>
        </p>

        <div class="mb-5 flex flex-wrap items-center gap-3">
          <?php ui('button', [
            'label'   => $lang === 'ja' ? 'テンプレートをダウンロード' : 'Download Template',
            'type'    => 'link',
            'href'    => './item/bulkTemplate/',
            'variant' => 'secondary',
            'size'    => 'sm',
          ]); ?>
          <span class="text-xs" style="color:var(--saso-text-sub)">
            <?php echo $lang === 'ja' ? '（Excel対応 UTF-8 CSV）' : '(UTF-8 CSV, Excel compatible)'; ?>
          </span>
        </div>

        <form method="post" action="./item/bulkAdd/" enctype="multipart/form-data">
          <div class="mb-4">
            <label for="bulk-csv" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
              <?php echo $lang === 'ja' ? 'CSVファイルを選択' : 'Select CSV file'; ?>
              <span class="text-red-500" aria-hidden="true">*</span>
            </label>
            <input id="bulk-csv" type="file" name="csv" accept=".csv,text/csv" required
                   class="block w-full cursor-pointer rounded-lg border px-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-brand-500"
                   style="border-color:var(--saso-card-bdr);color:var(--saso-text)">
            <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">
              <?php echo $lang === 'ja' ? '最大ファイルサイズ: 2MB' : 'Max file size: 2 MB'; ?>
            </p>
          </div>

          <button type="submit" class="btn btn-primary w-full">
            <?php echo $lang === 'ja' ? 'プレビュー・検証' : 'Preview & Validate'; ?>
          </button>
        </form>
      <?php },
    ]);
  ?>

  <!-- CSV format guide -->
  <?php
    ui('card', [
      'title' => $lang === 'ja' ? 'CSVフォーマット' : 'CSV Format',
      'body'  => function () use ($lang) { ?>
        <div class="overflow-x-auto">
          <table class="w-full table-auto text-left text-sm">
            <thead>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="pb-2 pr-4 font-semibold" style="color:var(--saso-text)">
                  <?php echo $lang === 'ja' ? '列名' : 'Column'; ?>
                </th>
                <th class="pb-2 pr-4 font-semibold" style="color:var(--saso-text)">
                  <?php echo $lang === 'ja' ? '必須' : 'Required'; ?>
                </th>
                <th class="pb-2 font-semibold" style="color:var(--saso-text)">
                  <?php echo $lang === 'ja' ? '説明' : 'Description'; ?>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <?php
              $cols = $lang === 'ja' ? [
                ['商品名',         '必須', '50文字以内'],
                ['分類ID',         '任意', '数値（カテゴリのID）'],
                ['価格',           '任意', '9桁以内の整数（カンマ区切り可）'],
                ['色（|区切り）',   '必須', '例: 赤|青|白（各50文字以内）'],
                ['サイズ（|区切り）','必須', '例: S|M|L（各50文字以内、色数×サイズ数≤100）'],
                ['プラ',           '任意', '1 = プラあり、空欄 = なし'],
                ['プラ付記',       '任意', '50文字以内のメモ（プラ=1のときのみ有効）'],
                ['紙',             '任意', '1 = 紙あり、空欄 = なし'],
                ['紙付記',         '任意', '50文字以内のメモ（紙=1のときのみ有効）'],
              ] : [
                ['Item name',         'Yes', 'Max 50 chars'],
                ['Category ID',       'No',  'Numeric category ID'],
                ['Price',             'No',  'Integer up to 9 digits (commas OK)'],
                ['Colours (|sep)',     'Yes', 'e.g. Red|Blue|White (max 50 chars each)'],
                ['Sizes (|sep)',       'Yes', 'e.g. S|M|L (colours × sizes ≤ 100)'],
                ['Plastic',           'No',  '1 = yes, blank = no'],
                ['Plastic note',      'No',  'Up to 50 chars (used when Plastic = 1)'],
                ['Paper',             'No',  '1 = yes, blank = no'],
                ['Paper note',        'No',  'Up to 50 chars (used when Paper = 1)'],
              ];
              foreach ($cols as $col): ?>
                <tr>
                  <td class="py-1.5 pr-4 font-mono text-xs" style="color:var(--saso-text)">
                    <?php echo htmlspecialchars($col[0], ENT_QUOTES, 'UTF-8'); ?>
                  </td>
                  <td class="py-1.5 pr-4 text-xs">
                    <?php if ($col[1] === '必須' || $col[1] === 'Yes'): ?>
                      <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        <?php echo $col[1]; ?>
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <?php echo $col[1]; ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="py-1.5 text-xs" style="color:var(--saso-text-sub)">
                    <?php echo htmlspecialchars($col[2], ENT_QUOTES, 'UTF-8'); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php },
    ]);
  ?>

<?php elseif ($step === 'preview'): ?>

  <!-- Error rows (if any) -->
  <?php if (!empty($errorRows)): ?>
    <?php
      ui('card', [
        'title' => ($lang === 'ja' ? 'エラー行' : 'Rows with errors').' ('.count($errorRows).')',
        'body'  => function () use ($lang, $errorRows) { ?>
          <?php ui('alert', [
            'variant' => 'warning',
            'body'    => $lang === 'ja'
              ? '以下の行にエラーがあります。CSVを修正して再アップロードするか、エラー行を無視して有効行のみ登録できます。'
              : 'The rows below contain errors. Fix and re-upload, or continue to register only the valid rows.',
          ]); ?>
          <div class="mt-4 overflow-x-auto">
            <table class="w-full table-auto text-left text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? '行番号' : 'Row'; ?>
                  </th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? '商品名' : 'Item name'; ?>
                  </th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? 'エラー内容' : 'Errors'; ?>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($errorRows as $eRow): ?>
                  <tr>
                    <td class="px-3 py-2 text-xs text-gray-500">
                      <?php echo $eRow['row'] > 0 ? (int) $eRow['row'] : '—'; ?>
                    </td>
                    <td class="px-3 py-2 text-sm" style="color:var(--saso-text)">
                      <?php echo htmlspecialchars($eRow['name'] ?? '', ENT_QUOTES, 'UTF-8') ?: '<span class="text-gray-400">—</span>'; ?>
                    </td>
                    <td class="px-3 py-2">
                      <ul class="list-inside list-disc space-y-0.5 text-xs text-red-600 dark:text-red-400">
                        <?php foreach ($eRow['errors'] as $err): ?>
                          <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            <?php ui('button', [
              'label'   => $lang === 'ja' ? 'CSVを再アップロード' : 'Re-upload CSV',
              'type'    => 'link',
              'href'    => './item/bulkAdd/',
              'variant' => 'secondary',
              'size'    => 'sm',
            ]); ?>
          </div>
        <?php },
      ]);
    ?>
  <?php endif; ?>

  <!-- Valid rows preview + confirm form -->
  <?php if (!empty($validRows)): ?>
    <?php
      ui('card', [
        'title'   => ($lang === 'ja' ? '登録予定の商品' : 'Items to register').' ('.count($validRows).')',
        'actions' => function () use ($lang, $token, $validRows) { ?>
          <form method="post" action="./item/bulkAdd/">
            <input type="hidden" name="_confirm" value="1">
            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <?php ui('button', [
              'label'   => ($lang === 'ja' ? '登録実行' : 'Register').' ('.count($validRows).'件)',
              'type'    => 'submit',
              'variant' => 'primary',
              'size'    => 'sm',
            ]); ?>
          </form>
        <?php },
        'body' => function () use ($lang, $validRows) { ?>
          <div class="overflow-x-auto">
            <table class="w-full table-auto text-left text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">#</th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? '商品名' : 'Item name'; ?>
                  </th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? '価格' : 'Price'; ?>
                  </th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? '色' : 'Colours'; ?>
                  </th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? 'サイズ' : 'Sizes'; ?>
                  </th>
                  <th class="px-3 py-2 font-semibold" style="color:var(--saso-text)">
                    <?php echo $lang === 'ja' ? '梱包' : 'Packing'; ?>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($validRows as $i => $row): ?>
                  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-3 py-2 text-xs text-gray-400"><?php echo $i + 1; ?></td>
                    <td class="px-3 py-2 font-medium" style="color:var(--saso-text)">
                      <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td class="px-3 py-2 text-sm" style="color:var(--saso-text-sub)">
                      <?php echo $row['price'] !== null ? '¥'.number_format($row['price']) : '<span class="text-gray-400">—</span>'; ?>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex flex-wrap gap-1">
                        <?php foreach ($row['colors'] as $c): ?>
                          <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            <?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex flex-wrap gap-1">
                        <?php foreach ($row['sizes'] as $s): ?>
                          <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700 dark:bg-green-900/30 dark:text-green-300">
                            <?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td class="px-3 py-2 text-xs" style="color:var(--saso-text-sub)">
                      <?php
                        $packing = [];
                        if ($row['pla'])   $packing[] = 'プラ' . ($row['plaNote'] ? '('.$row['plaNote'].')' : '');
                        if ($row['paper']) $packing[] = '紙'   . ($row['paperNote'] ? '('.$row['paperNote'].')' : '');
                        echo $packing ? htmlspecialchars(implode('・', $packing), ENT_QUOTES, 'UTF-8') : '<span class="text-gray-400">—</span>';
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Bottom confirm button (duplicated for convenience on long lists) -->
          <div class="mt-4 flex items-center justify-between gap-3">
            <?php ui('button', [
              'label'   => $lang === 'ja' ? 'CSVを再アップロード' : 'Re-upload CSV',
              'type'    => 'link',
              'href'    => './item/bulkAdd/',
              'variant' => 'secondary',
            ]); ?>
            <form method="post" action="./item/bulkAdd/">
              <input type="hidden" name="_confirm" value="1">
              <input type="hidden" name="_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
              <?php ui('button', [
                'label'   => ($lang === 'ja' ? '登録実行' : 'Register').' ('.count($validRows).'件)',
                'type'    => 'submit',
                'variant' => 'primary',
              ]); ?>
            </form>
          </div>
        <?php },
      ]);
    ?>
  <?php else: ?>
    <?php ui('alert', ['variant' => 'warning', 'body' => $lang === 'ja' ? '有効な登録行がありません。CSVを確認してください。' : 'No valid rows found. Please check your CSV.']); ?>
    <div class="mt-4">
      <?php ui('button', [
        'label'   => $lang === 'ja' ? '戻る' : 'Back',
        'type'    => 'link',
        'href'    => './item/bulkAdd/',
        'variant' => 'secondary',
      ]); ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php }; ?>
