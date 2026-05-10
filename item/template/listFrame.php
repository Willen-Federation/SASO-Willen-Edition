<?php $this->content = function($v) { ?>

<div class="hidden" id="current"><?php echo $v->request; ?></div>
<div class="flex items-center gap-2 mb-4">
  <input type="text" id="search" maxlength="50" placeholder="商品名"
         value="<?php echo htmlspecialchars(urldecode($v->search), ENT_QUOTES, 'UTF-8'); ?>"
         class="form-input w-64">
  <button id="searchButton" type="button" class="btn btn-primary btn-sm">検索</button>
  <a href="./<?php echo $v->request; ?>/" class="btn btn-secondary btn-sm">検索解除</a>
</div>

<div class="overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <thead>
      <tr>
        <th scope="col">商品番号
          <a href="./<?php echo $v->request; ?>/sortby/concatId/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
          <a href="./<?php echo $v->request; ?>/sortby/concatId/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
        </th>
        <th scope="col">商品名</th>
        <th scope="col">分類
          <a href="./<?php echo $v->request; ?>/sortby/categoryId/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
          <a href="./<?php echo $v->request; ?>/sortby/categoryId/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
        </th>
        <th scope="col" class="text-right">価格
          <a href="./<?php echo $v->request; ?>/sortby/price/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
          <a href="./<?php echo $v->request; ?>/sortby/price/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
        </th>
        <th scope="col">プラ</th>
        <th scope="col">付記</th>
        <th scope="col">紙</th>
        <th scope="col">付記</th>
        <th scope="col">登録日
          <a href="./<?php echo $v->request; ?>/sortby/createAt/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
          <a href="./<?php echo $v->request; ?>/sortby/createAt/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
        </th>
        <th scope="col">更新日
          <a href="./<?php echo $v->request; ?>/sortby/updateAt/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
          <a href="./<?php echo $v->request; ?>/sortby/updateAt/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
        </th>
        <th scope="col">色</th>
        <th scope="col">サイズ</th>
      </tr>
    </thead>
    <tbody>
      <?php ($v->inside)('item', 'listContents', $v->isArchive); ?>
    </tbody>
  </table>
</div>
<?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>

<?php }; ?>
