<?php $this->title = '色・サイズ追加'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item"><a href="./item/start/item/<?php echo (int)$v->item->id; ?>">商品情報</a></li>
  <li class="breadcrumb-item active" aria-current="page">色・サイズ追加</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table">
      <?php ($v->inside)('item', 'head'); ?>
      <?php ($v->inside)('item', 'row', $v->item); ?>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="./item/addFeature/item/<?php echo (int)$v->item->id; ?>">
      <div class="mb-3">
        <label for="addFeature-color" class="form-label">追加する色</label>
        <input type="text" id="addFeature-color" name="colorName" class="form-control">
      </div>
      <div class="mb-3">
        <label for="addFeature-size" class="form-label">追加するサイズ</label>
        <input type="text" id="addFeature-size" name="sizeName" class="form-control">
        <div class="form-hint">追加するものが複数ある場合は半角カンマ ( , ) で区切って下さい。</div>
      </div>
      <div class="alert alert-info mb-3" role="alert">
        各色・各サイズは50字まで。<br>
        色の数とサイズの数をかけて100を超えてはいけません。<br>
        色数 × サイズ数 ≦ 100
      </div>
      <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i>追加</button>
    </form>
  </div>
</div>

<?php }; ?>
