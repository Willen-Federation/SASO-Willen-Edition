<?php $this->title = '一括アーカイブ'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">一括アーカイブ</li>
  </ol>
</nav>

<div class="hidden" id="current"><?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?></div>

<div class="mb-4 flex items-center gap-2">
  <input type="text" id="search" class="form-input" maxlength="50"
         placeholder="商品名" value="<?php echo htmlspecialchars(urldecode($v->search), ENT_QUOTES, 'UTF-8'); ?>">
  <button id="searchButton" type="button" class="btn btn-secondary btn-sm">検索</button>
  <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/"
     class="btn btn-secondary btn-sm">検索解除</a>
</div>

<form action="./item/archiveAll" method="post">
  <div class="mb-4 flex items-center gap-3">
    <button class="btn btn-warning">一括アーカイブ</button>
    <label class="flex items-center gap-2 text-sm" style="color:var(--saso-text)">
      アーカイブ理由：
      <input type="text" name="archiveNote" class="form-input" maxlength="50">
    </label>
  </div>
  <div class="overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
    <table class="ta-table">
      <thead>
        <tr>
          <th scope="col">
            <label class="flex items-center gap-2">
              <input type="checkbox" id="checkAllArchiveAllCheckbox" class="h-4 w-4 rounded">
              <span>アーカイブ</span>
            </label>
          </th>
          <th scope="col">商品番号
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/concatId/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/concatId/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
          </th>
          <th scope="col">商品名</th>
          <th scope="col">分類
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/categoryId/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/categoryId/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
          </th>
          <th scope="col">価格
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/price/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/price/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
          </th>
          <th scope="col">プラ</th>
          <th scope="col">付記</th>
          <th scope="col">紙</th>
          <th scope="col">付記</th>
          <th scope="col">登録日
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/createAt/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/createAt/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
          </th>
          <th scope="col">更新日
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/updateAt/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
            <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/updateAt/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
          </th>
          <th scope="col">色</th>
          <th scope="col">サイズ</th>
        </tr>
      </thead>
      <tbody>
        <?php ($v->inside)('item', 'archiveAll', $v->isArchive); ?>
      </tbody>
    </table>
  </div>
</form>

<?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>

<?php }; ?>
