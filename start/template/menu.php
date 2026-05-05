<?php $this->content = function ($v) { ?>

<?php
  $sections = [
    [
      'title' => '商品管理',
      'items' => [
        ['./item/add/',                   '商品登録',         'ti-plus',           'primary'],
        ['./item/registerFromImage/',     '画像から商品登録', 'ti-photo-plus',     'primary'],
        ['./item/registerFromBarcode/',   'バーコード登録',   'ti-barcode',        'success'],
        ['./item/draftList/',             '下書き一覧',       'ti-file-pencil',    'warning'],
        ['./item/listFrame/',             '商品一覧',         'ti-list',           'primary'],
        ['./category/start/',             '分類管理',         'ti-list-tree',      'secondary'],
      ],
    ],
    [
      'title' => 'バーコード・ラベル',
      'items' => [
        ['./barcode/sheet/',              'バーコードシート発行', 'ti-qrcode',     'success'],
        ['./label/wizard/',               'ラベルファースト',     'ti-wand',       'primary'],
        ['./label/features/',             '商品ラベル印刷',       'ti-printer',    'warning'],
        ['./label/start/',                'ラベル寸法管理',       'ti-ruler-measure', 'secondary'],
      ],
    ],
    [
      'title' => '棚番管理',
      'items' => [
        ['./shelf/simple/',               '棚番簡易作成', 'ti-grid-dots',  'success'],
        ['./shelf/start/',                '棚番作成',     'ti-stack-2',    'primary'],
      ],
    ],
    [
      'title' => '在庫・照合',
      'items' => [
        ['./verify/start/',               'データ照合',       'ti-check',          'primary'],
        ['./archive/list/',               'アーカイブ一覧',   'ti-archive',        'secondary'],
        ['./item/archivingAll/',          '一括アーカイブ',   'ti-archive-off',    'warning'],
      ],
    ],
    [
      'title' => 'システム管理',
      'items' => [
        ['./auth/providers/',             '認証プロバイダー', 'ti-shield-lock',    'primary'],
        ['./member/start/',               'メンバー管理',     'ti-users',          'primary'],
        ['./admin/feature-flags/',        'フィーチャーフラグ', 'ti-flag',         'warning'],
        ['./admin/aiSettings/',           'AI設定',           'ti-robot',          'warning'],
        ['./settingAdmin/start/',         'システム設定',     'ti-settings',       'secondary'],
        ['./start/password/',             'パスワード変更',   'ti-key',            'secondary'],
      ],
    ],
  ];
?>

<?php foreach ($sections as $section): ?>
  <div class="mb-4">
    <h3 class="text-secondary text-uppercase fs-5 mb-3"><?php echo htmlspecialchars($section['title']); ?></h3>
    <div class="row row-cards">
      <?php foreach ($section['items'] as [$href, $label, $icon, $tone]): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?php echo htmlspecialchars($href); ?>" class="card card-link card-link-pop h-100">
            <div class="card-body text-center py-4">
              <span class="avatar avatar-rounded bg-<?php echo htmlspecialchars($tone); ?>-lt mb-3">
                <i class="ti <?php echo htmlspecialchars($icon); ?> fs-3"></i>
              </span>
              <div class="fw-medium"><?php echo htmlspecialchars($label); ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php }; ?>
