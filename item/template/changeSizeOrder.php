<?php $this->content = function($v) { ?>

<div class="card mb-3">
  <div class="card-body">
    <h3 class="card-title">サイズ表示順変更</h3>
    <p class="text-secondary">変更後の順番を数値で指定してください。(昇順)</p>
    <form method="post" action="./item/changeSizeOrder/item/<?php echo $v->item->id; ?>">
      <div class="row g-2" style="max-width: 480px;">
        <?php foreach($v->sizes as $size){ ?>
          <div class="col-12">
            <div class="input-group">
              <span class="input-group-text" style="min-width: 8em;"><?php echo $size->name; ?></span>
              <input type="number" class="form-control"
                     name="size<?php echo $size->code; ?>"
                     min="0" max="99"
                     value="<?php echo $size->orderNumber; ?>">
            </div>
          </div>
        <?php } ?>
      </div>
      <button type="submit" class="btn btn-primary mt-3"><i class="ti ti-check me-1"></i>変更</button>
    </form>
  </div>
</div>

<?php }; ?>
