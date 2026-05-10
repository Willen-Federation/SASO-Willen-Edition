<?php
/** @var \saso\mypage\MyPageErrorView $v */
?>
<?php $this->title = __('Error'); ?>
<?php $this->content = function ($v) { ?>
<div class="ta-alert ta-alert-danger" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div>
        <p class="font-semibold"><?php echo htmlspecialchars(__('Error')); ?></p>
        <p class="mt-1"><?php echo htmlspecialchars($v->message ?? __('An error occurred')); ?></p>
        <p class="mt-2">
            <a href="/start/start/" class="underline">
                <?php echo htmlspecialchars(__('Back to Home')); ?>
            </a>
        </p>
    </div>
</div>
<?php }; ?>
