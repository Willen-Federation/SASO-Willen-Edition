<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
  $dir  = $lang === 'ar' ? 'rtl' : 'ltr';
  $pageTitle = $v->insideView->getTitle();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base href="<?php echo $v->baseUrl; ?>">
  <title>SASO<?php echo $v->version; ?> - <?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
  <link rel="icon"          href="./favicon.ico" type="image/x-icon">
  <!-- Google Fonts (Noto Sans JP for Japanese) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- TailAdmin compiled CSS (build: npm run build) -->
  <?php if(file_exists(__DIR__ . '/../../css/app.css')): ?>
  <link href="./css/app.css" rel="stylesheet">
  <?php else: ?>
  <!-- Fallback: Tailwind CDN (development only — run `npm run build` for production) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          primary: '#3C50E0', secondary: '#80CAEE', bodydark: '#AEB7C0',
          bodydark1: '#DEE4EE', bodydark2: '#8A99AF', body: '#64748B',
          stroke: '#E2E8F0', graydark: '#333A48', 'gray-2': '#F7F9FC',
          whiten: '#F1F5F9', boxdark: '#24303F', 'boxdark-2': '#1A222C',
          strokedark: '#2E3A47', success: '#219653', danger: '#D34053',
          warning: '#FFA70B', 'meta-3': '#10B981', 'meta-4': '#313D4A',
          'meta-5': '#259AE6',
        },
        spacing: { '4.5': '1.125rem', '5.5': '1.375rem', '6.5': '1.625rem', '7.5': '1.875rem', '8.5': '2.125rem', '9.5': '2.375rem', '10.5': '2.625rem', '11.5': '2.875rem', '12.5': '3.125rem', '72.5': '18.125rem' },
      }
    }
  };
  </script>
  <style type="text/tailwindcss">
    @layer components {
      .sidebar-link { @apply flex items-center gap-2.5 rounded-md px-4 py-2 text-sm font-medium text-bodydark1 duration-300 ease-in-out hover:bg-graydark; }
      .sidebar-link.active { @apply bg-graydark text-white; }
      .form-input { @apply w-full rounded border border-stroke bg-transparent py-3 pl-6 pr-10 text-black outline-none focus:border-primary; }
      .form-label { @apply mb-2.5 block font-medium text-black dark:text-white; }
      .form-select { @apply relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 pl-5 pr-12 outline-none transition focus:border-primary; }
      .btn-primary { @apply flex justify-center items-center rounded bg-primary p-3 font-medium text-white hover:bg-opacity-90 transition; }
      .btn-secondary { @apply flex justify-center items-center rounded border border-stroke p-3 font-medium text-black hover:shadow-sm transition; }
      .btn-danger { @apply flex justify-center items-center rounded bg-danger p-3 font-medium text-white hover:bg-opacity-90 transition; }
      .btn-success { @apply flex justify-center items-center rounded bg-success p-3 font-medium text-white hover:bg-opacity-90 transition; }
      .btn-warning { @apply flex justify-center items-center rounded bg-warning p-3 font-medium text-black hover:bg-opacity-90 transition; }
      .btn-sm { @apply px-4 py-2 text-sm; }
      .card { @apply rounded-sm border border-stroke bg-white shadow-sm dark:border-strokedark dark:bg-boxdark; }
      .card-header { @apply border-b border-stroke px-6 py-4 dark:border-strokedark; }
      .card-body { @apply p-6; }
      .data-table { @apply w-full table-auto; }
      .data-table thead tr { @apply bg-gray-2 text-left dark:bg-meta-4; }
      .data-table thead th { @apply min-w-[120px] py-4 px-4 font-medium text-black dark:text-white; }
      .data-table tbody tr { @apply border-b border-stroke dark:border-strokedark; }
      .data-table tbody td { @apply py-3 px-4; }
      .badge { @apply inline-flex rounded-full px-3 py-1 text-sm font-medium; }
      .badge-success { @apply bg-success bg-opacity-10 text-success; }
      .badge-danger { @apply bg-danger bg-opacity-10 text-danger; }
      .badge-warning { @apply bg-warning bg-opacity-10 text-black; }
      .badge-primary { @apply bg-primary bg-opacity-10 text-primary; }
      .breadcrumb { @apply flex items-center gap-2 text-sm; }
      .breadcrumb-item a { @apply text-primary hover:underline; }
      .breadcrumb-item.active { @apply text-black dark:text-white font-medium; }
      .alert { @apply flex items-start gap-3 rounded-sm border p-4; }
      .alert-warning { @apply border-warning bg-warning bg-opacity-5; }
      .alert-danger { @apply border-danger bg-danger bg-opacity-5 text-danger; }
      .alert-success { @apply border-success bg-success bg-opacity-5 text-success; }
      .toggle { @apply relative inline-block h-6 w-11 cursor-pointer; }
      .toggle-slider { @apply absolute inset-0 rounded-full bg-stroke transition duration-300; }
      .hidden { display: none; }
      .focused { @apply ring-2 ring-primary ring-offset-1; }
    }
  </style>
  <?php endif; ?>
  <!-- Alpine.js for interactivity (no build step) -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body
  x-data="{ sidebarOpen: false, darkMode: (localStorage.getItem('darkMode') === 'true') }"
  x-init="$watch('darkMode', v => localStorage.setItem('darkMode', v))"
  :class="darkMode ? 'dark' : ''"
  class="bg-whiter dark:bg-boxdark-2"
