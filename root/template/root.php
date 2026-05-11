<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
  $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
  
  // Variables for partials
  $authed = $v->authed;
  $userName = $_SESSION['userName'] ?? null;
  $version = $v->version;
  $sidebar = $v->sidebar ?? [];
  $breadcrumb = $v->breadcrumb ?? [];
  $title = $v->insideView->getTitle();
  $activeKey = $v->matter; // Simple active key mapping
  $currentLocale = $v->currentLocale;
  $supportedLocales = $v->supportedLocales;
?>
<!DOCTYPE html>
<html lang="<?php echo ui_attr($lang); ?>" dir="ltr" x-data="taTheme()" :class="theme">
<head>
<base href="<?php echo $v->baseUrl; ?>">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Primary CSS -->
<link href="./css/app.css" rel="stylesheet">
<link href="./css/tailadmin.css" rel="stylesheet">

<!-- Favicon -->
<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">

<!-- Core JS — tailadmin.js MUST load before alpine.min.js so its `alpine:init`
     listener is registered before Alpine starts walking the DOM. -->
<script defer src="./js/html5-qrcode.min.js"></script>
<script defer src="./js/scanner.js"></script>
<script defer src="./js/alpine-focus.min.js"></script>
<script defer src="./js/alpine-persist.min.js"></script>
<script defer src="./js/tailadmin.js"></script>
<script defer src="./js/alpine.min.js"></script>

<title><?php echo ui_text($lang === 'ja' ? '在庫管理システム' : 'Inventory Management'); ?>「SASO<?php echo htmlspecialchars($v->version, ENT_QUOTES, 'UTF-8'); ?>」 - <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body class="bg-whiter text-black dark:bg-boxdark-2 dark:text-bodydark" x-data="{ mobileOpen: false }">
  <?php require __DIR__ . '/_layout/skip_link.php'; ?>

  <?php if ($authed): ?>
    <!-- ===== Authenticated App Shell ===== -->
    <div class="flex h-screen overflow-hidden">

      <?php require __DIR__ . '/_layout/sidebar.php'; ?>

      <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

        <?php require __DIR__ . '/_layout/header.php'; ?>

        <main id="main-content" class="mx-auto w-full max-w-(--breakpoint-2xl) p-4 md:p-6 2xl:p-10">
          <?php if (!empty($breadcrumb)): ?>
            <?php require __DIR__ . '/_layout/breadcrumb.php'; ?>
          <?php endif; ?>

          <div class="mt-4">
            <?php $v->insideView->getContent()($v->insideView); ?>
          </div>
        </main>

        <?php require __DIR__ . '/_layout/footer.php'; ?>

      </div>
    </div>
  <?php else: ?>
    <!-- ===== Unauthenticated Chromeless Layout ===== -->
    <?php require __DIR__ . '/_layout/auth_controls.php'; ?>

    <div class="min-h-screen flex flex-col">
      <main id="main-content" class="flex-1 flex flex-col items-center justify-center gap-6 p-4">
        <!-- Brand mark — single visual anchor on the chromeless login screen. -->
        <a href="./" class="flex items-center gap-3" aria-label="SASO">
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500 text-white text-xl font-bold shadow-md">S</span>
          <span class="text-2xl font-semibold tracking-tight text-gray-800 dark:text-white/90">
            SASO <span class="text-base font-medium text-gray-500 dark:text-gray-400">v<?php echo ui_text((string) $version); ?></span>
          </span>
        </a>

        <?php $v->insideView->getContent()($v->insideView); ?>
      </main>

      <?php require __DIR__ . '/_layout/footer.php'; ?>
    </div>
  <?php endif; ?>

  <script type="module">import "./js/main.js";</script>
</body>
</html>
<?php } ?>
