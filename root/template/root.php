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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href="./css/app.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Favicon -->
<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">

<!-- Core JS -->
<script defer src="./js/html5-qrcode.min.js"></script>
<script defer src="./js/scanner.js"></script>
<script defer src="./js/alpine-focus.min.js"></script>
<script defer src="./js/alpine-persist.min.js"></script>
<script defer src="./js/alpine.min.js"></script>
<script defer src="./js/tailadmin.js"></script>

<title><?php echo ui_text($lang === 'ja' ? '在庫管理システム' : 'Inventory Management'); ?>「SASO<?php echo htmlspecialchars($v->version, ENT_QUOTES, 'UTF-8'); ?>」 - <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body class="bg-whiter text-black dark:bg-boxdark-2 dark:text-bodydark" x-data="{ mobileOpen: false }">

  <!-- ===== Page Wrapper Start ===== -->
  <div class="flex h-screen overflow-hidden">

    <!-- ===== Sidebar Start ===== -->
    <?php require __DIR__ . '/_layout/sidebar.php'; ?>
    <!-- ===== Sidebar End ===== -->

    <!-- ===== Content Area Start ===== -->
    <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

      <!-- ===== Header Start ===== -->
      <?php require __DIR__ . '/_layout/header.php'; ?>
      <!-- ===== Header End ===== -->

      <!-- ===== Main Content Start ===== -->
      <main class="mx-auto w-full max-w-(--breakpoint-2xl) p-4 md:p-6 2xl:p-10">
        
        <!-- Breadcrumb -->
        <?php if ($authed && !empty($breadcrumb)): ?>
          <?php require __DIR__ . '/_layout/breadcrumb.php'; ?>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="mt-4">
          <?php $v->insideView->getContent()($v->insideView); ?>
        </div>
      </main>
      <!-- ===== Main Content End ===== -->
      
      <!-- ===== Footer Start ===== -->
      <?php require __DIR__ . '/_layout/footer.php'; ?>
      <!-- ===== Footer End ===== -->

    </div>
    <!-- ===== Content Area End ===== -->
  </div>
  <!-- ===== Page Wrapper End ===== -->

  <script type="module">import "./js/main.js";</script>
</body>
</html>
<?php } ?>
