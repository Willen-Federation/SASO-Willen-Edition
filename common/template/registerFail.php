<?php $this->title = __('ui.common.error', [], null, 'Error'); ?>
<?php $this->content = function($v) { ?>

<div class="mx-auto max-w-md text-center">
  <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-error-100 text-error-600 dark:bg-error-500/15 dark:text-error-400">
    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>

  <h1 class="mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90">
    <?php echo ui_text(__('ui.common.register_fail.title', [], null, 'Operation failed')); ?>
  </h1>

  <div class="mb-6 rounded-lg bg-gray-50 p-4 text-left text-theme-sm text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
    <p class="font-medium text-gray-800 dark:text-white/90 mb-1">
      <?php echo ui_text(__('ui.common.register_fail.reason', [], null, 'Reason:')); ?>
    </p>
    <p>
    <?php echo match($v->errorMessage) {
        //item\\RegisterConfirm
        'invalid pla note.' => 'プラの付記は50文字以内として下さい。',
        'invalid paper note.' => '紙の付記は50文字以内として下さい。',
        'invalid color code.' => '色名は1つから最大100個まで入力できます。',
        'invalid color name.' => '色名はそれぞれ50字以内として下さい。',
        'color is nothing.' => '色名が未入力です。',
        'invalid size code.' => 'サイズ名は1つから最大100個まで入力できます。',
        'invalid size name.' => 'サイズ名はそれぞれ50字以内として下さい。',
        'invalid size order number.' => 'サイズ名は1つから最大100個まで入力できます。',
        'size is nothing.' => 'サイズ名が未入力です。',
        'invalid item.' => '商品名は50字以内で入力して下さい。',
        //feature\\Amount
        'invalid input.'=>'何らかの入力エラーがあります。',
        //shelf\\Multi
        'invalid mins input.'=>'最小値は英数のみです。',
        'invalid page.'=>'ページ数が正しくありません。',
        //shelf\\Single
        'invalid single shelf input.'=>'棚番は半角英数とハイフンのみ使用できます。',
        //label\\Register
        'length is invalid.'=>'A4サイズに対して寸法が矛盾しています。',
        //label\\Delete
        'label not found.'=>'ラベルが見つかりません。',
        //item\\Archive
        'archive note is invalid or item is not found.'=>'アーカイブ理由は50字以内で入力して下さい。',
        //item\\ArchivedAll
        'item id is invalid.'=>'商品IDが不正です。',
        'some item has archived.'=>'既にアーカイブされている商品が含まれています。',
        'archive note is invalid.'=>'アーカイブ理由は50字以内で入力して下さい。',
        //item\\ChangeCategory
        'item or category are not found.'=>'商品か分類が見つかりません。',
        //item\\ChangePrice
        'item is not found or invalid price.'=>'価格は9桁以下にして下さい。',
        //item\\ChangeSizeOrder
        'item is not found.'=>'商品がありません。',
        //item\\Reproduction
        'fail to reproduction.'=>'復刻に失敗ました。',
        //item\\AddFeature
        'size or color is too many.'=>'サイズまたは色が多すぎます。',
        'color is invalid.'=>'各色50字以内として下さい。',
        'size is invalid.'=>'各サイズ50字以内として下さい。',
        default => '何らかの入力エラーがあります。'.ui_text($v->errorMessage),
    }; ?>
    </p>
  </div>

  <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
    <?php ui('button', [
      'label'   => __('ui.common.back', [], null, 'Back'),
      'type'    => 'link',
      'href'    => './'.$v->start,
      'variant' => 'secondary',
    ]); ?>
    <?php ui('button', [
      'label'   => __('ui.nav.home', [], null, 'Home'),
      'type'    => 'link',
      'href'    => './',
      'variant' => 'primary',
    ]); ?>
  </div>
</div>

<?php }; ?>
