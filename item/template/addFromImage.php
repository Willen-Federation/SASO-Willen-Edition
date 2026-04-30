<?php $this->title = __('ui.item.add_from_image.title', [], null, 'Register via Image'); ?>
<?php $this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $flashError = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);
?>

<?php if ($flashError): ?>
  <?php ui('alert', ['variant' => 'danger', 'body' => $flashError, 'dismissible' => true]); ?>
<?php endif; ?>

<?php
  ui('card', [
    'title' => $lang === 'ja' ? '画像から商品登録' : 'Register Product via Image',
    'body'  => function () use ($lang) { ?>

      <p class="mb-6 text-sm text-body dark:text-bodydark">
        <?php echo $lang === 'ja'
          ? '商品の画像をアップロードすると、AIがバーコードや商品情報を解析して自動入力します。解析完了後にドラフト一覧で内容を確認・修正して登録できます。'
          : 'Upload a product image and AI will analyse the barcode and product details for you. Review and edit the draft before confirming registration.'; ?>
      </p>

      <form method="post"
            action="./item/addFromImage/"
            enctype="multipart/form-data"
            class="space-y-6"
            x-data="{ fileName: '', previewUrl: null, dragging: false }"
      >

        <!-- Image drop zone -->
        <div
          class="relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-stroke p-8 transition hover:border-brand-400 dark:border-strokedark dark:hover:border-brand-500"
          :class="{ 'border-brand-400 bg-brand-50 dark:bg-brand-900/20': dragging }"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="
            dragging = false;
            const f = $event.dataTransfer.files[0];
            if (f) {
              fileName = f.name;
              previewUrl = URL.createObjectURL(f);
              $refs.fileInput.files = $event.dataTransfer.files;
            }
          "
        >
          <template x-if="!previewUrl">
            <div class="flex flex-col items-center gap-3 text-center">
              <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <div>
                <label for="image-upload" class="cursor-pointer font-semibold text-brand-600 hover:underline dark:text-brand-400">
                  <?php echo $lang === 'ja' ? 'ファイルを選択' : 'Choose a file'; ?>
                </label>
                <span class="text-body dark:text-bodydark">
                  <?php echo $lang === 'ja' ? 'またはここにドラッグ＆ドロップ' : ' or drag and drop here'; ?>
                </span>
              </div>
              <p class="text-xs text-gray-400">JPG, PNG, WEBP &mdash; <?php echo $lang === 'ja' ? '最大10MB' : 'max 10 MB'; ?></p>
            </div>
          </template>

          <template x-if="previewUrl">
            <div class="flex flex-col items-center gap-3">
              <img :src="previewUrl" alt="Preview" class="max-h-48 rounded object-contain">
              <p class="text-sm text-body dark:text-bodydark" x-text="fileName"></p>
              <button type="button"
                      class="text-xs text-danger hover:underline"
                      @click="previewUrl = null; fileName = ''; $refs.fileInput.value = ''">
                <?php echo $lang === 'ja' ? '削除' : 'Remove'; ?>
              </button>
            </div>
          </template>

          <input
            x-ref="fileInput"
            id="image-upload"
            type="file"
            name="image"
            accept="image/*"
            required
            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
            @change="
              const f = $event.target.files[0];
              if (f) {
                fileName = f.name;
                previewUrl = URL.createObjectURL(f);
              }
            "
          >
        </div>

        <!-- Optional hints -->
        <details class="rounded-lg border border-stroke p-4 dark:border-strokedark">
          <summary class="cursor-pointer text-sm font-medium text-black dark:text-white">
            <?php echo $lang === 'ja' ? '追加情報（任意）' : 'Additional hints (optional)'; ?>
          </summary>
          <div class="mt-4 space-y-4">
            <?php ui('formField', [
              'name'        => 'barcode_hint',
              'label'       => $lang === 'ja' ? 'バーコード番号' : 'Barcode / JAN / ISBN',
              'placeholder' => '4901234567890',
              'help'        => $lang === 'ja' ? '既にバーコードが分かっている場合は入力してください。' : 'Enter if you already know the barcode.',
            ]); ?>
            <?php ui('formField', [
              'name'        => 'item_name',
              'label'       => $lang === 'ja' ? '商品名ヒント' : 'Product name hint',
              'placeholder' => $lang === 'ja' ? '例：コットンTシャツ' : 'e.g. Cotton T-shirt',
            ]); ?>
            <?php ui('formField', [
              'name'        => 'price',
              'label'       => $lang === 'ja' ? '価格ヒント' : 'Price hint',
              'placeholder' => '1,200',
            ]); ?>
          </div>
        </details>

        <div class="flex justify-end gap-3">
          <?php ui('button', [
            'label'   => $lang === 'ja' ? 'キャンセル' : 'Cancel',
            'type'    => 'link',
            'href'    => './item/add/',
            'variant' => 'secondary',
          ]); ?>
          <?php ui('button', [
            'label'   => $lang === 'ja' ? '画像を送信してドラフト作成' : 'Upload & Create Draft',
            'type'    => 'submit',
            'variant' => 'primary',
          ]); ?>
        </div>

      </form>

    <?php },
  ]);
?>

<?php }; ?>