>

<?php if (!$v->authed): ?>
<!-- ======================================================== -->
<!-- UNAUTHENTICATED: Centred wrapper (login / installer etc) -->
<!-- ======================================================== -->
<?php if(file_exists('installer/installer.json')): ?>
<div class="flex min-h-screen items-center justify-center">
  <div class="alert alert-warning max-w-lg">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div>
      <p class="font-medium">インストールが未完了です</p>
      <p class="text-sm mt-1"><a href="./installer/start" class="underline font-semibold">installer/start</a> にアクセスしてインストールを完了してください。完了後は <code>installer/</code> フォルダを削除してください。</p>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="flex min-h-screen items-center justify-center bg-gray dark:bg-boxdark-2 px-4">
  <?php $v->insideView->getContent()($v->insideView); ?>
</div>

<?php else: ?>
<!-- ======================================================== -->
<!-- AUTHENTICATED: TailAdmin Sidebar Layout                   -->
<!-- ======================================================== -->

<!-- ---- Sidebar ---- -->
<aside
  id="sidebar"
  x-cloak
  :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
  class="fixed left-0 top-0 z-9999 flex h-screen w-72.5 flex-col overflow-y-hidden bg-black dark:bg-boxdark duration-300 ease-linear lg:translate-x-0"
  aria-label="<?php echo $lang === 'ja' ? 'サイドバーナビゲーション' : 'Sidebar Navigation'; ?>"
