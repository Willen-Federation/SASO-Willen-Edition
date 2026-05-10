<?php $this->title = '商品ラベル印刷'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">商品ラベル印刷</li>
  </ol>
</nav>

<div class="mb-5 rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="flex items-center justify-between gap-3 border-b px-5 py-4"
       style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold" style="color:var(--saso-text)">以下の商品ラベルを印刷します</h3>
    <form method="post" action="./label/deleteAll/" class="m-0">
      <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
      <input type="hidden" name="deleteAll" value="deleteAll">
      <button id="deleteAllItemLabels" type="button" class="btn btn-warning btn-sm">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>商品ラベル全削除</span>
      </button>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="ta-table" aria-label="印刷対象の商品ラベル一覧">
      <thead>
        <tr>
          <th scope="col">商品情報</th>
          <th scope="col">商品詳細番号</th>
          <th scope="col" class="text-right">ラベル枚数</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($v->labelCaches as $labelCache) { if($labelCache) { ?>
        <tr>
          <td>
            <a href="./item/start/item/<?php echo (int)$labelCache->feature->item->id; ?>/color/<?php echo rawurlencode($labelCache->feature->color->code); ?>/size/<?php echo rawurlencode($labelCache->feature->size->code); ?>/action/label"
               style="color:#3c50e0" class="hover:underline">
              <span id="longName<?php echo htmlspecialchars($labelCache->feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?>"><?php
                echo htmlspecialchars($labelCache->feature->item->name, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars($labelCache->feature->color->name, ENT_QUOTES, 'UTF-8'); ?>(<?php echo htmlspecialchars($labelCache->feature->color->code, ENT_QUOTES, 'UTF-8'); ?>)<?php echo htmlspecialchars($labelCache->feature->size->name, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          </td>
          <td><span class="fullCode font-mono text-sm"><?php echo htmlspecialchars($labelCache->feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td class="text-right"><?php echo (int)$labelCache->amount; ?></td>
        </tr>
      <?php } } ?>
      </tbody>
    </table>
  </div>
</div>

<form id="labelPrint" target="_blank" method="post" action="./label/outputPdf/">
  <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="px-5 py-5">
      <p class="mb-3 text-sm font-medium" style="color:var(--saso-text)">ラベルを選択してください。</p>
      <ul class="mb-4 space-y-1">
        <?php ($v->inside)('label', 'list'); ?>
      </ul>
      <button type="submit" class="btn btn-primary">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <rect x="6" y="14" width="12" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/>
        </svg>
        <span>PDF出力</span>
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
