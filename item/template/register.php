<?php $this->title = '商品登録'; ?>
<?php $this->content = function($v) { ?>


<div class="mx-auto max-w-xl rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">商品情報を入力</h2>
  </div>
  <div class="px-5 py-5">
    <form method="post" action="./item/add/">
      <div class="mb-4">
        <label for="reg-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          商品名（50字以内）<span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input id="reg-name" type="text" name="itemName" class="form-input w-full"
               maxlength="50" required value="">
      </div>

      <div class="mb-4">
        <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">分類</label>
        <div id="category" class="rounded-lg border p-3" style="border-color:var(--saso-card-bdr)">
          <div id="appendingParentInputs"></div>
          <button type="button" id="appendingParent" class="btn btn-secondary btn-sm mb-2">+</button>
          <div id="categoriesRoot"></div>
          <p class="mt-2 text-sm" style="color:var(--saso-text-sub)">
            選択中の分類：<span class="categoryPath categoryPathChangable font-medium"></span>
            <button type="button" class="hidden ml-2 text-xs underline" id="deselectCategory" style="color:#3c50e0">選択解除</button>
          </p>
        </div>
        <input type="hidden" name="categoryId" id="categoryId" value="">
      </div>

      <div class="mb-4">
        <label for="reg-price" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">価格</label>
        <div class="relative">
          <span class="absolute left-3 top-2.5 text-sm" style="color:var(--saso-text-sub)">¥</span>
          <input id="reg-price" type="text" name="price" pattern="^[0-9,]+$"
                 class="form-input w-full pl-7" maxlength="11" value=""
                 placeholder="0">
        </div>
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">9桁までの数。</p>
      </div>

      <div class="mb-4">
        <label for="reg-color" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          色<span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input id="reg-color" type="text" name="colorName" class="form-input w-full" required value="">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">複数の場合は半角カンマ（,）で区切ってください。各色50字まで。</p>
      </div>

      <div class="mb-4">
        <label for="reg-size" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          サイズ<span class="text-red-500" aria-hidden="true">*</span>
        </label>
        <input id="reg-size" type="text" name="sizeName" class="form-input w-full" required value="">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">複数の場合は半角カンマ（,）で区切ってください。色数×サイズ数 &le; 100</p>
      </div>

      <fieldset class="mb-5">
        <legend class="mb-2 text-sm font-medium" style="color:var(--saso-text)">梱包</legend>
        <div class="space-y-2">
          <label class="flex items-center gap-3">
            <input type="checkbox" name="pla" value="1" class="h-4 w-4 rounded accent-[#3c50e0]">
            <span class="text-sm" style="color:var(--saso-text)">プラ</span>
            <input type="text" name="plaNote" class="form-input flex-1" maxlength="50" placeholder="付記">
          </label>
          <label class="flex items-center gap-3">
            <input type="checkbox" name="paper" value="1" class="h-4 w-4 rounded accent-[#3c50e0]">
            <span class="text-sm" style="color:var(--saso-text)">紙</span>
            <input type="text" name="paperNote" class="form-input flex-1" maxlength="50" placeholder="付記">
          </label>
        </div>
      </fieldset>

      <button type="submit" class="btn btn-primary w-full">登録</button>
    </form>
  </div>
</div>

<?php }; ?>
