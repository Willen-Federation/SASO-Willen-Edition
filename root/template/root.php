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
  <style>[x-cloak]{display:none!important}</style>
</head>
<body x-data="taSidebar()" class="bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-300">
  <?php require __DIR__ . '/_layout/skip_link.php'; ?>

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
