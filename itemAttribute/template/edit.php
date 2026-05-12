<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $title = $lang === 'ja' ? '商品ステータス項目管理' : 'Item Status Columns';
    $valueTypeLabels = [
        'string'       => $lang === 'ja' ? 'テキスト'      : 'Text',
        'int'          => $lang === 'ja' ? '整数'          : 'Integer',
        'float'        => $lang === 'ja' ? '小数'          : 'Float',
        'bool'         => $lang === 'ja' ? 'はい/いいえ'   : 'Yes/No',
        'enum'         => $lang === 'ja' ? '選択肢(単一)' : 'Select (single)',
        'multi_select' => $lang === 'ja' ? '選択肢(複数)' : 'Select (multi)',
        'barcode'      => $lang === 'ja' ? 'バーコード'    : 'Barcode',
        'tags'         => $lang === 'ja' ? 'タグ'          : 'Tags',
    ];
?>

<div class="card">
  <div class="card-header flex items-center justify-between">
    <h2 class="font-semibold text-black dark:text-white">
      <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
    </h2>
    <button id="ia-btn-add"
            class="btn btn-sm btn-primary"
            aria-label="<?php echo $lang === 'ja' ? '項目を追加' : 'Add column'; ?>">
      + <?php echo $lang === 'ja' ? '項目を追加' : 'Add column'; ?>
    </button>
  </div>
  <div class="card-body">

    <p class="mb-4 text-sm" style="color:var(--saso-text-sub)">
      <?php echo $lang === 'ja'
        ? '商品に追加できるオリジナルのステータス項目を管理します。登録した項目は商品編集画面から値を設定できます。'
        : 'Manage custom status columns that can be added to items. Values for each column can be set on the item edit screen.'; ?>
    </p>

    <div id="ia-table-wrap" class="overflow-x-auto rounded-lg border" style="border-color:var(--saso-card-bdr)">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-left text-xs font-semibold uppercase tracking-wider"
              style="background:var(--saso-card-alt);border-color:var(--saso-card-bdr);color:var(--saso-text-sub)">
            <th class="px-4 py-3"><?php echo $lang === 'ja' ? 'コード' : 'Code'; ?></th>
            <th class="px-4 py-3"><?php echo $lang === 'ja' ? 'ラベル' : 'Label'; ?></th>
            <th class="px-4 py-3"><?php echo $lang === 'ja' ? '型' : 'Type'; ?></th>
            <th class="px-4 py-3"><?php echo $lang === 'ja' ? '単位' : 'Unit'; ?></th>
            <th class="px-4 py-3"><?php echo $lang === 'ja' ? '必須' : 'Required'; ?></th>
            <th class="px-4 py-3"><?php echo $lang === 'ja' ? '順序' : 'Order'; ?></th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody id="ia-tbody">
          <tr id="ia-empty-row">
            <td colspan="7" class="px-4 py-6 text-center text-sm" style="color:var(--saso-text-sub)">
              <?php echo $lang === 'ja' ? '項目がありません' : 'No columns defined'; ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal overlay -->
