<?php
/** @var \saso\mypage\EditProfileView $v */
?>
<?php $this->title = __('Edit Profile'); ?>
<?php $this->content = function ($v) { ?>

<div class="flex justify-center">
  <div class="w-full max-w-xl">
    <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="flex items-center px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold text-base" style="color:var(--saso-text)"><?php echo htmlspecialchars(__('Edit Profile')); ?></h2>
      </div>
      <div class="px-6 py-5">
        <?php if ($v->member): ?>
        <form method="POST" action="/mypage/editProfile/">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current()); ?>">
          <div class="mb-4">
            <label for="display_name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
              <?php echo htmlspecialchars(__('Display Name')); ?>
            </label>
            <input
              type="text"
              class="form-input w-full"
              id="display_name"
              name="display_name"
              maxlength="100"
              value="<?php echo htmlspecialchars($v->member->displayName ?: ''); ?>"
              placeholder="<?php echo htmlspecialchars($v->member->name); ?>">
            <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">
              <?php echo htmlspecialchars(__('Your name displayed in the application')); ?>
            </p>
          </div>

          <div class="mb-4">
            <label for="bio" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
              <?php echo htmlspecialchars(__('Bio')); ?>
            </label>
            <textarea
              class="form-input w-full"
              id="bio"
              name="bio"
              rows="4"
              maxlength="500"><?php echo htmlspecialchars($v->member->bio ?: ''); ?></textarea>
            <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">
              <?php echo htmlspecialchars(__('About yourself (max 500 characters)')); ?>
            </p>
          </div>

          <div class="mb-5">
            <label for="avatar_url" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
              <?php echo htmlspecialchars(__('Avatar URL')); ?>
            </label>
            <input
              type="url"
              class="form-input w-full"
              id="avatar_url"
              name="avatar_url"
              maxlength="500"
              value="<?php echo htmlspecialchars($v->member->avatarUrl ?: ''); ?>"
              placeholder="https://example.com/avatar.jpg">
            <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">
              <?php echo htmlspecialchars(__('URL to your avatar image (JPG, PNG, WebP)')); ?>
            </p>
          </div>

          <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">
              <?php echo htmlspecialchars(__('Save')); ?>
            </button>
            <a href="./mypage/start/" class="btn btn-secondary">
              <?php echo htmlspecialchars(__('Cancel')); ?>
            </a>
          </div>
        </form>

        <?php else: ?>
        <div class="ta-alert ta-alert-danger" role="alert">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          <?php echo htmlspecialchars(__('Member data not found')); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php }; ?>
