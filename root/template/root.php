<?php $this->content = function($v) { ?>
<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<base href="<?php echo $v->baseUrl; ?>">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/tabler-icons.min.css">
<link href="./css/style.css" rel="stylesheet">
<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<title>在庫管理システム「SASO<?php echo $v->version; ?>」 - <?php echo $v->insideView->getTitle(); ?></title>
</head>
<body>
<div class="page">
  <header class="navbar navbar-expand-md navbar-dark d-print-none" style="background-color: #066fd1;">
    <div class="container-xl">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3 mb-0">
        <a href="./" class="text-white text-decoration-none">
          <i class="ti ti-packages me-2"></i>在庫管理 SASO<?php echo $v->version; ?>
        </a>
      </h1>
      <?php if($v->authed) { ?>
        <div class="navbar-nav flex-row order-md-last">
          <div class="nav-item d-none d-md-flex me-3 align-items-center text-white-50">
            <i class="ti ti-user me-2"></i>
            <span><?php echo htmlspecialchars($_SESSION['userName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>様</span>
          </div>
          <div class="nav-item">
            <a href="./start/logout/" class="btn btn-sm btn-outline-light">
              <i class="ti ti-logout me-1"></i>ログアウト
            </a>
          </div>
        </div>
        <div class="collapse navbar-collapse" id="navbar-menu">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" href="./">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-home"></i></span>
                <span class="nav-link-title">ホーム</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./item/add/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-plus"></i></span>
                <span class="nav-link-title">商品登録</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./shelf/start/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-stack-2"></i></span>
                <span class="nav-link-title">棚番作成</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./label/features/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-printer"></i></span>
                <span class="nav-link-title">商品ラベル印刷</span>
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-archive"></i></span>
                <span class="nav-link-title">アーカイブ</span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="./archive/list/">アーカイブ一覧</a>
                <a class="dropdown-item" href="./item/archivingAll/">一括アーカイブ</a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-settings"></i></span>
                <span class="nav-link-title">管理</span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="./label/start/">ラベル寸法管理</a>
                <a class="dropdown-item" href="./category/start/">分類管理</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./auth/provider/new/">認証プロバイダーの追加</a>
                <a class="dropdown-item" href="./auth/providers/">認証プロバイダー一覧</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./start/password/">パスワード変更</a>
              </div>
            </li>
          </ul>
        </div>
      <?php } ?>
    </div>
  </header>
  <div class="page-wrapper">
    <div class="page-header d-print-none">
      <div class="container-xl">
        <div class="row g-2 align-items-center">
          <div class="col">
            <h2 class="page-title"><?php echo $v->insideView->getTitle(); ?></h2>
          </div>
        </div>
      </div>
    </div>
    <div class="page-body">
      <div class="container-xl">
        <?php $v->insideView->getContent()($v->insideView); ?>
      </div>
    </div>
  </div>
</div>
<script type="module">import "./js/main.js";</script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js"></script>
</body>
</html>

<?php } ?>
