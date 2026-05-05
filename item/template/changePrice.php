<?php $this->content = function($v) { ?>

<div class="card mb-3">
  <div class="card-body">
    <h3 class="card-title">価格変更</h3>
    <form method="post" action="./item/changePrice/item/<?php echo (int)$v->item->id; ?>">
      <div class="mb-3">
        <label for="changePrice-price" class="form-label">価格</label>
        <div class="input-group">
          <input type="text" id="changePrice-price" name="price" class="form-control"
                 pattern="^[0-9,]+$" maxlength="11"
                 value="<?php echo htmlspecialchars((string)($v->itemVar->price ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          <span class="input-group-text">円</span>
        </div>
        <div class="form-hint">9桁までの数</div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>変更</button>
    </form>
  </div>
</div>

<?php }; ?>
