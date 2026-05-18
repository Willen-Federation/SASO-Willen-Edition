<?php $this->title = '商品情報'; ?>
<?php $this->content = function($v) { ?>


<div class="mb-4 overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <?php ($v->inside)('item', 'head'); ?>
    <?php ($v->inside)('item', 'row', $v->item); ?>
  </table>
</div>

<?php
  $hasJan  = !empty($v->item->janCode);
  $hasIsbn = !empty($v->item->isbnCode);
  $hasNote = !empty($v->item->note);
?>
<?php if($hasJan || $hasIsbn || $hasNote): ?>
<div class="mb-4 rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-3" style="border-color:var(--saso-card-bdr)">
    <h2 class="text-sm font-semibold" style="color:var(--saso-text)">識別コード・備考</h2>
  </div>
  <div class="px-5 py-4">
    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
      <?php if($hasJan): ?>
        <dt class="font-medium" style="color:var(--saso-text)">JANコード</dt>
        <dd class="font-mono" style="color:var(--saso-text-sub)"><?php echo htmlspecialchars((string)$v->item->janCode, ENT_QUOTES, 'UTF-8'); ?></dd>
      <?php endif; ?>
      <?php if($hasIsbn): ?>
        <dt class="font-medium" style="color:var(--saso-text)">ISBNコード</dt>
        <dd class="font-mono" style="color:var(--saso-text-sub)"><?php echo htmlspecialchars((string)$v->item->isbnCode, ENT_QUOTES, 'UTF-8'); ?></dd>
      <?php endif; ?>
      <?php if($hasNote): ?>
        <dt class="font-medium" style="color:var(--saso-text)">その他備考</dt>
        <dd class="whitespace-pre-wrap break-words" style="color:var(--saso-text-sub)"><?php echo nl2br(htmlspecialchars((string)$v->item->note, ENT_QUOTES, 'UTF-8')); ?></dd>
      <?php endif; ?>
    </dl>
  </div>
</div>
<?php endif; ?>

<?php if(!$v->archive->archive): ?>
<div class="mb-5 flex flex-wrap gap-3">
  <a href="./item/edit/item/<?php echo (int)$v->item->id; ?>/" class="btn btn-secondary btn-sm">商品情報編集</a>
  <a href="./item/addFeature/item/<?php echo (int)$v->item->id; ?>/" class="btn btn-secondary btn-sm">色・サイズ追加</a>
</div>
<?php else: ?>
<div class="mb-5 rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="px-5 py-4">
    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
      <dt class="font-medium" style="color:var(--saso-text)">アーカイブ理由</dt>
      <dd style="color:var(--saso-text-sub)"><?php echo htmlspecialchars($v->archive->archiveNote, ENT_QUOTES, 'UTF-8'); ?></dd>
      <dt class="font-medium" style="color:var(--saso-text)">アーカイブ日時</dt>
      <dd style="color:var(--saso-text-sub)"><?php echo htmlspecialchars($v->archive->archiveAt->format('Y年m月d日 H時i分'), ENT_QUOTES, 'UTF-8'); ?></dd>
    </dl>
    <form method="post" action="<?php echo './item/reproduction/item/' . (int)$v->item->id; ?>" class="mt-3">
      <input type="hidden" name="isPost" value="true">
      <button type="submit" class="btn btn-primary btn-sm">復刻</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="rounded-2xl border overflow-hidden" style="border-color:var(--saso-card-bdr)">
  <div class="flex items-center justify-between gap-3 border-b px-5 py-4"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">数量・棚番管理</h2>
    <?php if(!$v->archive->archive): ?>
    <label class="flex cursor-pointer items-center gap-2 text-sm" style="color:var(--saso-text-sub)">
      <span>棚卸を許可</span>
      <button role="switch" type="button" id="inventoryButtonDisplayButton"
              aria-checked="false"
              class="saso-toggle relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline focus-visible:outline-2 focus-visible:[outline-color:var(--saso-ctrl-focus)] bg-gray-200 dark:bg-gray-700"
              onclick="this.setAttribute('aria-checked', this.getAttribute('aria-checked')==='false'?'true':'false')">
        <span class="saso-toggle-thumb pointer-events-none inline-block h-4 w-4 translate-x-0 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
      </button>
      <style>
        .saso-toggle[aria-checked="true"] { background-color: var(--saso-ctrl-focus) !important; }
        .saso-toggle[aria-checked="true"] .saso-toggle-thumb { transform: translateX(1rem); }
      </style>
    </label>
    <?php endif; ?>
  </div>
  <div class="overflow-x-auto">
    <table class="ta-table" aria-label="数量・棚番一覧">
      <thead>
        <tr>
          <th scope="col">商品詳細番号</th>
          <th scope="col">色</th>
          <th scope="col">サイズ</th>
          <th scope="col">数量</th>
          <?php if(!$v->archive->archive): ?>
          <th scope="col">入庫</th>
          <th scope="col">出庫</th>
          <th scope="col">棚卸</th>
          <?php endif; ?>
          <th scope="col">棚番</th>
          <th scope="col">ラベル枚数</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($v->quantityLogsGen as $quantityLogs):
        $feature = $quantityLogs->feature;
        $isFocused = fn(string $action) => $feature->color->code === $v->color && $feature->size->code === $v->size && $v->action === $action;
      ?>
      <tr>
        <td class="featureCode font-mono text-sm">
          <?php if($quantityLogs->isInventoried()): ?>
            <a href="<?php echo './item/history/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>"
               style="color:var(--saso-ctrl-focus)" class="hover:underline">
              <?php echo htmlspecialchars($feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php else: ?>
            <?php echo htmlspecialchars($feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?php echo './image/start/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code); ?>"
             style="color:var(--saso-ctrl-focus)" class="hover:underline">
            <?php echo htmlspecialchars($feature->color->name.'('.$feature->color->code.')', ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </td>
        <td><?php echo htmlspecialchars($feature->size->name, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="number featureSum" id="sumof<?php echo htmlspecialchars($feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?>">
          <?php if($quantityLogs->isInventoried()){ echo (int)$quantityLogs->sum(); } ?>
        </td>

        <?php if(!$v->archive->archive): ?>
          <?php if($quantityLogs->isInventoried()): ?>
            <td>
              <form method="post" action="<?php echo './item/stock/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>">
                <div class="flex">
                  <input type="number" name="amount"
                         class="form-input w-20 rounded-r-none <?php echo $isFocused('stock') ? 'ring-2 ring-[#3c50e0]' : ''; ?>"
                         aria-label="入庫数量" max="9999" min="1" required>
                  <input type="hidden" name="kind" value="stock">
                  <button type="submit" class="btn btn-secondary rounded-l-none stockButton">入庫</button>
                </div>
              </form>
            </td>
            <td>
              <form method="post" action="<?php echo './item/shipment/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>">
                <div class="flex">
                  <input id="shipmentof<?php echo htmlspecialchars($feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?>"
                         type="number" name="amount"
                         class="form-input w-20 rounded-r-none <?php echo $isFocused('shipment') ? 'ring-2 ring-[#3c50e0]' : ''; ?>"
                         aria-label="出庫数量" max="9999" min="1" required>
                  <input type="hidden" name="kind" value="shipment">
                  <button type="submit" class="btn btn-secondary rounded-l-none shipmentButton">出庫</button>
                </div>
              </form>
            </td>
            <td>
              <form method="post" action="<?php echo './item/inventory/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>">
                <div class="flex">
                  <input type="number" name="amount"
                         class="form-input w-20 rounded-r-none <?php echo $isFocused('inventory') ? 'ring-2 ring-[#3c50e0]' : ''; ?>"
                         aria-label="棚卸数量" max="9999" min="0" required>
                  <input type="hidden" name="kind" value="inventory">
                  <button type="submit" class="btn btn-secondary rounded-l-none inventoryButton" disabled>棚卸</button>
                </div>
              </form>
            </td>
          <?php else: ?>
            <td></td>
            <td></td>
            <td>
              <form method="post" action="<?php echo './item/inventory/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>">
                <div class="flex">
                  <input type="number" name="amount"
                         class="form-input w-20 rounded-r-none <?php echo $isFocused('inventory') ? 'ring-2 ring-[#3c50e0]' : ''; ?>"
                         aria-label="棚卸数量" max="9999" min="0" required>
                  <input type="hidden" name="kind" value="inventory">
                  <button type="submit" class="btn btn-secondary rounded-l-none inventoryButton">棚卸</button>
                </div>
              </form>
            </td>
          <?php endif; ?>
        <?php endif; ?>

        <td>
          <form method="post" action="<?php echo './shelf/put/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>">
            <div class="flex">
              <input type="text" name="number" value="<?php echo htmlspecialchars($feature->shelf?->number ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                     class="form-input w-24 rounded-r-none <?php echo $isFocused('shelf') ? 'ring-2 ring-[#3c50e0]' : ''; ?>"
                     aria-label="棚番号" pattern="^[0-9A-Za-z\-]+$" maxlength="15" required>
              <button type="submit" class="btn btn-secondary rounded-l-none">棚置</button>
            </div>
          </form>
        </td>
        <td>
          <form method="post" action="<?php echo './label/select/item/'.(int)$feature->item->id.'/color/'.rawurlencode($feature->color->code).'/size/'.rawurlencode($feature->size->code); ?>">
            <div class="flex">
              <input type="number" name="amount"
                     value="<?php echo $feature->labelAmount === 0 ? '' : (int)$feature->labelAmount; ?>"
                     class="form-input w-20 rounded-r-none labelSheetsInput <?php echo $isFocused('label') ? 'ring-2 ring-[#3c50e0]' : ''; ?>"
                     aria-label="ラベル枚数" min="0" max="100">
              <button type="submit" class="btn btn-secondary rounded-l-none">追加</button>
            </div>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="labelSheetsAmount" class="hidden"><?php echo (int)$v->labelSheetsAmount; ?></div>
<div id="labelSheetsAmountMax" class="hidden"><?php echo (int)$v->labelSheetsAmountMax; ?></div>

<?php }; ?>
