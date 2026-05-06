<?php $this->title = '棚番作成'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">棚番作成</li>
</ol>

<div class="alert alert-info mb-3" role="alert">
  ラベルは <a href="./label/start/" class="alert-link">ラベル寸法管理</a> で予め登録して下さい。
</div>

<div class="saso-action-row mb-3">
  <a href="./shelf/simple/" class="btn btn-outline-primary">
    <i class="bi bi-grid-3x3-gap me-2" aria-hidden="true"></i>棚番号ラベルシートを作成
  </a>
  <a href="./label/start/" class="btn btn-outline-secondary">
    <i class="bi bi-rulers me-2" aria-hidden="true"></i>ラベル寸法管理
  </a>
</div>

<!-- ── 単一作成（最も多いユースケース → 上に配置） ───────────────────── -->
<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title"><i class="bi bi-plus me-1"></i>単一作成</h3>
    <div class="card-options text-secondary small">よく使う形式をワンクリックで入力できます</div>
  </div>
  <div class="card-body">
    <p class="text-secondary mb-2">棚番を入力（半角英数・ハイフン）。英字は大文字に変換されます。</p>

    <!-- よく使うプリセット -->
    <div class="mb-3">
      <span class="text-muted small me-2">よく使う形式：</span>
      <?php
        $presets = [
          ['label' => 'A-01',    'value' => 'A-01'],
          ['label' => 'A-01-01', 'value' => 'A-01-01'],
          ['label' => '01-A-01', 'value' => '01-A-01'],
          ['label' => 'R01-S01', 'value' => 'R01-S01'],
          ['label' => 'W-01',    'value' => 'W-01'],
        ];
        foreach ($presets as $p):
      ?>
        <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 shelf-preset"
                data-value="<?php echo htmlspecialchars($p['value'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars($p['label'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="row g-2">
      <div class="col-md-3">
        <input type="text" id="singleShelfNumber" class="form-control form-control-lg"
               maxlength="15" pattern="^[0-9A-Za-z\-]+$"
               placeholder="例: A-01" required autofocus>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary btn-lg w-100" id="submitSingleButton">
          <i class="bi bi-printer me-1"></i>ラベル作成
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── 一括作成 ─────────────────────────────────────────────────────────── -->
<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title"><i class="bi bi-list me-1"></i>一括作成</h3>
  </div>
  <div class="card-body">
    <p class="text-secondary">
      各次元の min と max に数値を入力すると、全組み合わせで連番を生成します。<br>
      min に英字 or max を空欄にするとその次元は固定値になります。min が空欄だとそれ以降の次元は無視されます。英字は大文字に変換されます。
    </p>

    <!-- よく使うプリセット（一括） -->
    <div class="mb-3">
      <span class="text-muted small me-2">よく使うパターン：</span>
      <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 bulk-preset"
              data-dims='[["A",""],["1","10"],["",""],["",""],["",""]]'>
        列(A固定)×番号01〜10
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 bulk-preset"
              data-dims='[["A","E"],["1","5"],["",""],["",""],["",""]]'>
        列A〜E × 段01〜05
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 bulk-preset"
              data-dims='[["1","10"],["",""],["",""],["",""],["",""]]'>
        01〜10（番号のみ）
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 bulk-preset"
              data-dims='[["A","Z"],["",""],["",""],["",""],["",""]]'>
        A〜Z（英字のみ）
      </button>
    </div>

    <div class="alert alert-light py-2" role="note">
      <strong>例）</strong>1次元 min: 0, max: 2、2次元 min: A, max: 空欄、3次元 min: 0, max: 1<br>
      <code>00-A-00, 00-A-01, 01-A-00, 01-A-01, 02-A-00, 02-A-01</code>
    </div>

    <?php for ($d = 1; $d <= 5; $d++): ?>
          <div class="row g-2 align-items-center mb-2">
            <div class="col-md-2 col-form-label"><?php echo $d; ?>次元</div>
            <div class="col-md-2">
              <input type="text" id="dimension<?php echo $d; ?>min"
                     class="form-control" maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min">
            </div>
            <div class="col-auto text-secondary">〜</div>
            <div class="col-md-2">
              <input type="text" id="dimension<?php echo $d; ?>max"
                     class="form-control" maxlength="2" pattern="^[0-9]+$" placeholder="max">
            </div>
          </div>
    <?php endfor; ?>

    <div class="row mt-3">
      <div class="col-md-3 offset-md-2">
        <input type="hidden" id="pageNumber" value="1">
        <button class="btn btn-primary w-100" id="submitMultiButton">
          <i class="bi bi-list me-1"></i>ラベルリスト作成
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Single preset: fill the input and focus submit
document.querySelectorAll('.shelf-preset').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('singleShelfNumber').value = btn.dataset.value;
    document.getElementById('submitSingleButton').focus();
  });
});

// Bulk preset: fill all dimension inputs
document.querySelectorAll('.bulk-preset').forEach(btn => {
  btn.addEventListener('click', () => {
    const dims = JSON.parse(btn.dataset.dims);
    dims.forEach((pair, i) => {
      const n = i + 1;
      document.getElementById('dimension' + n + 'min').value = pair[0] ?? '';
      document.getElementById('dimension' + n + 'max').value = pair[1] ?? '';
    });
  });
});
</script>

<?php }; ?>
