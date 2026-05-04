<?php $this->content = function($v) { ?>

<div class="row row-cards mb-3">
  <?php
    $items = [
      ['./item/add/',           '商品登録',       'ti-plus'],
      ['./shelf/start/',        '棚番作成',       'ti-stack-2'],
      ['./label/features/',     '商品ラベル印刷', 'ti-printer'],
      ['./archive/list/',       'アーカイブ一覧', 'ti-archive'],
      ['./item/archivingAll/',  '一括アーカイブ', 'ti-archive-off'],
      ['./label/start/',        'ラベル寸法管理', 'ti-ruler-measure'],
      ['./category/start/',     '分類管理',       'ti-list-tree'],
      ['./start/password/',     'パスワード変更', 'ti-key'],
    ];
    foreach ($items as [$href, $label, $icon]):
  ?>
    <div class="col-6 col-md-4 col-lg-3">
      <a href="<?php echo $href; ?>" class="card card-link card-link-pop h-100">
        <div class="card-body text-center">
          <i class="ti <?php echo $icon; ?> text-primary" style="font-size: 1.75rem;"></i>
          <div class="mt-2 fw-medium"><?php echo $label; ?></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php }; ?>
