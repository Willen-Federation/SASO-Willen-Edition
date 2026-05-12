<?php $this->title = '商品登録確認'; ?>
<?php $this->content = function($v) { ?>


<div class="mb-5 overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table" aria-label="登録内容確認">
    <thead>
      <tr>
        <th scope="col">商品番号</th>
        <th scope="col">商品名</th>
        <th scope="col">分類</th>
        <th scope="col">価格</th>
        <th scope="col">プラ</th>
        <th scope="col">役割名</th>
        <th scope="col">紙</th>
        <th scope="col">役割名</th>
        <th scope="col">登録日</th>
        <th scope="col">更新日</th>
        <th scope="col">色</th>
        <th scope="col">サイズ</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>-</td>
        <td><?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="categoryPath categoryPathChangable"><?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo number_format((int)($v->itemVar->price ?? 0)); ?></td>
        <td>
          <?php if($v->item->pla): ?>
            <img src="./img/pla.gif" alt="プラ" width="25" height="25">
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <?php if($v->item->paper): ?>
            <img src="./img/kami.gif" alt="紙" width="25" height="25">
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>-</td>
        <td>-</td>
        <td><?php echo htmlspecialchars($v->serializedColors, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($v->serializedSizes, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    </tbody>
  </table>
</div>

<div class="mx-auto max-w-xl rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">内容を確認して登録</h2>
  </div>
  <div class="px-5 py-5">
    <?php if(!$v->validFeaturesAmount): ?>
      <div class="ta-alert ta-alert-warning mb-4" role="alert">
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="1.5"/>
          <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span>色の数とサイズの数をかけて100を超えてはいけません。色数×サイズ数 &le; 100</span>
      </div>
    <?php endif; ?>

    <form method="post" action="./item/add/">
      <input type="hidden" name="itemNameConfirm" value="<?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="categoryIdConfirm" value="<?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="priceConfirm" value="<?php echo htmlspecialchars((string)$v->itemVar->price, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="colorNameConfirm" value="<?php echo htmlspecialchars($v->validFeaturesAmount ? $v->inputColors : '', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="sizeNameConfirm" value="<?php echo htmlspecialchars($v->validFeaturesAmount ? $v->inputSizes : '', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="plaConfirm" value="<?php echo $v->item->pla ? '1' : ''; ?>">
      <input type="hidden" name="plaNoteConfirm" value="<?php echo htmlspecialchars($v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="paperConfirm" value="<?php echo $v->item->paper ? '1' : ''; ?>">
      <input type="hidden" name="paperNoteConfirm" value="<?php echo htmlspecialchars($v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="mb-4">
        <label for="rc-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          商品名（50字以内）<span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input id="rc-name" type="text" name="itemName" class="form-input w-full"
               maxlength="50" required value="<?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="mb-4">
        <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">分類</label>
        <div id="category" class="rounded-lg border p-3" style="border-color:var(--saso-card-bdr)">
          <div id="appendingParentInputs"></div>
          <button type="button" id="appendingParent" class="btn btn-secondary btn-sm mb-2">+</button>
          <div id="categoriesRoot"></div>
          <p class="mt-2 text-sm" style="color:var(--saso-text-sub)">
            選択中の分類：<span class="categoryPath categoryPathChangable font-medium"><?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?></span>
            <button type="button" class="hidden ml-2 text-xs underline" id="deselectCategory" style="color:#3c50e0">選択解除</button>
          </p>
        </div>
        <input type="hidden" name="categoryId" id="categoryId" value="<?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="mb-4">
        <label for="rc-price" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">価格</label>
        <div class="relative">
          <span class="absolute left-3 top-2.5 text-sm" style="color:var(--saso-text-sub)">¥</span>
          <input id="rc-price" type="text" name="price" pattern="^[0-9,]+$"
                 class="form-input w-full pl-7" maxlength="11"
                 value="<?php echo htmlspecialchars((string)$v->itemVar->price, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="mb-4">
        <label for="rc-color" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          色<span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input id="rc-color" type="text" name="colorName" class="form-input w-full" required
               value="<?php echo htmlspecialchars($v->inputColors, ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="mb-5">
        <label for="rc-size" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          サイズ<span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input id="rc-size" type="text" name="sizeName" class="form-input w-full" required
               value="<?php echo htmlspecialchars($v->inputSizes, ENT_QUOTES, 'UTF-8'); ?>">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">色数×サイズ数 &le; 100</p>
      </div>

      <button type="submit" class="btn btn-primary w-full">登録</button>
    </form>
  </div>
</div>

<?php }; ?>
