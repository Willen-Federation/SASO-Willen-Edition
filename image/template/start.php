<?php $this->title = '商品画像'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <?php if($v->archive->archive) { ?>
    <li class="breadcrumb-item"><a href="./archive/list">アーカイブ一覧</a></li>
    <?php } ?>
    <li class="breadcrumb-item"><a href="<?php echo 'item/start/item/' . $v->item->id; ?>">商品情報</a></li>
    <li class="breadcrumb-item active" aria-current="page">商品画像</li>
  </ol>
</nav>

<div class="overflow-x-auto rounded-2xl border mb-4" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <?php ($v->inside)('item', 'head'); ?>
    <?php ($v->inside)('item', 'row', $v->item); ?>
  </table>
</div>

<p class="mb-3 text-sm" style="color:var(--saso-text-sub)">
  <?php echo htmlspecialchars($v->color->name, ENT_QUOTES, 'UTF-8'); ?>(<?php echo htmlspecialchars($v->color->code, ENT_QUOTES, 'UTF-8'); ?>)
</p>

<?php if(is_null($v->color->imageType)) { ?>
  <p class="text-sm mb-4" style="color:var(--saso-text-sub)">画像はありません。</p>
<?php }else{ ?>
  <img src="./image/display<?php echo '/item/'.$v->item->id. '/color/' . rawurlencode($v->color->code); ?>"
       alt="<?php echo htmlspecialchars($v->item->name . 'の' . $v->color->name . '(' . $v->color->code . ')', ENT_QUOTES, 'UTF-8'); ?>"
       class="mb-4 rounded-xl border max-w-sm" style="border-color:var(--saso-card-bdr)">
<?php } ?>

<form method="post" action="./image/add<?php echo '/item/'.$v->item->id. '/color/' . rawurlencode($v->color->code); ?>" enctype="multipart/form-data" class="flex items-center gap-3">
  <input type="file" name="image" class="text-sm" aria-label="画像ファイルを選択">
  <button type="submit" class="btn btn-primary btn-sm">アップロード</button>
</form>
<p class="mt-2 text-xs" style="color:var(--saso-text-sub)">画像形式はjpeg, png, gifのみ。</p>

<?php }; ?>
