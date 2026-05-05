<?php $this->content = function($v) { ?>

<div class="card">
  <div class="card-body">
    <h3 class="card-title">アーカイブ</h3>
    <form method="post" action="./item/archive/item/<?php echo (int)$v->item->id; ?>">
      <div class="mb-3">
        <label for="archive-note" class="form-label">アーカイブ理由</label>
        <input type="text" id="archive-note" name="archiveNote" class="form-control" maxlength="50">
      </div>
      <button type="submit" class="btn btn-warning"><i class="ti ti-archive me-1"></i>アーカイブ</button>
    </form>
  </div>
</div>

<?php }; ?>
