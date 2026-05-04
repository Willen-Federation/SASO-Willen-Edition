<?php $this->title = '棚番作成'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">棚番作成</li>
</ol>

<div class="alert alert-info mb-3" role="alert">
  ラベルは <a href="./label/start/" class="alert-link">ラベル寸法管理</a> で予め登録して下さい。
</div>

<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title">一括作成</h3>
  </div>
  <div class="card-body">
    <p class="text-secondary">
      各次元のminとmaxに数値を入力した場合、次元ごとのすべての組み合わせで連番が生成されます。<br>
      minに英字を入れるか、maxを空欄にした場合、その次元はminの値が固定値となります。<br>
      minが空欄だと以降の次元は無視されます。<br>
      英字は大文字に変換されます。
    </p>
    <div class="alert alert-light" role="note">
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
          <i class="ti ti-list me-1"></i>ラベルリスト作成
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">単一作成</h3>
  </div>
  <div class="card-body">
    <p class="text-secondary">作成する棚版を入力（半角英数、ハイフン）。英字は大文字に変換されます。</p>
    <div class="row g-2">
      <div class="col-md-3">
        <input type="text" id="singleShelfNumber" class="form-control" maxlength="15" pattern="^[0-9A-Za-z\-]+$" required>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" id="submitSingleButton">
          <i class="ti ti-plus me-1"></i>ラベル作成
        </button>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
