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

      <?php if ($v->fieldVisible['price'] ?? true): ?>
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
      <?php endif; ?>

      <?php if ($v->fieldVisible['color'] ?? true): ?>
      <div class="mb-4">
        <label for="reg-color" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">色</label>
        <input id="reg-color" type="text" name="colorName" class="form-input w-full" value="">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。複数の場合は半角カンマ（,）で区切ってください。各色50字まで。</p>
      </div>
      <?php endif; ?>

      <?php if ($v->fieldVisible['size'] ?? true): ?>
      <div class="mb-4">
        <label for="reg-size" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">サイズ</label>
        <input id="reg-size" type="text" name="sizeName" class="form-input w-full" value="">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。複数の場合は半角カンマ（,）で区切ってください。色数×サイズ数 &le; 100</p>
      </div>
      <?php endif; ?>

      <?php if (($v->fieldVisible['jan'] ?? true) || ($v->fieldVisible['isbn'] ?? true)): ?>
      <!-- JAN/EAN or ISBN auto-fill hint -->
      <div id="code-lookup-status" class="hidden mb-3 rounded-lg px-3 py-2 text-sm" role="status"></div>
      <?php endif; ?>

      <?php if ($v->fieldVisible['jan'] ?? true): ?>
      <div class="mb-4">
        <label for="reg-jan" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">JANコード</label>
        <div class="relative">
          <input id="reg-jan" type="text" name="janCode" class="form-input w-full pr-9"
                 maxlength="32" value="" inputmode="numeric" autocomplete="off"
                 placeholder="例：4912345678904">
          <span id="jan-spinner" class="absolute right-2.5 top-2.5 hidden h-4 w-4 animate-spin rounded-full
                border-2 border-[#3c50e0] border-t-transparent" aria-hidden="true"></span>
        </div>
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。JAN/EANのバーコード番号を入力すると商品名などが自動入力されます。</p>
      </div>
      <?php endif; ?>

      <?php if ($v->fieldVisible['isbn'] ?? true): ?>
      <div class="mb-4">
        <label for="reg-isbn" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">ISBNコード</label>
        <div class="relative">
          <input id="reg-isbn" type="text" name="isbnCode" class="form-input w-full pr-9"
                 maxlength="32" value="" autocomplete="off"
                 placeholder="例：9784101010014">
          <span id="isbn-spinner" class="absolute right-2.5 top-2.5 hidden h-4 w-4 animate-spin rounded-full
                border-2 border-[#3c50e0] border-t-transparent" aria-hidden="true"></span>
        </div>
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。書籍のISBN-13を入力すると書誌情報が自動入力されます。</p>
      </div>
      <?php endif; ?>

      <?php if ($v->fieldVisible['packing'] ?? true): ?>
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
      <?php endif; ?>

      <?php if ($v->fieldVisible['note'] ?? true): ?>
      <div class="mb-5">
        <label for="reg-note" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">その他の備考</label>
        <textarea id="reg-note" name="note" class="form-input w-full" rows="3" maxlength="255"
                  placeholder="自由記述（255字以内）"></textarea>
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。プラ／紙の付記とは別の自由記述欄です。</p>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary w-full">登録</button>
    </form>
  </div>
</div>

<?php
// Only emit the auto-fill script if at least one code field is visible.
$janVisible  = $v->fieldVisible['jan']  ?? true;
$isbnVisible = $v->fieldVisible['isbn'] ?? true;
if ($janVisible || $isbnVisible):
?>
<script>
(function () {
  const LOOKUP_URL = './item/lookupCode.json';

  // Targets for filling from code lookup result.
  const nameField  = document.getElementById('reg-name');
  const priceField = document.getElementById('reg-price');

  function showStatus(msg, ok) {
    const el = document.getElementById('code-lookup-status');
    if (!el) return;
    el.textContent = msg;
    el.className = 'mb-3 rounded-lg px-3 py-2 text-sm ' + (ok
      ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
      : 'bg-amber-50  text-amber-700  dark:bg-amber-900/20  dark:text-amber-400');
    el.classList.remove('hidden');
  }

  function hideStatus() {
    const el = document.getElementById('code-lookup-status');
    if (el) el.classList.add('hidden');
  }

  function setSpinner(id, visible) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle('hidden', !visible);
  }

  // Debounce helper — waits `delay` ms after the last call.
  function debounce(fn, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  /**
   * Fill the name field (and optionally price) from lookup data.
   * Only pre-fills if the field is currently empty so we do not
   * overwrite data the user has already typed.
   */
  function applyData(data) {
    if (!data) return false;

    let filled = false;
    if (nameField && !nameField.value.trim() && data.name) {
      nameField.value = data.name;
      nameField.dispatchEvent(new Event('input'));
      filled = true;
    }
    // Note field (備考): append author/brand info if available.
    const noteField = document.getElementById('reg-note');
    const extra = [data.author, data.brand, data.publisher].filter(Boolean).join(' / ');
    if (noteField && !noteField.value.trim() && extra) {
      noteField.value = extra;
    }
    return filled;
  }

  async function lookup(code, spinnerId) {
    if (!code || !/^[0-9X]{8,14}$/i.test(code)) return;
    hideStatus();
    setSpinner(spinnerId, true);
    try {
      const res  = await fetch(LOOKUP_URL + '?code=' + encodeURIComponent(code));
      const json = await res.json();
      if (json.error || !json.data) {
        showStatus('商品情報が見つかりませんでした。', false);
        return;
      }
      const ok = applyData(json.data);
      if (ok) {
        showStatus('商品情報を自動入力しました。内容を確認してください。', true);
      } else {
        showStatus('商品名はすでに入力されています。他のフィールドへのデータ: ' + (json.data.name || '—'), false);
      }
    } catch (_) {
      showStatus('自動入力の取得に失敗しました。', false);
    } finally {
      setSpinner(spinnerId, false);
    }
  }

  const debouncedLookup = debounce(lookup, 600);

  <?php if ($janVisible): ?>
  const janInput = document.getElementById('reg-jan');
  if (janInput) {
    janInput.addEventListener('input', function () {
      debouncedLookup(this.value.trim(), 'jan-spinner');
    });
  }
  <?php endif; ?>

  <?php if ($isbnVisible): ?>
  const isbnInput = document.getElementById('reg-isbn');
  if (isbnInput) {
    isbnInput.addEventListener('input', function () {
      debouncedLookup(this.value.trim(), 'isbn-spinner');
    });
  }
  <?php endif; ?>
})();
</script>
<?php endif; ?>

<?php }; ?>
