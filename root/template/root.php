<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
  $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="<?php echo ui_attr($lang); ?>" dir="ltr">
<head>
<base href="<?php echo $v->baseUrl; ?>">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="./css/style.css" rel="stylesheet">
<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<script defer src="./js/html5-qrcode.min.js"></script>
<script defer src="./js/scanner.js"></script>
<script defer src="./js/alpine-focus.min.js"></script>
<script defer src="./js/alpine.min.js"></script>
<title><?php echo ui_text($lang === 'ja' ? '在庫管理システム' : 'Inventory Management'); ?>「SASO<?php echo htmlspecialchars($v->version, ENT_QUOTES, 'UTF-8'); ?>」 - <?php echo htmlspecialchars($v->insideView->getTitle(), ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
<div class="page">
  <header class="navbar navbar-expand-md navbar-dark d-print-none bg-azure">
    <div class="container-xl">
      <?php if($v->authed) { ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="<?php echo ui_attr($lang === 'ja' ? 'ナビゲーションを開閉' : 'Toggle navigation'); ?>">
        <span class="navbar-toggler-icon"></span>
      </button>
      <?php } ?>
      <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3 mb-0">
        <a href="./" class="text-white text-decoration-none">
          <i class="bi bi-box2 me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? '在庫管理' : 'Inventory'); ?> SASO<?php echo htmlspecialchars($v->version, ENT_QUOTES, 'UTF-8'); ?>
        </a>
      </h1>
      <?php if($v->authed) { ?>
        <div class="navbar-nav flex-row order-md-last align-items-center gap-2">
          <form method="post" action="/locale/set/<?php echo $lang === 'ja' ? 'en' : 'ja'; ?>/" class="m-0">
            <input type="hidden" name="return" value="<?php echo ui_attr($currentPath); ?>">
            <button type="submit" class="btn btn-sm btn-outline-light" aria-label="<?php echo ui_attr($lang === 'ja' ? 'Englishに切り替え' : 'Switch to Japanese'); ?>">
              <?php echo ui_text($lang === 'ja' ? 'EN' : 'JA'); ?>
            </button>
          </form>
          <div class="nav-item dropdown">
            <a href="#" class="nav-link d-flex lh-1 text-reset p-0 text-white" data-bs-toggle="dropdown" aria-label="<?php echo ui_attr($lang === 'ja' ? 'ユーザーメニューを開く' : 'Open user menu'); ?>" aria-expanded="false">
              <span class="avatar avatar-sm bg-white text-azure" aria-hidden="true"><i class="bi bi-person"></i></span>
              <span class="d-none d-xl-block ps-2">
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['userName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
              <a class="dropdown-item" href="./mypage/start/"><i class="bi bi-person-lines-fill me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'プロフィール' : 'Profile'); ?></a>
              <a class="dropdown-item" href="./start/password/"><i class="bi bi-key me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'パスワード変更' : 'Change Password'); ?></a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="./start/logout/"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'ログアウト' : 'Logout'); ?></a>
            </div>
          </div>
        </div>
        <div class="collapse navbar-collapse" id="navbar-menu">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" href="./">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-house"></i></span>
                <span class="nav-link-title"><?php echo ui_text($lang === 'ja' ? 'ホーム' : 'Home'); ?></span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./item/add/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-plus"></i></span>
                <span class="nav-link-title"><?php echo ui_text($lang === 'ja' ? '商品登録' : 'Add Item'); ?></span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./shelf/start/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-stack"></i></span>
                <span class="nav-link-title"><?php echo ui_text($lang === 'ja' ? '棚番作成' : 'Shelves'); ?></span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./label/features/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-printer"></i></span>
                <span class="nav-link-title"><?php echo ui_text($lang === 'ja' ? '商品ラベル印刷' : 'Item Labels'); ?></span>
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-archive"></i></span>
                <span class="nav-link-title"><?php echo ui_text($lang === 'ja' ? 'アーカイブ' : 'Archive'); ?></span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="./archive/list/"><?php echo ui_text($lang === 'ja' ? 'アーカイブ一覧' : 'Archive List'); ?></a>
                <a class="dropdown-item" href="./item/archivingAll/"><?php echo ui_text($lang === 'ja' ? '一括アーカイブ' : 'Bulk Archive'); ?></a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-gear"></i></span>
                <span class="nav-link-title"><?php echo ui_text($lang === 'ja' ? '管理' : 'Admin'); ?></span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="./label/start/"><?php echo ui_text($lang === 'ja' ? 'ラベル寸法管理' : 'Label Sizes'); ?></a>
                <a class="dropdown-item" href="./shelf/simple/"><?php echo ui_text($lang === 'ja' ? '棚番号ラベルシート' : 'Shelf Label Sheet'); ?></a>
                <a class="dropdown-item" href="./category/start/"><?php echo ui_text($lang === 'ja' ? '分類管理' : 'Categories'); ?></a>
                <a class="dropdown-item" href="./member/start/"><?php echo ui_text($lang === 'ja' ? 'メンバー管理' : 'Members'); ?></a>
                <a class="dropdown-item" href="./role/start/"><?php echo ui_text($lang === 'ja' ? 'ロール管理' : 'Roles'); ?></a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./admin/aiSettings/">
                  <i class="bi bi-cpu me-1" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'AI設定' : 'AI Settings'); ?>
                </a>
                <a class="dropdown-item" href="./admin/feature-flags/">
                  <i class="bi bi-flag me-1" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? '機能フラグ' : 'Feature Flags'); ?>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./auth/provider/new/"><?php echo ui_text($lang === 'ja' ? '認証プロバイダーの追加' : 'Add Auth Provider'); ?></a>
                <a class="dropdown-item" href="./admin/auth-providers/"><?php echo ui_text($lang === 'ja' ? '認証プロバイダー一覧' : 'Auth Providers'); ?></a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./start/password/"><?php echo ui_text($lang === 'ja' ? 'パスワード変更' : 'Change Password'); ?></a>
              </div>
            </li>
          </ul>
        </div>
    <?php } ?>
    </div>
</header>
<div class="page-wrapper">
  <div class="container-xl py-3">
    <?php if($v->authed) { ?>
    <div class="row align-items-center mb-3">
      <div class="col">
        <h1 class="page-title m-0"><?php echo ui_text($v->insideView->getTitle()); ?></h1>
      </div>
      <div class="col-auto">
        <p class="text-success m-0">
          <?php echo ui_text(($_SESSION['userName'] ?? '') . ($lang === 'ja' ? '様ログイン中。' : ' is signed in.')); ?>
          <a class="btn btn-secondary btn-sm ms-2" href="./start/logout/">
            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'ログアウト' : 'Logout'); ?>
          </a>
        </p>
      </div>
    </div>
    <?php } ?>
    <div class="row">
      <div class="col-12">
        <?php $v->insideView->getContent()($v->insideView); ?>
      </div>
    </div>
  </div>
</div>
</div><!-- /.page -->
<script type="module">import "./js/main.js";</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>

<?php } ?>