>
  <!-- Logo -->
  <div class="flex items-center justify-between gap-2 px-6 py-5.5 lg:py-6.5">
    <a href="./" class="flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
      </svg>
      <span class="text-white text-lg font-bold">SASO <span class="text-primary text-sm"><?php echo htmlspecialchars($v->version); ?></span></span>
    </a>
    <!-- Close sidebar (mobile) -->
    <button @click="sidebarOpen = false" class="lg:hidden text-bodydark1" aria-label="サイドバーを閉じる">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>

  <!-- Nav -->
  <nav class="mt-5 overflow-y-auto px-4 pb-10 lg:mt-9 lg:px-6" aria-label="メインナビゲーション">

    <!-- Menu label -->
    <div class="mb-4">
      <h3 class="text-xs font-semibold uppercase text-bodydark2 mb-3 ml-4 tracking-widest">
        <?php echo $lang === 'ja' ? 'メインメニュー' : 'MAIN MENU'; ?>
      </h3>
      <ul class="flex flex-col gap-1.5">
        <li>
          <a href="./" class="sidebar-link" aria-label="<?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?>
          </a>
        </li>
        <!-- 商品管理 -->
        <li x-data="{ open: false }">
          <button @click="open = !open" class="sidebar-link w-full justify-between" aria-expanded="false" :aria-expanded="open.toString()">
            <span class="flex items-center gap-2.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              <?php echo $lang === 'ja' ? '商品管理' : 'Products'; ?>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <ul x-show="open" x-transition class="mt-1 ml-4 flex flex-col gap-1">
            <li><a href="./item/add/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? '商品登録' : 'Register Product'; ?></a></li>
            <li><a href="./item/start/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? '商品一覧' : 'Product List'; ?></a></li>
            <li><a href="./archive/list/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? 'アーカイブ一覧' : 'Archive List'; ?></a></li>
            <li><a href="./item/archivingAll/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? '一括アーカイブ' : 'Bulk Archive'; ?></a></li>
          </ul>
        </li>
        <!-- ラベル印刷 -->
        <li x-data="{ open: false }">
          <button @click="open = !open" class="sidebar-link w-full justify-between" :aria-expanded="open.toString()">
            <span class="flex items-center gap-2.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              <?php echo $lang === 'ja' ? 'ラベル印刷' : 'Label Printing'; ?>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <ul x-show="open" x-transition class="mt-1 ml-4 flex flex-col gap-1">
            <li><a href="./label/features/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? '商品ラベル印刷' : 'Print Product Labels'; ?></a></li>
            <li><a href="./barcode/sheet/" class="sidebar-link text-sm flex items-center gap-1">
              <span class="badge badge-primary text-xs px-1.5 py-0.5">NEW</span>
              <?php echo $lang === 'ja' ? 'バーコードシート印刷' : 'Print Barcode Sheets'; ?>
            </a></li>
            <li><a href="./item/fromBarcode/" class="sidebar-link text-sm flex items-center gap-1">
              <span class="badge badge-primary text-xs px-1.5 py-0.5">NEW</span>
              <?php echo $lang === 'ja' ? 'バーコードから商品登録' : 'Register from Barcode'; ?>
            </a></li>
            <li><a href="./label/start/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? 'ラベル寸法管理' : 'Label Size Mgmt'; ?></a></li>
          </ul>
        </li>
        <!-- 棚番管理 -->
        <li x-data="{ open: false }">
          <button @click="open = !open" class="sidebar-link w-full justify-between" :aria-expanded="open.toString()">
            <span class="flex items-center gap-2.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
              <?php echo $lang === 'ja' ? '棚番管理' : 'Shelf Mgmt'; ?>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <ul x-show="open" x-transition class="mt-1 ml-4 flex flex-col gap-1">
            <li><a href="./shelf/simple/" class="sidebar-link text-sm flex items-center gap-1">
              <span class="badge badge-primary text-xs px-1.5 py-0.5">NEW</span>
              <?php echo $lang === 'ja' ? '棚番簡易設定' : 'Quick Shelf Setup'; ?>
            </a></li>
            <li><a href="./shelf/start/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? '棚番作成（詳細）' : 'Shelf Creation'; ?></a></li>
            <li><a href="./shelf/label/" class="sidebar-link text-sm"><?php echo $lang === 'ja' ? '棚番シール印刷' : 'Print Shelf Labels'; ?></a></li>
          </ul>
        </li>
        <!-- データ照合 -->
        <li>
          <a href="./verify/start/" class="sidebar-link flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span class="flex items-center gap-1">
              <span class="badge badge-primary text-xs px-1.5 py-0.5">NEW</span>
              <?php echo $lang === 'ja' ? 'データ照合' : 'Data Verify'; ?>
            </span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Admin menu -->
    <div class="mb-4 mt-6">
      <h3 class="text-xs font-semibold uppercase text-bodydark2 mb-3 ml-4 tracking-widest">
        <?php echo $lang === 'ja' ? '管理' : 'ADMINISTRATION'; ?>
      </h3>
      <ul class="flex flex-col gap-1.5">
        <li>
          <a href="./category/start/" class="sidebar-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            <?php echo $lang === 'ja' ? '分類管理' : 'Categories'; ?>
          </a>
        </li>
        <!-- Feature Flags -->
        <li>
          <a href="./admin/feature-flags/" class="sidebar-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
            <?php echo $lang === 'ja' ? 'フィーチャーフラグ' : 'Feature Flags'; ?>
          </a>
        </li>
        <!-- External Auth -->
        <li>
          <a href="./admin/auth-providers/" class="sidebar-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <?php echo $lang === 'ja' ? '外部認証設定' : 'Auth Providers'; ?>
          </a>
        </li>
        <!-- Mobile連携 -->
        <li>
          <a href="./admin/mobile/" class="sidebar-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <?php echo $lang === 'ja' ? 'モバイル連携' : 'Mobile Connect'; ?>
          </a>
        </li>
        <li>
          <a href="./start/password/" class="sidebar-link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <?php echo $lang === 'ja' ? 'パスワード変更' : 'Change Password'; ?>
          </a>
        </li>
      </ul>
    </div>

  </nav>
</aside>

