<?php
/** @var \saso\mypage\MyPageErrorView $v */
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$t = static fn(string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
?>
<?php $this->title = $t('エラー', 'Error'); ?>
<?php $this->content = function ($v) { ?>
<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$t = static fn(string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
?>

<div class="flex justify-center px-4 py-8">
  <div class="w-full max-w-lg">
    <div class="ta-alert ta-alert-danger" role="alert">
      <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-.75-5.25a.75.75 0 0 0 1.5 0V10a.75.75 0 0 0-1.5 0v2.75zm0-5.5a.75.75 0 0 0 1.5 0v-.25a.75.75 0 0 0-1.5 0v.25z" clip-rule="evenodd"/>
      </svg>
      <div>
        <p class="font-semibold"><?php echo ui_text($t('エラーが発生しました', 'An error occurred')); ?></p>
        <p class="mt-1 text-sm"><?php echo ui_text($v->message ?? $t('エラーが発生しました', 'An error occurred')); ?></p>
        <p class="mt-3">
          <a href="/start/start/"
             class="text-sm font-medium underline underline-offset-2 hover:no-underline">
            <?php echo ui_text($t('ホームに戻る', 'Back to Home')); ?>
          </a>
        </p>
      </div>
    </div>
  </div>
</div>
<?php }; ?>
