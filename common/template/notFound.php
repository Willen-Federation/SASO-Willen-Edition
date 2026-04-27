<?php $this->title = 'ページが見つかりません'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>
<div class="flex flex-col items-center justify-center py-24 text-center">
  <div class="mb-6 text-8xl font-bold text-primary opacity-20">404</div>
  <h1 class="mb-3 text-2xl font-bold text-black dark:text-white">
    <?php echo $lang === 'ja' ? 'ページが見つかりません' : 'Page Not Found'; ?>
  </h1>
  <p class="mb-8 text-body dark:text-bodydark">
    <?php echo $lang === 'ja' ? 'お探しのページは存在しないか、移動した可能性があります。' : 'The page you are looking for does not exist or has been moved.'; ?>
  </p>
  <a href="./" class="btn-primary px-8">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    <?php echo $lang === 'ja' ? 'ホームへ戻る' : 'Back to Home'; ?>
  </a>
</div>
<?php }; ?>
