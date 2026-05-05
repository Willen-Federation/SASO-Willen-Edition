<?php $this->title = '入出庫履歴'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<?php if($v->archive->archive) { ?>
<li class="breadcrumb-item"><a href="./archive/list">アーカイブ一覧</a></li>
<?php } ?>
<li class="breadcrumb-item"><a href="<?php echo 'item/start/item/' . (int)$v->item->id; ?>">商品情報</a></li>
<li class="breadcrumb-item active">入出庫履歴</li>
</ol>
</nav>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table" aria-label="商品情報">
      <?php ($v->inside)('item', 'head'); ?>
      <?php ($v->inside)('item', 'row', $v->item); ?>
    </table>
  </div>
</div>

<dl class="row mb-3">
    <dt class="col-sm-3">カラー</dt>
    <dd class="col-sm-9"><?php echo htmlspecialchars($v->color->name, ENT_QUOTES, 'UTF-8'); ?>(<?php echo htmlspecialchars($v->color->code, ENT_QUOTES, 'UTF-8'); ?>)</dd>
    <dt class="col-sm-3">サイズ</dt>
    <dd class="col-sm-9"><?php echo htmlspecialchars($v->size->name, ENT_QUOTES, 'UTF-8'); ?></dd>
</dl>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped table-hover table-vcenter card-table" aria-label="入出庫履歴">
      <thead>
        <tr>
          <th scope="col">日時</th>
          <th scope="col">入出庫数</th>
          <th scope="col">摘要</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($v->quantityLogs as $log): ?>
        <tr>
          <td><?php echo $log->changeAt->format('Y年m月d日 H時i分'); ?></td>
          <td class="number"><?php echo (int)$log->fluctuation; ?></td>
          <td>
            <?php
            if($log->isInventory){ echo '棚卸'; }
            elseif($log->fluctuation < 0){ echo '出庫'; }
            else{ echo '入庫'; }
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="fw-bold">
          <td>合計</td>
          <td class="number"><?php echo (int)$v->quantityLogs->sum(); ?></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php }; ?>
