<?php $this->title = '棚番作成'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">棚番作成</li>
</ol>
</nav>

<p>ラベルは<a href="./label/start/">ラベル寸法管理</a>で予め登録して下さい。</p>

<h2>一括作成</h2>
<p>
    各次元のminとmaxに数値を入力した場合、次元ごとのすべての組み合わせで連番が生成されます。
    <br>minに英字を入れるか、maxを空欄にした場合、その次元はminの値が固定値となります。
    <br>minが空欄だと以降の次元は無視されます。
    <br>英字は大文字に変換されます。
</p>
<p>例）1次元min: 0, max: 2、2次元min:A, max:空欄、3次元min: 0,max: 1の場合、
00-A-00,
00-A-01,
01-A-00,
01-A-01,
02-A-00,
02-A-01</p>
<div class="container-lg">
    <div class="row mb-1">
        <div class="col-lg-2 d-flex justify-content-evenly">
            1次元：
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension1min" maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min">
        </div>
        <div class="col-lg-1 d-flex justify-content-center">
            〜
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension1max" maxlength="2" pattern="^[0-9]+$" placeholder="max">
        </div>
    </div>
    <div class="row mb-1">
        <div class="col-lg-2 d-flex justify-content-evenly">
            2次元：
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension2min" maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min">
        </div>
        <div class="col-lg-1 d-flex justify-content-center">
            〜
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension2max" maxlength="2" pattern="^[0-9]+$" placeholder="max">
        </div>
    </div>
    <div class="row mb-1">
        <div class="col-lg-2 d-flex justify-content-evenly">
            3次元：
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension3min" maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min">
        </div>
        <div class="col-lg-1 d-flex justify-content-center">
            〜
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension3max" maxlength="2" pattern="^[0-9]+$" placeholder="max">
        </div>
    </div>
    <div class="row mb-1">
        <div class="col-lg-2 d-flex justify-content-evenly">
            4次元：
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension4min" maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min">
        </div>
        <div class="col-lg-1 d-flex justify-content-center">
            〜
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension4max" maxlength="2" pattern="^[0-9]+$" placeholder="max">
        </div>
    </div>
    <div class="row mb-1">
        <div class="col-lg-2 d-flex justify-content-evenly">
            5次元：
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension5min" maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min">
        </div>
        <div class="col-lg-1 d-flex justify-content-center">
            〜
        </div>
        <div class="col-lg-1">
            <input class="form-control" type="text" id="dimension5max" maxlength="2" pattern="^[0-9]+$" placeholder="max">
        </div>
    </div>
    <div class="row mb-1">
        <div class="d-grid col-lg-3 offset-lg-2">
            <input type="hidden" id="pageNumber" value="1">
            <button class="btn btn-primary" id="submitMultiButton">ラベルリスト作成</button>
        </div>
    </div>
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
    <div class="row mb-1">
        <div class="col-lg-2">
            <button class="btn btn-primary" id="submitSingleButton">ラベル作成</button>
        </div>
    </div>
</div>
</p>

<?php }; ?>
