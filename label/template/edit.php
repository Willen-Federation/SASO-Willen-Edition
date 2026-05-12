<?php $this->title = 'ラベル寸法管理'; ?>
<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
?>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

  <!-- Label size list -->
  <div class="card">
    <div class="card-header">
      <h2 class="font-semibold text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'ラベル寸法一覧' : 'Label Sizes'; ?>
      </h2>
    </div>
    <div class="card-body" id="labelSizeList">
      <ul class="flex flex-col gap-2">
        <?php ($v->inside)('label', 'list'); ?>
        <li class="flex items-center gap-2">
          <input type="radio" name="labelName" id="newLabelSize" value="(new)">
          <label for="newLabelSize" class="text-sm cursor-pointer text-gray-700 dark:text-gray-300">
            <?php echo $lang === 'ja' ? '新規作成' : 'Create new'; ?>
          </label>
        </li>
      </ul>
      <div class="mt-4">
        <a class="text-sm text-brand-500 cursor-pointer hover:underline" id="labelSizeDeleteDisplay">
          <?php echo $lang === 'ja' ? '削除ボタンを表示' : 'Show delete button'; ?>
        </a>
      </div>
      <div id="labelSizeDeleteButton" class="hidden mt-3">
        <button type="button" id="labelSizeDelete" class="btn btn-sm bg-error-500 text-white hover:bg-error-600">
          <?php echo $lang === 'ja' ? '選択したサイズを削除' : 'Delete selected'; ?>
        </button>
      </div>
    </div>
  </div>

  <!-- New label size form (shown when "Create new" is selected) -->
  <div id="newLabelSizeForm" class="card hidden">
    <div class="card-header">
      <h2 class="font-semibold text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'ラベル寸法登録' : 'Register Label Size'; ?>
      </h2>
    </div>
    <div class="card-body">
      <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        <?php echo $lang === 'ja'
          ? '寸法を単位「mm」で入力してください（小数第1位まで、用紙サイズ：A4）'
          : 'Enter dimensions in mm (1 decimal place, A4 paper size)'; ?>
      </p>
      <form method="post" action="./label/add/" class="space-y-4">
        <div>
          <label class="form-label" for="newLabelName">
            <?php echo $lang === 'ja' ? 'ラベル名（半角英数・ハイフン・アンダーバー）' : 'Label name (alphanumeric/hyphens/underscores)'; ?>
          </label>
          <input id="newLabelName" type="text" name="labelName"
                 pattern="^[0-9a-zA-Z_\-]+$" maxlength="50" required
                 class="form-input">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '幅 (mm)' : 'Width (mm)'; ?></label>
            <input type="number" name="width" step="0.1" min="0" max="999.9" required class="form-input">
          </div>
          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '高さ (mm)' : 'Height (mm)'; ?></label>
            <input type="number" name="height" step="0.1" min="0" max="999.9" required class="form-input">
          </div>
          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '左余白 (mm)' : 'Left margin (mm)'; ?></label>
            <input type="number" name="marginLeft" step="0.1" min="0" max="999.9" required class="form-input">
          </div>
          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '上余白 (mm)' : 'Top margin (mm)'; ?></label>
            <input type="number" name="marginTop" step="0.1" min="0" max="999.9" required class="form-input">
          </div>
          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '横間隔 (mm)' : 'H. gap (mm)'; ?></label>
            <input type="number" name="intervalColumn" step="0.1" min="0" max="999.9" required class="form-input">
          </div>
          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '縦間隔 (mm)' : 'V. gap (mm)'; ?></label>
            <input type="number" name="intervalRow" step="0.1" min="0" max="999.9" required class="form-input">
          </div>
        </div>
        <button id="newLabelSizeSubmit" type="submit" class="btn btn-primary w-full">
          <?php echo $lang === 'ja' ? '登録' : 'Register'; ?>
        </button>
      </form>
    </div>
  </div>

</div>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
