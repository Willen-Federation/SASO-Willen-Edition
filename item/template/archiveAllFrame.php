<?php $this->title = '一括アーカイブ'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">一括アーカイブ</li>
</ol>
</nav>

<div class="hidden" id="current"><?php echo $v->request; ?></div>
<p><input type="text" id="search" maxlength="50"  placeholder="商品名" value="<?php echo urldecode($v->search); ?>">
<button id="searchButton" type="button">検索</button>
<a href="./<?php echo $v->request; ?>/">検索解除</a></p>

<form action="./item/archiveAll" method="post">
<p><button class="btn btn-warning">一括アーカイブ</button></p>
<p>アーカイブ理由:
<input type="text" name="archiveNote" class="input" maxlength="50"></p>
<table class="table table-striped table-hover">
<thead>
<tr>
<td><input type="checkbox" id="checkAllArchiveAllCheckbox">アーカイブ</td>
<th scope="col">商品番号
<a href="./<?php echo $v->request; ?>/sortby/concatId/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
<a href="./<?php echo $v->request; ?>/sortby/concatId/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
</th>
<th scope="col">商品名</th>
<th scope="col">分類
<a href="./<?php echo $v->request; ?>/sortby/categoryId/direction/desc/<?php echo $v->searchUrl; ?>">▼</a>
<a href="./<?php echo $v->request; ?>/sortby/categoryId/direction/asc/<?php echo $v->searchUrl; ?>">▲</a>
</th>
<th scope="col">価格
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
<?php ($v->inside)('item', 'archiveAll', $v->isArchive); ?>
</tbody>
</table>
</form>
<?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>

<?php }; ?>
