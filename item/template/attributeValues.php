<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $item = $v->item;
    $attributes = $v->attributes ?? [];
    if (!$item) return;
    $itemId = htmlspecialchars($item->id, ENT_QUOTES, 'UTF-8');
?>

<div class="mx-auto max-w-xl rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">
      <?php echo $lang === 'ja' ? 'オリジナル項目' : 'Custom Columns'; ?>
    </h2>
  </div>
  <div class="px-5 py-5">
    <?php if (empty($attributes)): ?>
      <p class="text-sm" style="color:var(--saso-text-sub)">
        <?php echo $lang === 'ja'
          ? '登録されたオリジナル項目がありません。'
          : 'No custom columns are configured.'; ?>
        <a href="./itemAttribute/start/" class="underline">
          <?php echo $lang === 'ja' ? '項目を追加する' : 'Add columns'; ?>
        </a>
      </p>
    <?php else: ?>
      <form method="post" action="./item/attributeValues/item/<?php echo $itemId; ?>">
        <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="flex flex-col gap-4">
          <?php foreach ($attributes as $attr):
            $code    = htmlspecialchars($attr['code'], ENT_QUOTES, 'UTF-8');
            $label   = htmlspecialchars($lang === 'ja' ? $attr['label_ja'] : ($attr['label_en'] ?: $attr['label_ja']), ENT_QUOTES, 'UTF-8');
            $unit    = $attr['unit'] ? htmlspecialchars($attr['unit'], ENT_QUOTES, 'UTF-8') : null;
            $type    = $attr['value_type'];
            $req     = $attr['required'] ? ' required' : '';
            $inputName = 'attr[' . $code . ']';
          ?>
            <div>
              <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)"
                     for="attr_<?php echo $code; ?>">
                <?php echo $label; ?>
                <?php if ($unit): ?><span class="text-xs opacity-60">(<?php echo $unit; ?>)</span><?php endif; ?>
                <?php if ($attr['required']): ?><span class="text-error-500 ml-0.5">*</span><?php endif; ?>
              </label>

              <?php if ($type === 'bool'): ?>
                <select id="attr_<?php echo $code; ?>" name="<?php echo $inputName; ?>"
                        class="form-input w-full"<?php echo $req; ?>>
                  <option value=""><?php echo $lang === 'ja' ? '— 未設定 —' : '— Unset —'; ?></option>
                  <option value="1" <?php echo ($attr['value_bool'] === true) ? 'selected' : ''; ?>>
                    <?php echo $lang === 'ja' ? 'はい' : 'Yes'; ?>
                  </option>
                  <option value="0" <?php echo ($attr['value_bool'] === false && $attr['value_bool'] !== null) ? 'selected' : ''; ?>>
                    <?php echo $lang === 'ja' ? 'いいえ' : 'No'; ?>
                  </option>
                </select>

              <?php elseif ($type === 'enum' || $type === 'multi_select'): ?>
                <?php $opts = is_array($attr['enum_values']) ? $attr['enum_values'] : []; ?>
                <?php $currentVal = $attr['value_string'] ?? ''; ?>
                <?php if ($type === 'multi_select'): ?>
                  <?php $selected = array_filter(explode(',', $currentVal)); ?>
                  <div class="flex flex-col gap-1.5">
                    <?php foreach ($opts as $opt):
                      $optEsc = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                      <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="<?php echo $inputName; ?>[]"
                               value="<?php echo $optEsc; ?>"
                               class="h-4 w-4 rounded"
                               <?php echo in_array($opt, $selected, true) ? 'checked' : ''; ?>>
                        <?php echo $optEsc; ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <select id="attr_<?php echo $code; ?>" name="<?php echo $inputName; ?>"
                          class="form-input w-full"<?php echo $req; ?>>
                    <option value=""><?php echo $lang === 'ja' ? '— 選択 —' : '— Select —'; ?></option>
                    <?php foreach ($opts as $opt):
                      $optEsc = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                      <option value="<?php echo $optEsc; ?>"
                              <?php echo $currentVal === $opt ? 'selected' : ''; ?>>
                        <?php echo $optEsc; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>

              <?php elseif ($type === 'int'): ?>
                <input id="attr_<?php echo $code; ?>" type="number" step="1"
                       name="<?php echo $inputName; ?>"
                       value="<?php echo htmlspecialchars((string)($attr['value_int'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-input w-full"<?php echo $req; ?>>

              <?php elseif ($type === 'float'): ?>
                <input id="attr_<?php echo $code; ?>" type="number" step="any"
                       name="<?php echo $inputName; ?>"
                       value="<?php echo htmlspecialchars((string)($attr['value_float'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-input w-full"<?php echo $req; ?>>

              <?php elseif ($type === 'tags'): ?>
                <input id="attr_<?php echo $code; ?>" type="text"
                       name="<?php echo $inputName; ?>"
                       value="<?php echo htmlspecialchars($attr['value_string'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="<?php echo $lang === 'ja' ? 'タグをカンマ区切りで入力' : 'Comma-separated tags'; ?>"
                       class="form-input w-full"<?php echo $req; ?>>

              <?php else: ?>
                <input id="attr_<?php echo $code; ?>" type="text"
                       name="<?php echo $inputName; ?>"
                       value="<?php echo htmlspecialchars($attr['value_string'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-input w-full"<?php echo $req; ?>>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-5">
          <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" aria-hidden="true" focusable="false">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?php echo $lang === 'ja' ? '保存する' : 'Save'; ?>
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php }; ?>
