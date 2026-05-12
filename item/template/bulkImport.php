<?php $this->title = '一括操作'; ?>
<?php $this->content = function ($v) { ?>

<div class="mx-auto max-w-2xl space-y-6">

  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h2 class="font-semibold" style="color:var(--saso-text)">エクスポート / テンプレート</h2>
    </div>
    <div class="px-5 py-5 space-y-3">
      <p class="text-sm mb-3" style="color:var(--saso-text-sub)">
        現在登録されている商品をCSVで書き出したり、一括登録用テンプレートをダウンロードできます。
      </p>
      <a href="./item/bulkExport/"
         class="btn btn-secondary w-full flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        現在の商品一覧をCSVでエクスポート
      </a>
      <a href="./item/bulkTemplate/"
         class="btn btn-secondary w-full flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        インポート用テンプレートをダウンロード
      </a>
    </div>
  </div>

  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h2 class="font-semibold" style="color:var(--saso-text)">CSVで一括登録</h2>
    </div>
    <div class="px-5 py-5">
      <div class="mb-4 rounded-lg p-4 text-sm"
           style="background:var(--saso-bg);border:1px solid var(--saso-card-bdr);color:var(--saso-text-sub)">
        <p class="font-medium mb-2" style="color:var(--saso-text)">CSVファイルの形式</p>
        <p>ヘッダー行（1行目）は以下の列が必要です：</p>
        <code class="block mt-1 text-xs break-all">
          商品名, 分類ID, 価格, 色, サイズ, プラ, プラ付記, 紙, 紙付記
        </code>
        <ul class="mt-2 space-y-1 list-disc list-inside">
          <li>色・サイズが複数の場合はカンマ（,）区切り。例: <code>赤,青,白</code></li>
          <li>プラ・紙は <code>1</code>（あり）または <code>0</code>（なし）</li>
          <li>分類ID・価格・付記は省略可</li>
          <li>文字コード: UTF-8（BOM付き可）</li>
        </ul>
      </div>

      <form method="post" action="./item/bulkImport/" enctype="multipart/form-data">
        <input type="hidden" name="csrftoken"
               value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
        <div class="mb-5">
          <label for="bulk-csv"
                 class="mb-1.5 block text-sm font-medium"
                 style="color:var(--saso-text)">
            CSVファイル <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="bulk-csv" type="file" name="csv" accept=".csv,text/csv" required
                 class="form-input w-full">
          <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">
            上限: <?php echo ini_get('upload_max_filesize'); ?> / 行数制限なし（処理時間は行数に比例します）
          </p>
        </div>
        <button type="submit" class="btn btn-primary w-full">
          インポート実行
        </button>
      </form>
    </div>
  </div>

</div>

<?php }; ?>
