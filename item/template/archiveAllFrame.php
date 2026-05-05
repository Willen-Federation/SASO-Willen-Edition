<?php $this->title = '一括アーカイブ'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">一括アーカイブ</li>
</ol>

<div class="d-none" id="current"><?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?></div>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label for="search" class="form-label">商品名で検索</label>
        <input type="text" id="search" class="form-control" maxlength="50"
               placeholder="商品名" value="<?php echo htmlspecialchars(urldecode($v->search), ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="col-md-auto">
        <button id="searchButton" type="button" class="btn btn-outline-primary">
          <i class="ti ti-search me-1"></i>検索
        </button>
      </div>
      <div class="col-md-auto">
        <a href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/" class="btn btn-link">検索解除</a>
      </div>
    </div>
  </div>
</div>

<form action="./item/archiveAll" method="post">
  <div class="card mb-3">
    <div class="card-body">
      <div class="mb-3">
        <label for="archiveAll-note" class="form-label">アーカイブ理由</label>
        <input type="text" id="archiveAll-note" name="archiveNote" class="form-control" maxlength="50">
      </div>
      <button type="submit" class="btn btn-warning"><i class="ti ti-archive me-1"></i>一括アーカイブ</button>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-vcenter table-hover card-table">
        <thead>
          <tr>
            <th scope="col"><label class="form-check m-0"><input type="checkbox" class="form-check-input" id="checkAllArchiveAllCheckbox"><span class="form-check-label">アーカイブ</span></label></th>
            <th scope="col">商品番号
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/concatId/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/concatId/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
            </th>
            <th scope="col">商品名</th>
            <th scope="col">分類
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/categoryId/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/categoryId/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
            </th>
            <th scope="col">価格
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/price/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/price/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
            </th>
            <th scope="col">プラ</th>
            <th scope="col">付記</th>
            <th scope="col">紙</th>
            <th scope="col">付記</th>
            <th scope="col">登録日
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/createAt/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/createAt/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
            </th>
            <th scope="col">更新日
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/updateAt/direction/desc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▼</a>
              <a class="text-secondary" href="./<?php echo htmlspecialchars($v->request, ENT_QUOTES, 'UTF-8'); ?>/sortby/updateAt/direction/asc/<?php echo htmlspecialchars($v->searchUrl, ENT_QUOTES, 'UTF-8'); ?>">▲</a>
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
  </div>
</form>

<?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>

<?php }; ?>
