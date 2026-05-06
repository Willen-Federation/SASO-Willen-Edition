<?php $this->title = '色・サイズ追加確認'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item"><a href="./item/start/item/<?php echo (int)$v->item->id; ?>">商品情報</a></li>
  <li class="breadcrumb-item"><a href="./item/addFeature/item/<?php echo (int)$v->item->id; ?>">色・サイズ追加</a></li>
  <li class="breadcrumb-item active" aria-current="page">色・サイズ追加確認</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table">
      <?php ($v->inside)('item', 'head'); ?>
      <?php ($v->inside)('item', 'row', $v->item); ?>
    </table>
  </div>
</div>

<?php if(!$v->isValidAmount) { ?>
  <div class="alert alert-warning" role="note">
    追加後の色の数とサイズの数をかけて100を超えてはいけません。<br>
    色数 × サイズ数 ≦ 100
  </div>
<?php } ?>

<div class="card">
  <div class="card-body">
    <?php if(!empty($v->inputColors)) { ?>
      <p class="mb-1">追加する色：<strong><?php echo htmlspecialchars($v->serializedColors, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php } ?>
    <?php if(!empty($v->inputSizes)) { ?>
      <p class="mb-3">追加するサイズ：<strong><?php echo htmlspecialchars($v->serializedSizes, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php } ?>

    <form method="post" action="./item/addFeature/item/<?php echo (int)$v->item->id; ?>">
      <input type="hidden" name="colorNameConfirm" value="<?php echo htmlspecialchars($v->isValidAmount?$v->inputColors:'', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="sizeNameConfirm" value="<?php echo htmlspecialchars($v->isValidAmount?$v->inputSizes:'', ENT_QUOTES, 'UTF-8'); ?>">

      <div class="mb-3">
        <label for="addFeatConfirm-color" class="form-label">追加する色</label>
        <input type="text" id="addFeatConfirm-color" name="colorName" class="form-control"
               value="<?php echo htmlspecialchars($v->inputColors, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="mb-3">
        <label for="addFeatConfirm-size" class="form-label">追加するサイズ</label>
        <input type="text" id="addFeatConfirm-size" name="sizeName" class="form-control"
               value="<?php echo htmlspecialchars($v->inputSizes, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-hint">追加するものが複数ある場合は半角カンマ ( , ) で区切って下さい。</div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-plus me-1"></i>追加</button>
    </form>
  </div>
</div>

<?php }; ?>