<!-- ---- Main content area ---- -->
<div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden lg:ml-72.5">

  <!-- Header -->
  <header class="sticky top-0 z-999 flex w-full bg-white drop-shadow-1 dark:bg-boxdark dark:drop-shadow-none" role="banner">
    <div class="flex flex-grow items-center justify-between px-4 py-4 shadow-2 md:px-6 2xl:px-11">
      <div class="flex items-center gap-2 sm:gap-4 lg:hidden">
        <!-- Hamburger -->
        <button @click="sidebarOpen = true" class="block rounded-sm border border-stroke bg-white p-1.5 shadow-sm dark:border-strokedark dark:bg-boxdark lg:hidden" aria-label="メニューを開く">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
        </button>
      </div>

      <!-- Page title (desktop) -->
      <div class="hidden lg:block">
        <nav aria-label="パンくずリスト">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pageTitle); ?></li>
          </ol>
        </nav>
      </div>

      <!-- Right: dark mode + language + user -->
      <div class="flex items-center gap-3 2xsm:gap-7">
        <!-- Dark mode toggle -->
        <button @click="darkMode = !darkMode" class="flex h-8.5 w-8.5 items-center justify-center rounded-full border border-stroke bg-gray hover:text-primary dark:border-strokedark dark:bg-meta-4" :aria-label="darkMode ? 'ライトモードに切り替え' : 'ダークモードに切り替え'">
          <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
          <svg x-show="darkMode" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>

        <!-- Language selector -->
        <div x-data="{ langOpen: false }" class="relative">
          <button @click="langOpen = !langOpen" class="flex items-center gap-1 text-sm font-medium" aria-haspopup="true" :aria-expanded="langOpen.toString()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
            <?php echo strtoupper($lang); ?>
          </button>
          <div x-show="langOpen" @click.away="langOpen = false" x-transition class="absolute right-0 mt-2 w-28 rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark z-50" role="menu">
            <a href="?lang=ja" class="block px-4 py-2 text-sm hover:bg-gray dark:hover:bg-meta-4 <?php echo $lang === 'ja' ? 'font-semibold text-primary' : ''; ?>" role="menuitem" lang="ja">日本語</a>
            <a href="?lang=en" class="block px-4 py-2 text-sm hover:bg-gray dark:hover:bg-meta-4 <?php echo $lang === 'en' ? 'font-semibold text-primary' : ''; ?>" role="menuitem" lang="en">English</a>
          </div>
        </div>

        <!-- User info + logout -->
        <div x-data="{ userOpen: false }" class="relative">
          <button @click="userOpen = !userOpen" class="flex items-center gap-2" aria-haspopup="true" :aria-expanded="userOpen.toString()">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white text-sm font-bold">
              <?php echo mb_substr(htmlspecialchars($_SESSION['userName'] ?? 'U'), 0, 1); ?>
            </div>
            <span class="hidden text-sm font-medium text-black dark:text-white md:block"><?php echo htmlspecialchars($_SESSION['userName'] ?? ''); ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div x-show="userOpen" @click.away="userOpen = false" x-transition class="absolute right-0 mt-2 w-44 rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark z-50" role="menu">
            <a href="./start/password/" class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-gray dark:hover:bg-meta-4" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
              <?php echo $lang === 'ja' ? 'パスワード変更' : 'Change Password'; ?>
            </a>
            <hr class="border-stroke dark:border-strokedark">
            <a href="./start/logout/" class="flex items-center gap-2 px-4 py-3 text-sm text-danger hover:bg-gray dark:hover:bg-meta-4" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
              <?php echo $lang === 'ja' ? 'ログアウト' : 'Logout'; ?>
            </a>
          </div>
        </div>

      </div>
    </div>
  </header>

  <?php if(file_exists('installer/installer.json')): ?>
  <div class="alert alert-warning mx-4 mt-4" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div>
      <p class="font-medium"><?php echo $lang === 'ja' ? 'インストールが未完了です' : 'Installation not complete'; ?></p>
      <p class="text-sm mt-1"><a href="./installer/start" class="underline"><?php echo $lang === 'ja' ? 'installer/start にアクセスしてください' : 'Visit installer/start'; ?></a></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Page content -->
  <main id="main-content" class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10" role="main" aria-label="<?php echo htmlspecialchars($pageTitle); ?>">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-black dark:text-white"><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    <?php $v->insideView->getContent()($v->insideView); ?>
  </main>

</div><!-- /main content -->

<!-- Sidebar overlay (mobile) -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-9998 bg-black bg-opacity-50 lg:hidden" aria-hidden="true"></div>

<?php endif; // authed ?>

<!-- Legacy JS (kept for barcode/category/etc.) -->
<script type="module">import "./js/main.js";</script>
</body>
</html>
<?php } ?>
