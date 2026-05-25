<?php $this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
?>

<?php if (!$v->authorized): ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => '管理者権限が必要です',
    'body'    => '設定を管理するには role=admin のユーザーでサインインしてください。',
  ]); ?>
<?php else: ?>

  <?php ui('card', [
    'title' => '商品入力項目設定',
    'body'  => function () use ($v, $lang) { ?>

    <?php if (!empty($v->message)): ?>
      <div class="mb-4">
        <?php ui('alert', ['variant' => 'success', 'body' => $v->message]); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrftoken"
             value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">

      <!-- Built-in field visibility -->
      <div class="mb-6">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
          標準項目の表示 / 非表示
        </h4>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
          チェックを外すと、商品登録・編集フォームからその入力欄が非表示になります。
        </p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <?php foreach (\saso\settingAdmin\ItemFieldsView::FIELDS as $key => $label): ?>
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition
                          hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                   style="border-color:var(--saso-card-bdr)">
              <input type="checkbox"
                     name="field_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                     value="1"
                     class="h-4 w-4 rounded accent-[#3c50e0]"
                     <?php echo ($v->fieldVisible[$key] ?? true) ? 'checked' : ''; ?>>
              <span class="text-sm font-medium" style="color:var(--saso-text)">
                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Link to custom attribute definitions -->
      <div class="mb-6 rounded-lg border p-4" style="border-color:var(--saso-card-bdr);background:var(--saso-body)">
        <h4 class="mb-2 text-sm font-semibold" style="color:var(--saso-text)">
          カスタム属性（追加項目）
        </h4>
        <p class="mb-3 text-sm" style="color:var(--saso-text-sub)">
          標準項目に加え、独自の属性（素材、重量、原産国など）を自由に追加できます。
        </p>
        <a href="./itemAttribute/start/"
           class="btn btn-sm btn-secondary inline-flex items-center gap-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
          </svg>
          カスタム属性を管理する
        </a>
      </div>

      <?php ui('button', [
        'label'      => '設定を保存する',
        'type'       => 'submit',
        'variant'    => 'primary',
        'extraClass' => 'w-full justify-center',
      ]); ?>
    </form>

  <?php }, ]); ?>

<?php endif; ?>

<?php }; ?>
