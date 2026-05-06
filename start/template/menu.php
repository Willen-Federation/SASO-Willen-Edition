<?php $this->content = function ($v) { ?>

<?php
  $sections = [
    [
      'title' => '商品管理',
      'items' => [
        ['./item/add/',                   '商品登録',         'bi-plus-circle',    'primary'],
        ['./item/registerFromImage/',     '画像から商品登録', 'bi-file-image',     'primary'],
        ['./item/fromBarcode/',           'バーコード登録',   'bi-qr-code',        'success'],
        ['./item/draftList/',             '下書き一覧',       'bi-file-earmark-text', 'warning'],
        ['./item/list/',                  '商品一覧',         'bi-list-ul',        'primary'],
        ['./category/start/',             '分類管理',         'bi-diagram-3',      'secondary'],
      ],
    ],
    [
      'title' => 'バーコード・ラベル',
      'items' => [
        ['./barcode/sheet/',              'バーコードシート発行', 'bi-qr-code',    'success'],
        ['./label/wizard/',               'ラベルファースト',     'bi-magic',      'primary'],
        ['./label/features/',             '商品ラベル印刷',       'bi-printer',    'warning'],
        ['./label/start/',                'ラベル寸法管理',       'bi-rulers',     'secondary'],
      ],
    ],
    [
      'title' => '棚番管理',
      'items' => [
        ['./shelf/simple/',               '棚番簡易作成', 'bi-grid-3x3',  'success'],
        ['./shelf/start/',                '棚番作成',     'bi-stack',     'primary'],
      ],
    ],
    [
      'title' => '在庫・照合',
      'items' => [
        ['./verify/start/',               'データ照合',       'bi-check-circle',   'primary'],
        ['./archive/list/',               'アーカイブ一覧',   'bi-archive',        'secondary'],
        ['./item/archivingAll/',          '一括アーカイブ',   'bi-boxes',          'warning'],
      ],
    ],
    [
      'title' => 'システム管理',
      'items' => [
        ['./mypage/start/',               'マイページ',       'bi-person-circle',  'primary'],
        ['./auth/providers/',             '認証プロバイダー', 'bi-shield-lock',    'primary'],
        ['./member/start/',               'メンバー管理',     'bi-people',         'primary'],
        ['./role/start/',                 'ロール管理',       'bi-shield-check',   'info'],
        ['./admin/feature-flags/',        'フィーチャーフラグ', 'bi-flag',         'warning'],
        ['./admin/aiSettings/',           'AI設定',           'bi-cpu',            'warning'],
        ['./settingAdmin/start/',         'システム設定',     'bi-gear',           'secondary'],
        ['./start/password/',             'パスワード変更',   'bi-key',            'secondary'],
      ],
    ],
  ];
?>

<?php foreach ($sections as $section): ?>
  <div class="mb-4">
    <h3 class="text-secondary text-uppercase fs-5 mb-3"><?php echo htmlspecialchars($section['title']); ?></h3>
    <div class="row row-cards">
      <?php foreach ($section['items'] as [$href, $label, $icon, $tone]): ?>
        <div class="col-4 col-md-3 col-lg-2">
          <a href="<?php echo htmlspecialchars($href); ?>" class="card card-link card-link-pop h-100">
            <div class="card-body text-center py-4">
              <span class="avatar avatar-xl avatar-rounded bg-<?php echo htmlspecialchars($tone); ?>-lt mb-3">
                <i class="bi <?php echo htmlspecialchars($icon); ?>" style="font-size: 2rem;"></i>
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
