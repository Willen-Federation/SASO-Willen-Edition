<?php
/** @var \saso\mypage\MyPageView $v */
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
?>
<?php $this->title = $lang === 'ja' ? 'マイページ' : 'My Page'; ?>
<?php $this->content = function ($v) { ?>
<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$t = static fn(string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
?>

<?php if (!$v->member): ?>
  <div class="ta-alert ta-alert-danger" role="alert">
    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-.75-5.25a.75.75 0 0 0 1.5 0V10a.75.75 0 0 0-1.5 0v2.75zm0-5.5a.75.75 0 0 0 1.5 0v-.25a.75.75 0 0 0-1.5 0v.25z" clip-rule="evenodd"/>
    </svg>
    <span><?php echo ui_text($t('メンバー情報が見つかりません。もう一度ログインしてください。', 'Member data was not found. Please sign in again.')); ?></span>
  </div>
<?php return; endif; ?>

<?php
$avatarUrl   = \saso\util\AvatarHelper::externalUrl($v->member->avatarUrl ?? null);
$displayName = \saso\util\AvatarHelper::displayName($v->member);
$avatarLabel = $displayName !== '' ? $displayName : $t('ユーザー', 'User');
$avatarTone  = \saso\util\AvatarHelper::fallbackTone($avatarLabel);
?>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

  <!-- ── Left column (profile + auth methods) ── -->
  <div class="lg:col-span-2 flex flex-col gap-5">

    <!-- Profile card -->
    <section class="rounded-2xl border"
             style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <header class="flex items-center justify-between border-b px-5 py-4"
              style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold" style="color:var(--saso-text)">
          <?php echo ui_text($t('プロフィール', 'Profile')); ?>
        </h2>
        <a href="./mypage/editProfile/" class="btn btn-secondary btn-sm">
          <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span><?php echo ui_text($t('編集', 'Edit')); ?></span>
        </a>
      </header>

      <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-start">
        <!-- Avatar -->
        <div class="shrink-0">
          <?php if ($avatarUrl !== null): ?>
            <img src="<?php echo ui_attr($avatarUrl); ?>"
                 alt="<?php echo ui_attr($avatarLabel); ?>"
                 class="h-20 w-20 rounded-full border object-cover"
                 style="border-color:var(--saso-card-bdr)"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <span class="hidden h-20 w-20 items-center justify-center rounded-full bg-brand-100 text-2xl font-bold text-brand-600 dark:bg-brand-900/30 dark:text-brand-300"
                  aria-hidden="true">
              <?php echo ui_text(mb_substr($avatarLabel, 0, 1)); ?>
            </span>
          <?php else: ?>
            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-100 text-2xl font-bold text-brand-600 dark:bg-brand-900/30 dark:text-brand-300"
                  aria-hidden="true">
              <?php echo ui_text(mb_substr($avatarLabel, 0, 1)); ?>
            </span>
          <?php endif; ?>
        </div>

        <!-- Detail list -->
        <dl class="flex-1 divide-y" style="color:var(--saso-text);border-color:var(--saso-card-bdr)">
          <div class="grid grid-cols-3 gap-2 py-2.5">
            <dt class="text-sm font-medium" style="color:var(--saso-text-sub)"><?php echo ui_text($t('ユーザーID', 'User ID')); ?></dt>
            <dd class="col-span-2 font-mono text-sm"><?php echo ui_text($v->member->id); ?></dd>
          </div>
          <div class="grid grid-cols-3 gap-2 py-2.5">
            <dt class="text-sm font-medium" style="color:var(--saso-text-sub)"><?php echo ui_text($t('表示名', 'Display Name')); ?></dt>
            <dd class="col-span-2 text-sm"><?php echo ui_text($displayName ?: '—'); ?></dd>
          </div>
          <div class="grid grid-cols-3 gap-2 py-2.5">
            <dt class="text-sm font-medium" style="color:var(--saso-text-sub)"><?php echo ui_text($t('ロール', 'Role')); ?></dt>
            <dd class="col-span-2">
              <span class="ta-badge ta-badge-primary"><?php echo ui_text($v->member->role); ?></span>
            </dd>
          </div>
          <?php if ($v->member->bio): ?>
          <div class="grid grid-cols-3 gap-2 py-2.5">
            <dt class="text-sm font-medium" style="color:var(--saso-text-sub)"><?php echo ui_text($t('自己紹介', 'Bio')); ?></dt>
            <dd class="col-span-2 text-sm"><?php echo nl2br(ui_text($v->member->bio)); ?></dd>
          </div>
          <?php endif; ?>
        </dl>
      </div>
    </section>

    <!-- Auth methods card -->
    <section class="rounded-2xl border"
             style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <header class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold" style="color:var(--saso-text)">
          <?php echo ui_text($t('認証方法', 'Authentication Methods')); ?>
        </h2>
      </header>

      <div class="divide-y px-5" style="border-color:var(--saso-card-bdr)">

        <!-- Local auth -->
        <div class="flex items-start justify-between gap-4 py-4">
          <div>
            <h3 class="text-sm font-semibold" style="color:var(--saso-text)">
              <svg class="mr-1.5 inline h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <?php echo ui_text($t('ローカル認証', 'Local Authentication')); ?>
            </h3>
            <p class="mt-0.5 text-sm" style="color:var(--saso-text-sub)">
              <?php echo ui_text($t('ID とパスワードでログインできます。', 'Sign in with your ID and password.')); ?>
            </p>
          </div>
          <a href="./start/password/" class="btn btn-secondary btn-sm shrink-0">
            <?php echo ui_text($t('パスワード変更', 'Change Password')); ?>
          </a>
        </div>

        <!-- External providers -->
        <?php foreach ($v->authMethods as $method): ?>
          <div class="flex items-start justify-between gap-4 py-4">
            <div>
              <h3 class="text-sm font-semibold" style="color:var(--saso-text)">
                <svg class="mr-1.5 inline h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php echo ui_text((string) $method['name']); ?>
              </h3>
              <p class="mt-0.5 text-xs font-mono" style="color:var(--saso-text-sub)"><?php echo ui_text((string) $method['external_subject']); ?></p>
              <p class="text-xs" style="color:var(--saso-text-sub)">
                <?php echo ui_text($t('最終ログイン', 'Last login')); ?>: <?php echo ui_text((string) ($method['last_login_at'] ?? '—')); ?>
              </p>
            </div>
            <form method="post" action="./mypage/unlinkProvider/"
                  onsubmit="return confirm('<?php echo ui_attr($t('この認証方法を削除しますか？', 'Remove this authentication method?')); ?>');">
              <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
              <input type="hidden" name="providerId" value="<?php echo (int) $method['id']; ?>">
              <button type="submit" class="btn btn-danger btn-sm">
                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span><?php echo ui_text($t('削除', 'Remove')); ?></span>
              </button>
            </form>
          </div>
        <?php endforeach; ?>

        <!-- Add external auth -->
        <?php if ($v->availableProviders !== []): ?>
          <div class="py-4">
            <h3 class="mb-3 text-sm font-semibold" style="color:var(--saso-text)">
              <?php echo ui_text($t('外部認証を追加', 'Add External Authentication')); ?>
            </h3>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($v->availableProviders as $provider): ?>
                <form method="post" action="./mypage/linkProvider/id/<?php echo (int) $provider['id']; ?>/">
                  <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                      <path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span><?php echo ui_text((string) $provider['name']); ?></span>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- ── Right column (passkeys) ── -->
  <div>
    <section class="rounded-2xl border"
             style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <header class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold" style="color:var(--saso-text)">
          <?php echo ui_text($t('パスキー', 'Passkeys')); ?>
        </h2>
      </header>

      <div class="flex flex-col gap-4 px-5 py-5">
        <p class="text-sm" style="color:var(--saso-text-sub)">
          <?php echo ui_text($t('Windows Hello、Touch ID、Face ID などでログインできます。', 'Sign in with Windows Hello, Touch ID, Face ID, or a security key.')); ?>
        </p>

        <button type="button"
                id="register-passkey-btn"
                class="btn btn-primary w-full"
                data-csrftoken="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
          <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 1C8.676 1 6 3.676 6 7c0 2.386 1.342 4.453 3.313 5.5L9 21h2l.5-2H13l.5 2H16l-.313-8.5C17.658 11.453 19 9.386 19 7c0-3.324-2.676-6-7-6z"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          </svg>
          <span><?php echo ui_text($t('新しいパスキーを登録', 'Register New Passkey')); ?></span>
        </button>

        <?php if ($v->passkeys === []): ?>
          <div class="ta-alert ta-alert-info" role="status">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo ui_text($t('登録済みパスキーはありません。', 'No passkeys are registered.')); ?></span>
          </div>
        <?php else: ?>
          <ul class="flex flex-col gap-3" aria-label="<?php echo ui_attr($t('登録済みパスキー', 'Registered passkeys')); ?>">
            <?php foreach ($v->passkeys as $passkey): ?>
              <li class="rounded-lg border px-4 py-3" style="border-color:var(--saso-card-bdr)">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <p class="text-sm font-semibold" style="color:var(--saso-text)"><?php echo ui_text((string) $passkey['name']); ?></p>
                    <p class="text-xs" style="color:var(--saso-text-sub)">
                      <?php echo ui_text($t('登録日', 'Created')); ?>: <?php echo ui_text((string) $passkey['created_at']); ?>
                    </p>
                  </div>
                  <form method="post" action="./mypage/passkeyDelete/">
                    <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $passkey['id']; ?>">
                    <button type="submit"
                            class="btn btn-danger btn-sm"
                            aria-label="<?php echo ui_attr($t('パスキーを削除', 'Delete passkey') . ': ' . (string) $passkey['name']); ?>">
                      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>
  </div>

</div>

<script defer src="./js/passkey-register.js"></script>
<?php }; ?>
