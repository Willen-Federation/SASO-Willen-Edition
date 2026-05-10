<?php $this->title = '入出庫履歴'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <?php if($v->archive->archive) { ?>
    <li class="breadcrumb-item"><a href="./archive/list">アーカイブ一覧</a></li>
    <?php } ?>
    <li class="breadcrumb-item"><a href="<?php echo 'item/start/item/' . $v->item->id; ?>">商品情報</a></li>
    <li class="breadcrumb-item active" aria-current="page">入出庫履歴</li>
  </ol>
</nav>

<div class="overflow-x-auto rounded-2xl border mb-4" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <?php ($v->inside)('item', 'head'); ?>
    <?php ($v->inside)('item', 'row', $v->item); ?>
  </table>
</div>

<dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4 max-w-xs">
  <dt class="font-medium" style="color:var(--saso-text)">カラー</dt>
  <dd style="color:var(--saso-text-sub)"><?php echo htmlspecialchars($v->color->name, ENT_QUOTES, 'UTF-8'); ?>(<?php echo htmlspecialchars($v->color->code, ENT_QUOTES, 'UTF-8'); ?>)</dd>
  <dt class="font-medium" style="color:var(--saso-text)">サイズ</dt>
  <dd style="color:var(--saso-text-sub)"><?php echo htmlspecialchars($v->size->name, ENT_QUOTES, 'UTF-8'); ?></dd>
</dl>

<div class="overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <thead>
      <tr>
        <th scope="col">日時</th>
        <th scope="col" class="text-right">入出庫数</th>
        <th scope="col">摘要</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($v->quantityLogs as $log): ?>
      <tr>
        <td class="text-sm" style="color:var(--saso-text-sub)"><?php echo $log->changeAt->format('Y年m月d日 H時i分'); ?></td>
        <td class="text-right font-mono"><?php echo $log->fluctuation; ?></td>
        <td class="text-sm">
          <?php
          if($log->isInventory){ echo '棚卸'; }
          elseif($log->fluctuation < 0){ echo '出庫'; }
          else{ echo '入庫'; }
          ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="font-semibold">
        <td>合計</td>
        <td class="text-right font-mono"><?php echo $v->quantityLogs->sum(); ?></td>
        <td></td>
      </tr>
    </tbody>
  </table>
</div>

<?php }; ?>
