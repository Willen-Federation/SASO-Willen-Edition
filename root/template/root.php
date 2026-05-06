<?php $this->content = function($v) { ?>
<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
<base href="<?php echo htmlspecialchars($v->baseUrl, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="csrf-token" content="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
<link href="./css/style.css" rel="stylesheet">
<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<script defer src="./js/html5-qrcode.min.js"></script>
<script defer src="./js/scanner.js"></script>
<script defer src="./js/alpine-focus.min.js"></script>
<script defer src="./js/alpine.min.js"></script>
<title>在庫管理システム「SASO<?php echo htmlspecialchars($v->version, ENT_QUOTES, 'UTF-8'); ?>」 - <?php echo htmlspecialchars($v->insideView->getTitle(), ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
<div class="page">
  <header class="navbar navbar-expand-md navbar-dark d-print-none bg-azure">
    <div class="container-xl">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3 mb-0">
        <a href="./" class="text-white text-decoration-none">
          <i class="bi bi-box2 me-2"></i>在庫管理 SASO<?php echo htmlspecialchars($v->version, ENT_QUOTES, 'UTF-8'); ?>
        </a>
      </h1>
      <?php if($v->authed) { ?>
        <div class="navbar-nav flex-row order-md-last">
          <div class="nav-item d-none d-md-flex me-3 align-items-center text-white-50">
            <i class="bi bi-person me-2"></i>
            <span><?php echo htmlspecialchars($_SESSION['userName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>様</span>
          </div>
          <div class="nav-item">
            <a href="./start/logout/" class="btn btn-sm btn-outline-light">
              <i class="bi bi-box-arrow-right me-1"></i>ログアウト
            </a>
          </div>
        </div>
        <div class="collapse navbar-collapse" id="navbar-menu">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" href="./">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-house"></i></span>
                <span class="nav-link-title">ホーム</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./item/add/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-plus"></i></span>
                <span class="nav-link-title">商品登録</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./shelf/start/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-stack"></i></span>
                <span class="nav-link-title">棚番作成</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./label/features/">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-printer"></i></span>
                <span class="nav-link-title">商品ラベル印刷</span>
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-archive"></i></span>
                <span class="nav-link-title">アーカイブ</span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="./archive/list/">アーカイブ一覧</a>
                <a class="dropdown-item" href="./item/archivingAll/">一括アーカイブ</a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-gear"></i></span>
                <span class="nav-link-title">管理</span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="./label/start/">ラベル寸法管理</a>
                <a class="dropdown-item" href="./category/start/">分類管理</a>
                <a class="dropdown-item" href="./member/start/">メンバー管理</a>
                <a class="dropdown-item" href="./role/start/">ロール管理</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./admin/aiSettings/">
                  <i class="bi bi-cpu me-1"></i>AI設定
                </a>
                <a class="dropdown-item" href="./admin/feature-flags/">
                  <i class="bi bi-flag me-1"></i>機能フラグ
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="./auth/providerNew/">認証プロバイダーの追加</a>
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
            <h2 class="page-title"><?php echo htmlspecialchars($v->insideView->getTitle(), ENT_QUOTES, 'UTF-8'); ?></h2>
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
<script type="module" src="./js/main.js"></script>
<script defer src="./js/browser-init.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js"></script>
</body>
</html>

<?php } ?>
