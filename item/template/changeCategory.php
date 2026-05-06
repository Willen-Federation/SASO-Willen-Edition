<?php $this->content = function($v) { ?>

<div class="card mb-3">
  <div class="card-body">
    <h3 class="card-title">分類変更</h3>
    <p>
      <button type="button" class="btn btn-outline-primary" id="changeCategotyOfAnItem">
        <i class="bi bi-diagram-3 me-1"></i>分類一覧表示
      </button>
    </p>
    <div class="d-none" id="category">
      <div id="appendingParentInputs"></div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="appendingParent">+</button>
      <div id="categoriesRoot"></div>
      <p class="mt-3">新しい分類：<span class="categoryPath categoryPathChangable"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary d-none ms-2" id="deselectCategory">選択解除</button>
      </p>
      <p>もとの分類：<span class="categoryPath text-secondary"><?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?></span></p>
      <form method="post" action="./item/changeCategory/item/<?php echo (int)$v->item->id; ?>">
        <input type="hidden" name="categoryId" id="categoryId" value="<?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>変更</button>
      </form>
    </div>
  </div>
</div>

<?php }; ?>
