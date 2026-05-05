<?php $this->title = '商品登録'; ?>
<?php $this->content = function ($v) { ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <li class="breadcrumb-item active" aria-current="page">商品登録</li>
  </ol>
</nav>

<form method="post" action="./item/add/" novalidate>

  <!-- 基本情報 -->
  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="bi bi-tag me-2 text-primary"></i>基本情報</h3>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <label for="itemName" class="form-label">商品名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="itemName" name="itemName"
                 maxlength="50" required placeholder="例：コットンTシャツ">
          <div class="form-text">50字以内</div>
        </div>
        <div class="col-md-4">
          <label for="price" class="form-label">価格</label>
          <div class="input-group">
            <span class="input-group-text">¥</span>
            <input type="text" class="form-control" id="price" name="price"
                   pattern="^[0-9,]+$" maxlength="11" placeholder="1,200">
          </div>
          <div class="form-text">9桁までの数（カンマ区切り可）</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 分類 -->
  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="bi bi-diagram-3 me-2 text-secondary"></i>分類</h3>
    </div>
    <div class="card-body">
      <div id="category">
        <div id="appendingParentInputs"></div>
        <button type="button" id="appendingParent" class="btn btn-outline-secondary btn-sm mb-2">
          <i class="bi bi-plus me-1"></i>ルート分類を追加
        </button>
        <div id="categoriesRoot" class="mt-2"></div>
        <div class="mt-2 d-flex align-items-center gap-2">
          <span class="text-muted small">選択中：</span>
          <span class="categoryPath categoryPathChangable fw-medium"></span>
          <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="deselectCategory">
            選択解除
          </button>
        </div>
      </div>
      <input type="hidden" name="categoryId" id="categoryId" value="">
    </div>
  </div>

  <!-- バリエーション -->
  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="bi bi-palette me-2 text-info"></i>バリエーション</h3>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="colorName" class="form-label">色 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="colorName" name="colorName"
                 required placeholder="例：赤,青,白">
          <div class="form-text">複数の場合は半角カンマ（,）で区切り。各50字以内。</div>
        </div>
        <div class="col-md-6">
          <label for="sizeName" class="form-label">サイズ <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="sizeName" name="sizeName"
                 required placeholder="例：S,M,L,XL">
          <div class="form-text">複数の場合は半角カンマ（,）で区切り。各50字以内。</div>
        </div>
        <div class="col-12">
          <div class="alert alert-info py-2 mb-0" role="note">
            <i class="bi bi-info-circle me-1"></i>
            色の数 × サイズの数 &le; 100
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 梱包 -->
  <div class="card mb-4">
    <div class="card-header">
      <h3 class="card-title"><i class="bi bi-box me-2 text-warning"></i>梱包</h3>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="d-flex align-items-center gap-2 mb-1">
            <input type="checkbox" class="form-check-input" id="pla" name="pla" value="1">
            <label class="form-check-label fw-medium" for="pla">プラ</label>
          </div>
          <input type="text" class="form-control" name="plaNote" maxlength="50" placeholder="備考（50字以内）">
        </div>
        <div class="col-md-6">
          <div class="d-flex align-items-center gap-2 mb-1">
            <input type="checkbox" class="form-check-input" id="paper" name="paper" value="1">
            <label class="form-check-label fw-medium" for="paper">紙</label>
          </div>
          <input type="text" class="form-control" name="paperNote" maxlength="50" placeholder="備考（50字以内）">
        </div>
      </div>
    </div>
  </div>

  <!-- 送信 -->
  <div class="d-flex justify-content-end gap-2">
    <a href="./" class="btn btn-outline-secondary">
      <i class="bi bi-x me-1"></i>キャンセル
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check me-1"></i>登録
    </button>
  </div>

</form>

<?php }; ?>
