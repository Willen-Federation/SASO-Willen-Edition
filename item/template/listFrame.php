<?php $this->content = function($v) {
  $sortLink = static function (string $col) use ($v): array {
    $base = './'.$v->request.'/sortby/'.$col.'/direction/';
    return [
      'desc' => $base.'desc/'.$v->searchUrl,
      'asc'  => $base.'asc/'.$v->searchUrl,
    ];
  };
  $sortConcatId = $sortLink('concatId');
  $sortCategory = $sortLink('categoryId');
  $sortPrice    = $sortLink('price');
  $sortCreate   = $sortLink('createAt');
  $sortUpdate   = $sortLink('updateAt');
?>

<div class="hidden" id="current"><?php echo $v->request; ?></div>

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
