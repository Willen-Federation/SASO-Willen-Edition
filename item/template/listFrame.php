<?php $this->content = function($v) { ?>

<div class="d-none" id="current"><?php echo $v->request; ?></div>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label for="search" class="form-label">商品名で検索</label>
        <input type="text" id="search" class="form-control" maxlength="50"
               placeholder="商品名" value="<?php echo urldecode($v->search); ?>">
      </div>
      <div class="col-md-auto">
        <button id="searchButton" type="button" class="btn btn-outline-primary">
          <i class="ti ti-search me-1"></i>検索
        </button>
      </div>
      <div class="col-md-auto">
        <a href="./<?php echo $v->request; ?>/" class="btn btn-link">検索解除</a>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter table-hover card-table">
      <thead>
        <tr>
          <th scope="col">商品番号
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/concatId/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/concatId/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
          </th>
          <th scope="col">商品名</th>
          <th scope="col">分類
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/categoryId/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/categoryId/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
          </th>
          <th scope="col">価格
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/price/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/price/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
          </th>
          <th scope="col">プラ</th>
          <th scope="col">付記</th>
          <th scope="col">紙</th>
          <th scope="col">付記</th>
          <th scope="col">登録日
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/createAt/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/createAt/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
          </th>
          <th scope="col">更新日
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/updateAt/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
            <a class="text-secondary" href="./<?php echo $v->request; ?>/sortby/updateAt/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
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
</div>

<?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>

<?php }; ?>
