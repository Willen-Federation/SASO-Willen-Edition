<?php $this->title = '棚番作成'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? '棚番作成' : 'Shelf Creation'; ?></li>
  </ol>
</nav>

<div class="mb-4 flex gap-3">
  <a href="./shelf/simple/" class="btn-primary btn-sm flex items-center gap-1">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    <?php echo $lang === 'ja' ? '簡易設定（おすすめ）' : 'Quick Setup (Recommended)'; ?>
  </a>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

  <!-- 一括作成 -->
  <div class="card">
    <div class="card-header">
      <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '一括作成（連番）' : 'Bulk Create (Sequential)'; ?></h2>
    </div>
    <div class="card-body">
      <div class="alert alert-warning mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="text-xs">
          <?php echo $lang === 'ja'
            ? '各次元の min/max に数値を入れると全組み合わせを生成。英字を入れるか max を空欄にすると固定値になります。英字は大文字に変換されます。'
            : 'Enter numeric min/max for each dimension to generate all combinations. Letters or empty max = fixed value. Letters are uppercased.'; ?>
          <br>
          <?php echo $lang === 'ja' ? '例）1次元 0〜2、2次元 A（固定）、3次元 0〜1 → 00-A-00, 00-A-01, 01-A-00, ...' : 'e.g. dim1: 0-2, dim2: A(fixed), dim3: 0-1 → 00-A-00, 00-A-01, ...'; ?>
        </div>
      </div>

      <?php
      $dims = [
        ['1次元', '1st Dim'],
        ['2次元', '2nd Dim'],
        ['3次元', '3rd Dim'],
        ['4次元', '4th Dim'],
        ['5次元', '5th Dim'],
      ];
      foreach($dims as $i => $dim):
        $n = $i + 1;
      ?>
      <div class="mb-3 grid grid-cols-5 items-center gap-2">
        <label class="col-span-1 text-sm font-medium text-black dark:text-white">
          <?php echo $lang === 'ja' ? $dim[0] : $dim[1]; ?>
        </label>
        <input
          type="text"
          id="dimension<?php echo $n; ?>min"
          maxlength="2"
          pattern="^[0-9A-Za-z]+$"
          placeholder="min"
          class="form-input col-span-2 text-center py-2"
          aria-label="<?php echo ($lang === 'ja' ? $dim[0] : $dim[1]) . ' min'; ?>"
        >
        <span class="text-center text-body">〜</span>
        <input
          type="text"
          id="dimension<?php echo $n; ?>max"
          maxlength="2"
          pattern="^[0-9]+$"
          placeholder="max"
          class="form-input text-center py-2"
          aria-label="<?php echo ($lang === 'ja' ? $dim[0] : $dim[1]) . ' max'; ?>"
        >
      </div>
      <?php endforeach; ?>

      <input type="hidden" id="pageNumber" value="1">
      <button id="submitMultiButton" class="btn-primary w-full mt-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <?php echo $lang === 'ja' ? 'ラベルリスト作成' : 'Generate Label List'; ?>
      </button>
    </div>
  </div>

  <!-- 単一作成 -->
  <div class="card">
    <div class="card-header">
      <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '単一作成' : 'Single Create'; ?></h2>
    </div>
    <div class="card-body">
      <p class="mb-4 text-sm text-body dark:text-bodydark">
        <?php echo $lang === 'ja' ? '作成する棚番を直接入力します（半角英数・ハイフン）。英字は大文字に変換されます。' : 'Enter the shelf number directly (alphanumeric, hyphens). Letters are uppercased.'; ?>
      </p>
      <div class="mb-4">
        <label for="singleShelfNumber" class="form-label"><?php echo $lang === 'ja' ? '棚番号' : 'Shelf Number'; ?></label>
        <input
          type="text"
          id="singleShelfNumber"
          maxlength="15"
          pattern="^[0-9A-Za-z\-]+$"
          class="form-input"
          placeholder="<?php echo $lang === 'ja' ? '例: A-01-03' : 'e.g. A-01-03'; ?>"
          required
          aria-required="true"
        >
      </div>
      <button id="submitSingleButton" class="btn-primary w-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <?php echo $lang === 'ja' ? 'ラベル作成' : 'Create Label'; ?>
      </button>
    </div>
  </div>

</div>

<?php }; ?>
