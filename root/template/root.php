<?php
/*
 * Root layout. Loaded by RootView::display() inside the View instance — the
 * surrounding closure is bound to $this == RootView. The closure receives
 * the root view ($v) which carries:
 *   - $v->baseUrl          string
 *   - $v->version          string
 *   - $v->authed           bool
 *   - $v->matter, $v->action  string  (for active-menu detection)
 *   - $v->insideView       View      (the page-specific inside view)
 *   - $v->currentLocale    string
 *   - $v->supportedLocales list<string>
 *   - $v->sidebar          list<array{type,label,items}>
 *   - $v->breadcrumb       list<array{label,href?}>
 *   - $v->userName         string
 *
 * Helpers ui()/ui_attr()/ui_text()/__() are auto-loaded via composer.json's
 * `autoload.files` (see framework/ui/helpers.php and src/Infrastructure/Translation/functions.php).
 */
$this->content = function ($v) {
    $title = $v->insideView->getTitle();
    $activeKey = $v->matter; // marks the current sidebar item
?>
<!DOCTYPE html>
<html lang="<?php echo ui_attr($v->currentLocale); ?>" dir="ltr">
<head>
  <base href="<?php echo ui_attr($v->baseUrl); ?>">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./css/tailadmin.css?v=<?php echo $v->version; ?>">
  <link rel="stylesheet" href="./css/style.css?v=<?php echo time(); ?>">
  <link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
  <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
  <title><?php echo ui_text(__('ui.app.title', ['version' => $v->version], null, 'SASO {version}')); ?> &mdash; <?php echo ui_text($title); ?></title>
  <script defer src="./js/alpine-persist.min.js"></script>
  <script defer src="./js/alpine-focus.min.js"></script>
  <script defer src="./js/alpine.min.js"></script>
  <script src="./js/tailadmin.js"></script>
  <script src="./js/html5-qrcode.min.js"></script>
  <script src="./js/scanner.js"></script>
  <style>[x-cloak]{display:none!important}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="./">在庫管理システム『SASO<?php echo $v->version; ?>』</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <?php if($v->authed) { ?>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" aria-current="page" href="./">ホーム</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./item/add/">商品登録</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./shelf/start/">棚番作成</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="./label/features/">商品ラベル印刷</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownArchive" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            アーカイブ
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="./archive/list/">アーカイブ一覧</a></li>
            <li><a class="dropdown-item" href="./item/archivingAll/">一括アーカイブ</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            管理
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="./label/start/">ラベル寸法管理</a></li>
            <li><a class="dropdown-item" href="./category/start/">分類管理</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="./auth/provider/new/">認証プロバイダーの追加</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="./start/password/">パスワード変更</a></li>
          </ul>
        </li>
      </ul>
      <?php if(false) { ?>
      <!--<form class="d-flex">
        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
      </form>-->
      <?php } ?>
    </div>
    <?php } ?>
  </div>
</nav>
<?php if(file_exists('installer/installer.json')){ ?>
<div class="alert alert-warning d-flex align-items-center" role="alert">
<i class="bi flex-shrink-0 me-2 bi-exclamation-triangle-fill"></i>
<p>まだインストールが済んでいないなら、「<a class="alert-link" href="./installer/start">installer/start</a>」にアクセスして下さい。
<br>すでにインストール済みなら、フォルダ「installer」を削除して下さい。</p>
</div>
<?php } ?>

  <div class="flex min-h-screen flex-col lg:flex-row">
    <?php
    $authed = $v->authed;
    $version = $v->version;
    $sidebar = $v->sidebar;
    require __DIR__ . '/_layout/sidebar.php';
    ?>

    <div class="flex w-full flex-1 flex-col">
      <?php
      $userName = $_SESSION['userName'] ?? null;
      $currentLocale = $v->currentLocale;
      $supportedLocales = $v->supportedLocales;
      require __DIR__ . '/_layout/header.php';
      ?>

      <main id="main-content" tabindex="-1" class="flex-1 px-4 py-6 lg:px-6 lg:py-8">
        <?php require __DIR__ . '/_layout/installer_alert.php'; ?>

        <?php
        $breadcrumb = $v->breadcrumb;
        require __DIR__ . '/_layout/breadcrumb.php';
        ?>

        <?php $v->insideView->getContent()($v->insideView); ?>
      </main>

      <?php require __DIR__ . '/_layout/footer.php'; ?>
    </div>
  </div>

  <script type="module">import "./js/main.js";</script>
</body>
</html>
<?php
};
