<?php $this->title = '棚番作成'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <li class="breadcrumb-item active" aria-current="page">棚番作成</li>
  </ol>
</nav>

<p class="mb-4 text-sm" style="color:var(--saso-text-sub)">
  ラベルは<a href="./label/start/" class="underline">ラベル寸法管理</a>で予め登録して下さい。
</p>

<!-- クイックアクション -->
<div class="flex flex-wrap gap-3 mb-6">
  <a href="./shelf/simple/" class="btn btn-secondary">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16M4 10h16M4 15h16M4 20h16"/></svg>
    棚番号ラベルシートを作成
  </a>
  <a href="./label/start/" class="btn btn-secondary">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h18M3 18h12"/></svg>
    ラベル寸法管理
  </a>
</div>

<!-- 一括作成 -->
<div class="rounded-2xl border shadow-sm mb-6 overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="flex items-center px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold text-base" style="color:var(--saso-text)">一括作成</h3>
  </div>
  <div class="px-6 py-5">
    <p class="text-sm mb-4" style="color:var(--saso-text-sub)">
      各次元のminとmaxに数値を入力した場合、次元ごとのすべての組み合わせで連番が生成されます。<br>
      minに英字を入れるか、maxを空欄にした場合、その次元はminの値が固定値となります。<br>
      minが空欄だと以降の次元は無視されます。英字は大文字に変換されます。
    </p>
    <p class="text-sm mb-4" style="color:var(--saso-text-sub)">
      例）1次元min: 0, max: 2、2次元min:A, max:空欄、3次元min: 0,max: 1の場合：<br>
      <span class="font-mono">00-A-00, 00-A-01, 01-A-00, 01-A-01, 02-A-00, 02-A-01</span>
    </p>

    <fieldset>
      <legend class="sr-only">次元範囲入力</legend>
      <div class="space-y-3">
        <?php
        $dimLabels = ['1次元', '2次元', '3次元', '4次元', '5次元'];
        for ($i = 1; $i <= 5; $i++):
        ?>
        <div class="flex items-center gap-3">
          <span class="w-14 text-sm shrink-0 text-right" style="color:var(--saso-text-sub)"><?php echo $dimLabels[$i-1]; ?>：</span>
          <input class="form-input w-24" type="text"
                 id="dimension<?php echo $i; ?>min"
                 maxlength="2" pattern="^[0-9A-Za-z]+$" placeholder="min"
                 aria-label="<?php echo $dimLabels[$i-1]; ?>最小値">
          <span class="text-sm shrink-0" style="color:var(--saso-text-sub)">〜</span>
          <input class="form-input w-24" type="text"
                 id="dimension<?php echo $i; ?>max"
                 maxlength="2" pattern="^[0-9]+$" placeholder="max"
                 aria-label="<?php echo $dimLabels[$i-1]; ?>最大値">
        </div>
        <?php endfor; ?>
      </div>
    </fieldset>

    <div class="mt-4">
      <input type="hidden" id="pageNumber" value="1">
      <button class="btn btn-primary" id="submitMultiButton">ラベルリスト作成</button>
    </div>
  </div>
</div>

<!-- 単一作成 -->
<div class="rounded-2xl border shadow-sm mb-6 overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold text-base" style="color:var(--saso-text)">単一作成</h3>
    <span class="text-xs" style="color:var(--saso-text-sub)">よく使う形式をワンクリックで入力できます</span>
  </div>
  <div class="px-6 py-5">
    <p class="text-sm mb-3" style="color:var(--saso-text-sub)">棚番を入力（半角英数・ハイフン）。英字は大文字に変換されます。</p>

    <div class="mb-4">
      <span class="text-xs mr-2" style="color:var(--saso-text-sub)">よく使う形式：</span>
      <?php
        $presets = [
          ['label' => 'A-01',    'value' => 'A-01'],
          ['label' => 'A-01-01', 'value' => 'A-01-01'],
          ['label' => '01-A-01', 'value' => '01-A-01'],
          ['label' => 'R01-S01', 'value' => 'R01-S01'],
          ['label' => 'W-01',    'value' => 'W-01'],
        ];
        foreach ($presets as $p):
      ?>
        <button type="button"
                class="btn btn-secondary btn-sm mr-1 mb-1 shelf-preset"
                data-value="<?php echo htmlspecialchars($p['value'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars($p['label'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div>
      <button class="btn btn-primary" id="submitSingleButton">ラベル作成</button>
    </div>
  </div>
</div>

<?php }; ?>
