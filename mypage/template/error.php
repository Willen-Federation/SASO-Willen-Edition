<?php
/** @var \saso\mypage\MyPageErrorView $v */
?>
<?php $v->title = __('Error'); ?>
<?php $v->content = function ($v) { ?>
<div class="container mt-5">
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><?php echo htmlspecialchars(__('Error')); ?></h4>
        <p><?php echo htmlspecialchars($v->message ?? __('An error occurred')); ?></p>
        <hr>
        <p class="mb-0">
            <a href="/start/start/" class="alert-link">
                <?php echo htmlspecialchars(__('Back to Home')); ?>
            </a>
        </p>
    </div>
</div>
<?php }; ?>
