<?php $this->title = 'バーコード・商品検索'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">バーコード・商品検索</li>
</ol>

<div class="card mb-3" style="max-width: 48rem;">
  <div class="card-header">
    <h3 class="card-title"><i class="ti ti-search me-2"></i>商品を検索</h3>
  </div>
  <div class="card-body">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="barcodeTab" href="#barcodePane" data-bs-toggle="tab" role="tab">
          <i class="ti ti-barcode me-1"></i>バーコード検索
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="keywordTab" href="#keywordPane" data-bs-toggle="tab" role="tab">
          <i class="ti ti-abc me-1"></i>商品名検索
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="barcodePane" role="tabpanel">
        <div class="mt-3">
          <label for="barcodeInput" class="form-label">バーコード番号</label>
          <div class="input-group">
            <input id="barcodeInput" type="text" class="form-control"
                   maxlength="12" placeholder="12桁バーコードを入力"
                   autocomplete="off" inputmode="numeric">
            <a id="barcodeSubmit" class="btn btn-primary" href="" role="button">
              <i class="ti ti-search me-1"></i>検索
            </a>
          </div>
          <div class="form-text">商品バーコード（12桁）を入力すると棚番管理画面に移動します。</div>
        </div>
      </div>

      <div class="tab-pane fade" id="keywordPane" role="tabpanel">
        <div class="mt-3">
          <label for="keywordInput" class="form-label">商品名</label>
          <div class="input-group">
            <input id="keywordInput" type="text" class="form-control"
                   maxlength="50" placeholder="商品名キーワードを入力"
                   autocomplete="off">
            <button id="keywordSubmit" type="button" class="btn btn-primary">
              <i class="ti ti-search me-1"></i>検索
            </button>
          </div>
          <div class="form-text">商品名のキーワードで検索します。</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
