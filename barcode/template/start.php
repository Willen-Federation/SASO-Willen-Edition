<?php $this->title = 'バーコード検索'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">バーコード検索</li>
</ol>

<div class="card mb-3" style="max-width: 32rem;">
  <div class="card-header">
    <h3 class="card-title"><i class="ti ti-barcode me-2"></i>バーコードから在庫確認</h3>
  </div>
  <div class="card-body">
    <div class="mb-3">
      <label for="barcodeInput" class="form-label">バーコード番号</label>
      <div class="input-group">
        <input id="barcodeInput" type="text" class="form-control"
               maxlength="12" placeholder="12桁バーコードを入力"
               autocomplete="off" inputmode="numeric">
        <a id="barcodeSubmit" class="btn btn-primary" href="" role="button">
          <i class="ti ti-search me-1"></i>表示
        </a>
      </div>
      <div class="form-text">商品バーコード（12桁）を入力すると棚番管理画面に移動します。</div>
    </div>
  </div>
</div>

<?php }; ?>
