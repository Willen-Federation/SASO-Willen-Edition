<?php $this->title = '商品画像'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <?php if($v->archive->archive) { ?>
    <li class="breadcrumb-item"><a href="./archive/list">アーカイブ一覧</a></li>
  <?php } ?>
  <li class="breadcrumb-item"><a href="<?php echo 'item/start/item/' . $v->item->id; ?>">商品情報</a></li>
  <li class="breadcrumb-item active" aria-current="page">商品画像</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table">
      <?php ($v->inside)('item', 'head'); ?>
      <?php ($v->inside)('item', 'row', $v->item); ?>
    </table>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title"><?php echo $v->color->name . '(' . $v->color->code . ')'; ?></h3>
  </div>
  <div class="card-body text-center">
    <?php if(is_null($v->color->imageType)) { ?>
      <p class="text-secondary">画像はありません。</p>
    <?php }else{ ?>
      <img src="./image/display<?php echo '/item/'.$v->item->id. '/color/' . $v->color->code; ?>"
           alt="<?php echo $v->item->name . 'の' .$v->color->name . '(' . $v->color->code . ')'; ?>"
           class="img-fluid rounded">
    <?php } ?>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="./image/add<?php echo '/item/'.$v->item->id. '/color/' . $v->color->code; ?>" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="image-upload" class="form-label">画像ファイル</label>
        <input id="image-upload" type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
        <div class="form-hint">画像形式は jpeg, png, gif のみ。</div>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="ti ti-upload me-1"></i>アップロード
      </button>
    </form>
  </div>
</div>

<?php }; ?>
