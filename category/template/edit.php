<?php $this->title = '分類管理'; ?>
<?php $this->content = function ($v) { ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <li class="breadcrumb-item active" aria-current="page">分類管理</li>
  </ol>
</nav>

<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="bi bi-diagram-3 me-2 text-secondary"></i>分類ツリー</h3>
  </div>
  <div class="card-body">
    <div id="appendingParentInputs"></div>
    <button type="button" id="appendingParent" class="btn btn-outline-secondary btn-sm mb-3">
      <i class="bi bi-plus me-1"></i>ルート分類を追加
    </button>
    <div id="categoriesRoot"></div>
  </div>
</div>

<?php }; ?>
