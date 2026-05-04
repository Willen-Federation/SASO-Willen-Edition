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

      <p class="mb-4 small text-muted">
        <?php echo $lang === 'ja'
          ? '商品の画像をアップロードすると、AIがバーコードや商品情報を解析して自動入力します。解析完了後にドラフト一覧で内容を確認・修正して登録できます。'
          : 'Upload a product image and AI will analyse the barcode and product details for you. Review and edit the draft before confirming registration.'; ?>
      </p>

      <form method="post"
            action="./item/addFromImage/"
            enctype="multipart/form-data"
            class="vstack gap-4"
            x-data="{ fileName: '', previewUrl: null, dragging: false }"
      >

        <!-- Image drop zone -->
        <div
          class="position-relative d-flex flex-column align-items-center justify-content-center rounded border border-2 p-5 text-center"
          style="border-style:dashed;min-height:12rem;transition:border-color .15s;"
          :class="dragging ? 'border-primary bg-primary-subtle' : 'border-secondary'"
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
            <div class="d-flex flex-column align-items-center gap-3">
              <i class="ti ti-photo text-muted" style="font-size:3rem;" aria-hidden="true"></i>
              <div>
                <label for="image-upload" class="text-primary fw-semibold" style="cursor:pointer;">
                  <?php echo $lang === 'ja' ? 'ファイルを選択' : 'Choose a file'; ?>
                </label>
                <span class="text-muted">
                  <?php echo $lang === 'ja' ? 'またはここにドラッグ＆ドロップ' : ' or drag and drop here'; ?>
                </span>
              </div>
              <p class="small text-muted mb-0">JPG, PNG, WEBP &mdash; <?php echo $lang === 'ja' ? '最大10MB' : 'max 10 MB'; ?></p>
            </div>
          </template>

          <template x-if="previewUrl">
            <div class="d-flex flex-column align-items-center gap-3">
              <img :src="previewUrl" alt="Preview" class="rounded"
                   style="max-height:12rem;object-fit:contain;">
              <p class="small text-muted mb-0" x-text="fileName"></p>
              <button type="button"
                      class="btn btn-link btn-sm text-danger p-0"
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
            class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
            style="cursor:pointer;"
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
        <details class="border rounded p-3">
          <summary class="form-label mb-0" style="cursor:pointer;">
            <?php echo $lang === 'ja' ? '追加情報（任意）' : 'Additional hints (optional)'; ?>
          </summary>
          <div class="vstack gap-3 mt-3">
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

        <div class="d-flex justify-content-end gap-3">
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
