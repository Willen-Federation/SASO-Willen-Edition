<?php
/** @var \saso\mypage\MyPageView $v */
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
?>
<?php $this->title = $lang === 'ja' ? 'マイページ' : 'My Page'; ?>
<?php $this->content = function ($v) { ?>
<?php
    $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
    $t = static fn (string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
    ?>

<?php if (!$v->member): ?>
  <div class="ta-alert ta-alert-danger" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <?php echo ui_text($t('メンバー情報が見つかりません。もう一度ログインしてください。', 'Member data was not found. Please sign in again.')); ?>
  </div>
<?php return; endif; ?>

<?php
    $avatarUrl = \saso\util\AvatarHelper::externalUrl($v->member->avatarUrl ?? null);
    $displayName = \saso\util\AvatarHelper::displayName($v->member);
    $avatarLabel = $displayName !== '' ? $displayName : $t('ユーザー', 'User');
    $avatarTone = \saso\util\AvatarHelper::fallbackTone($avatarLabel);
    ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  <!-- Left column (profile + auth methods) -->
  <div class="lg:col-span-2 flex flex-col gap-4">

    <!-- Profile card -->
    <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold text-base" style="color:var(--saso-text)"><?php echo ui_text($t('プロフィール', 'Profile')); ?></h2>
        <a href="./mypage/editProfile/" class="btn btn-primary btn-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          <?php echo ui_text($t('編集', 'Edit')); ?>
        </a>
      </div>
      <div class="px-6 py-5">
        <div class="flex items-start gap-4">
          <div class="shrink-0">
            <?php if ($avatarUrl !== null): ?>
              <img src="<?php echo ui_attr($avatarUrl); ?>"
                   alt="<?php echo ui_attr($avatarLabel); ?>"
                   class="rounded-full border saso-avatar-image w-20 h-20 object-cover"
                   style="border-color:var(--saso-card-bdr)"
                   onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');">
              <span class="avatar avatar-xl <?php echo ui_attr($avatarTone); ?> text-white hidden" aria-hidden="true">
                <i class="<?php echo ui_attr(\saso\util\AvatarHelper::fallbackIconClass()); ?>"></i>
              </span>
            <?php else: ?>
              <span class="avatar avatar-xl <?php echo ui_attr($avatarTone); ?> text-white" aria-hidden="true">
                <i class="<?php echo ui_attr(\saso\util\AvatarHelper::fallbackIconClass()); ?>"></i>
              </span>
            <?php endif; ?>
          </div>
          <dl class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <dt class="font-medium" style="color:var(--saso-text)"><?php echo ui_text($t('ユーザーID', 'User ID')); ?></dt>
            <dd class="font-mono" style="color:var(--saso-text-sub)"><?php echo ui_text($v->member->id); ?></dd>
            <dt class="font-medium" style="color:var(--saso-text)"><?php echo ui_text($t('表示名', 'Display Name')); ?></dt>
            <dd style="color:var(--saso-text-sub)"><?php echo ui_text($displayName); ?></dd>
            <dt class="font-medium" style="color:var(--saso-text)"><?php echo ui_text($t('ロール', 'Role')); ?></dt>
            <dd><span class="ta-badge ta-badge-primary"><?php echo ui_text($v->member->role); ?></span></dd>
            <?php if ($v->member->bio): ?>
              <dt class="font-medium" style="color:var(--saso-text)"><?php echo ui_text($t('自己紹介', 'Bio')); ?></dt>
              <dd style="color:var(--saso-text-sub)"><?php echo nl2br(ui_text($v->member->bio)); ?></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    </div>

    <!-- Auth methods card -->
    <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="flex items-center px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold text-base" style="color:var(--saso-text)"><?php echo ui_text($t('認証方法', 'Authentication Methods')); ?></h2>
      </div>
      <div class="px-6 py-5 flex flex-col gap-4">

        <!-- Local auth -->
        <div class="saso-auth-method">
          <div class="flex justify-between items-start gap-3">
            <div>
              <h3 class="font-semibold text-sm mb-1 flex items-center gap-1.5" style="color:var(--saso-text)">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <?php echo ui_text($t('ローカル認証', 'Local Authentication')); ?>
              </h3>
              <p class="text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text($t('ID とパスワードでログインできます。', 'You can sign in with your ID and password.')); ?></p>
            </div>
            <a href="./start/password/" class="btn btn-secondary btn-sm shrink-0"><?php echo ui_text($t('パスワード変更', 'Change Password')); ?></a>
          </div>
        </div>

        <?php foreach ($v->authMethods as $method): ?>
          <div class="saso-auth-method">
            <div class="saso-action-row flex justify-between items-start gap-3">
              <div>
                <h3 class="font-semibold text-sm mb-1 flex items-center gap-1.5" style="color:var(--saso-text)">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <?php echo ui_text((string) $method['name']); ?>
                </h3>
                <p class="text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text((string) $method['external_subject']); ?></p>
                <p class="text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text($t('最終ログイン', 'Last login')); ?>: <?php echo ui_text((string) ($method['last_login_at'] ?? '-')); ?></p>
              </div>
              <form method="post" action="./mypage/unlinkProvider/" onsubmit="return confirm('<?php echo ui_attr($t('この認証方法を削除しますか？', 'Remove this authentication method?')); ?>');" class="shrink-0">
                <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                <input type="hidden" name="providerId" value="<?php echo (int) $method['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  <?php echo ui_text($t('削除', 'Remove')); ?>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ($v->availableProviders !== []): ?>
          <div class="saso-auth-method">
            <h3 class="font-semibold text-sm mb-2" style="color:var(--saso-text)"><?php echo ui_text($t('外部認証を追加', 'Add External Authentication')); ?></h3>
            <div class="saso-action-row flex flex-wrap gap-2">
              <?php foreach ($v->availableProviders as $provider): ?>
                <form method="post" action="./mypage/linkProvider/id/<?php echo (int) $provider['id']; ?>/" style="display:inline">
                  <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <?php echo ui_text((string) $provider['name']); ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Right column (passkeys) -->
  <div class="lg:col-span-1">
    <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="flex items-center px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold text-base" style="color:var(--saso-text)"><?php echo ui_text($t('パスキー', 'Passkeys')); ?></h2>
      </div>
      <div class="px-6 py-5 flex flex-col gap-4">
        <div class="ta-alert ta-alert-warning" role="status"><?php echo ui_text($t('パスキー機能は現在無効です（WebAuthn 署名検証の整備中）。', 'Passkey support is currently disabled while WebAuthn signature verification is being implemented.')); ?></div>
      </div>
    </div>
  </div>

</div>

<?php }; ?>