<div id="ia-modal" class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,0.5)">
  <div class="mx-4 w-full max-w-lg rounded-2xl p-6 shadow-xl"
       style="background:var(--saso-card);color:var(--saso-text)">
    <h3 id="ia-modal-title" class="mb-4 text-base font-semibold"></h3>

    <form id="ia-form" class="flex flex-col gap-4" novalidate>
      <input type="hidden" id="ia-id" name="id" value="">

      <div>
        <label class="mb-1 block text-xs font-medium" for="ia-code">
          <?php echo $lang === 'ja' ? 'コード (英数字・_.のみ)' : 'Code (alphanumeric, _ .)'; ?> <span class="text-error-500">*</span>
        </label>
        <input id="ia-code" name="code" type="text" required
               pattern="[a-z0-9._]+" maxlength="120"
               class="form-input w-full" placeholder="e.g. color_size">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-xs font-medium" for="ia-label-ja">
            <?php echo $lang === 'ja' ? 'ラベル（日本語）' : 'Label (JA)'; ?> <span class="text-error-500">*</span>
          </label>
          <input id="ia-label-ja" name="label_ja" type="text" required
                 maxlength="200" class="form-input w-full">
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium" for="ia-label-en">
            <?php echo $lang === 'ja' ? 'ラベル（英語）' : 'Label (EN)'; ?>
          </label>
          <input id="ia-label-en" name="label_en" type="text"
                 maxlength="200" class="form-input w-full">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-xs font-medium" for="ia-value-type">
            <?php echo $lang === 'ja' ? '型' : 'Type'; ?> <span class="text-error-500">*</span>
          </label>
          <select id="ia-value-type" name="value_type" class="form-input w-full">
            <?php foreach ($valueTypeLabels as $val => $lbl): ?>
              <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium" for="ia-unit">
            <?php echo $lang === 'ja' ? '単位 (例: kg, cm)' : 'Unit (e.g. kg, cm)'; ?>
          </label>
          <input id="ia-unit" name="unit" type="text" maxlength="40"
                 class="form-input w-full">
        </div>
      </div>

      <div id="ia-enum-wrap" class="hidden">
        <label class="mb-1 block text-xs font-medium" for="ia-enum-values">
          <?php echo $lang === 'ja' ? '選択肢 (改行区切り)' : 'Options (one per line)'; ?>
        </label>
        <textarea id="ia-enum-values" name="enum_values_raw" rows="4"
                  class="form-input w-full resize-y font-mono text-xs"></textarea>
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">
          <?php echo $lang === 'ja' ? '1行に1つの選択肢を入力してください。' : 'Enter one option per line.'; ?>
        </p>
        <input type="hidden" id="ia-enum-json" name="enum_values">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-xs font-medium" for="ia-sort-order">
            <?php echo $lang === 'ja' ? '表示順' : 'Sort order'; ?>
          </label>
          <input id="ia-sort-order" name="sort_order" type="number"
                 min="0" max="9999" value="0" class="form-input w-full">
        </div>
        <div class="flex flex-col justify-center gap-2 pt-4">
          <label class="flex cursor-pointer items-center gap-2 text-sm">
            <input id="ia-required" name="required" type="checkbox" class="h-4 w-4 rounded">
            <?php echo $lang === 'ja' ? '必須' : 'Required'; ?>
          </label>
          <label class="flex cursor-pointer items-center gap-2 text-sm">
            <input id="ia-show-web" name="show_on_web" type="checkbox" class="h-4 w-4 rounded" checked>
            <?php echo $lang === 'ja' ? 'Web表示' : 'Show on Web'; ?>
          </label>
        </div>
      </div>

      <div id="ia-error" class="hidden rounded border border-error-200 bg-error-50 px-3 py-2 text-sm text-error-700 dark:border-error-800 dark:bg-error-950 dark:text-error-300"></div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" id="ia-cancel" class="btn btn-sm btn-secondary">
          <?php echo $lang === 'ja' ? 'キャンセル' : 'Cancel'; ?>
        </button>
        <button type="submit" id="ia-submit" class="btn btn-sm btn-primary">
          <?php echo $lang === 'ja' ? '保存' : 'Save'; ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const csrftoken = <?php echo json_encode(\saso\util\CSRFtoken::current()); ?>;
  const lang      = <?php echo json_encode($lang); ?>;
  const labels    = <?php echo json_encode($valueTypeLabels); ?>;

  const modal     = document.getElementById('ia-modal');
  const form      = document.getElementById('ia-form');
  const tbody     = document.getElementById('ia-tbody');
  const emptyRow  = document.getElementById('ia-empty-row');
  const errBox    = document.getElementById('ia-error');
  const enumWrap  = document.getElementById('ia-enum-wrap');

  function post(url, data) {
    return fetch(url, {
      method: 'POST',
      body: JSON.stringify(Object.assign({ csrftoken }, data)),
    }).then(r => r.json());
  }

  function loadList() {
    post('./itemAttribute/list.json', {}).then(data => {
      const defs = data.definitions ?? [];
      tbody.innerHTML = '';
      if (defs.length === 0) {
        tbody.appendChild(emptyRow);
        return;
      }
      defs.forEach(d => tbody.appendChild(buildRow(d)));
    });
  }

  function buildRow(d) {
    const tr = document.createElement('tr');
    tr.className = 'border-b last:border-0 hover:bg-gray-50 dark:hover:bg-white/[0.02]';
    tr.style.borderColor = 'var(--saso-card-bdr)';
    tr.dataset.id = d.id;
    tr.innerHTML = `
      <td class="px-4 py-3 font-mono text-xs">${esc(d.code)}</td>
      <td class="px-4 py-3">${esc(d.label_ja)}${d.label_en && d.label_en !== d.label_ja ? ' <span class="text-xs opacity-60">/ ' + esc(d.label_en) + '</span>' : ''}</td>
      <td class="px-4 py-3 text-xs">${esc(labels[d.value_type] ?? d.value_type)}</td>
      <td class="px-4 py-3 text-xs">${d.unit ? esc(d.unit) : '<span class="opacity-40">—</span>'}</td>
      <td class="px-4 py-3 text-xs">${d.required ? (lang === 'ja' ? '必須' : 'Yes') : '<span class="opacity-40">—</span>'}</td>
      <td class="px-4 py-3 text-xs">${d.sort_order}</td>
      <td class="px-4 py-3 text-right">
        <button class="ia-edit-btn btn btn-xs btn-secondary mr-1" data-id="${d.id}" aria-label="${lang === 'ja' ? '編集' : 'Edit'}">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
        </button>
        <button class="ia-del-btn btn btn-xs" style="background:var(--color-error-100,#fee2e2);color:var(--color-error-600,#dc2626)" data-id="${d.id}" data-code="${esc(d.code)}" aria-label="${lang === 'ja' ? '削除' : 'Delete'}">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </td>`;
    tr.querySelector('.ia-edit-btn').addEventListener('click', () => openEdit(d));
    tr.querySelector('.ia-del-btn').addEventListener('click', () => confirmDelete(d));
    return tr;
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = String(s ?? '');
    return d.innerHTML;
  }

  function openAdd() {
    form.reset();
    document.getElementById('ia-id').value = '';
    document.getElementById('ia-modal-title').textContent = lang === 'ja' ? '項目を追加' : 'Add Column';
    document.getElementById('ia-submit').textContent = lang === 'ja' ? '追加' : 'Add';
    document.getElementById('ia-show-web').checked = true;
    errBox.classList.add('hidden');
    toggleEnumWrap();
    showModal();
  }

  function openEdit(d) {
    form.reset();
    document.getElementById('ia-id').value          = d.id;
    document.getElementById('ia-code').value        = d.code;
    document.getElementById('ia-label-ja').value    = d.label_ja;
    document.getElementById('ia-label-en').value    = d.label_en ?? '';
    document.getElementById('ia-value-type').value  = d.value_type;
    document.getElementById('ia-unit').value        = d.unit ?? '';
    document.getElementById('ia-sort-order').value  = d.sort_order ?? 0;
    document.getElementById('ia-required').checked  = !!d.required;
    document.getElementById('ia-show-web').checked  = d.show_on_web !== false;
    document.getElementById('ia-modal-title').textContent = lang === 'ja' ? '項目を編集' : 'Edit Column';
    document.getElementById('ia-submit').textContent = lang === 'ja' ? '更新' : 'Update';
    errBox.classList.add('hidden');

    if (d.enum_values) {
      let opts = [];
      try { opts = JSON.parse(d.enum_values); } catch(e) {}
      document.getElementById('ia-enum-values').value = Array.isArray(opts) ? opts.join('\n') : '';
      document.getElementById('ia-enum-json').value = d.enum_values;
    }
    toggleEnumWrap();
    showModal();
  }

  function confirmDelete(d) {
    const msg = lang === 'ja'
      ? `「${d.code}」を削除しますか？この操作は取り消せません。`
      : `Delete "${d.code}"? This cannot be undone.`;
    if (!confirm(msg)) return;
    post('./itemAttribute/delete', { id: d.id }).then(() => loadList());
  }

  function showModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('ia-code').focus();
  }

  function hideModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function toggleEnumWrap() {
    const t = document.getElementById('ia-value-type').value;
    if (t === 'enum' || t === 'multi_select') {
      enumWrap.classList.remove('hidden');
    } else {
      enumWrap.classList.add('hidden');
    }
  }

  document.getElementById('ia-btn-add').addEventListener('click', openAdd);
  document.getElementById('ia-cancel').addEventListener('click', hideModal);
  modal.addEventListener('click', e => { if (e.target === modal) hideModal(); });

  document.getElementById('ia-value-type').addEventListener('change', toggleEnumWrap);

  document.getElementById('ia-enum-values').addEventListener('input', function () {
    const lines = this.value.split('\n').map(l => l.trim()).filter(Boolean);
    document.getElementById('ia-enum-json').value = lines.length ? JSON.stringify(lines) : '';
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errBox.classList.add('hidden');

    const id = document.getElementById('ia-id').value;
    const payload = {
      id:           id || undefined,
      code:         document.getElementById('ia-code').value.trim().toLowerCase().replace(/[^a-z0-9._]/g, ''),
      label_ja:     document.getElementById('ia-label-ja').value.trim(),
      label_en:     document.getElementById('ia-label-en').value.trim(),
      value_type:   document.getElementById('ia-value-type').value,
      unit:         document.getElementById('ia-unit').value.trim() || null,
      required:     document.getElementById('ia-required').checked ? 1 : 0,
      enum_values:  document.getElementById('ia-enum-json').value || null,
      sort_order:   parseInt(document.getElementById('ia-sort-order').value, 10) || 0,
      show_on_web:  document.getElementById('ia-show-web').checked ? 1 : 0,
      show_on_mobile: 1,
    };

    if (!payload.code) {
      showErr(lang === 'ja' ? 'コードを入力してください。' : 'Code is required.');
      return;
    }
    if (!payload.label_ja) {
      showErr(lang === 'ja' ? 'ラベル（日本語）を入力してください。' : 'Japanese label is required.');
      return;
    }

    const url = id ? './itemAttribute/update' : './itemAttribute/add';
    post(url, payload).then(() => {
      hideModal();
      loadList();
    }).catch(() => showErr(lang === 'ja' ? '保存に失敗しました。' : 'Save failed.'));
  });

  function showErr(msg) {
    errBox.textContent = msg;
    errBox.classList.remove('hidden');
  }

  loadList();
})();
</script>

<?php }; ?>
