<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? 'ログイン' : 'Login';
?>
<?php $this->content = function ($v) { ?>
<?php $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'); ?>

<div class="w-full max-w-md">

    <!-- Login card -->
    <div class="rounded-2xl border shadow-lg"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr);box-shadow:0 4px 24px rgba(0,0,0,0.14)">
      <div class="px-10 py-10 sm:px-12 sm:py-12">
        <h2 class="text-2xl font-semibold text-center mb-8" style="color:var(--saso-text)"><?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?></h2>

        <?php if ($v->isError) { ?>
          <div class="ta-alert ta-alert-danger mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <?php echo ui_text($lang === 'ja' ? 'ID、パスワードが違います。' : 'The user ID or password is incorrect.'); ?>
          </div>
        <?php } ?>

        <form method="post" action="<?php echo htmlspecialchars($v->restoredPath, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="on">
          <div class="mb-4">
            <label for="login-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)"><?php echo ui_text($lang === 'ja' ? 'ログインID' : 'Login ID'); ?></label>
            <input type="text" id="login-id" name="id" class="form-input w-full"
                   autocomplete="username" required aria-required="true">
          </div>
          <div class="mb-5">
            <label for="login-password" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)"><?php echo ui_text($lang === 'ja' ? 'パスワード' : 'Password'); ?></label>
            <input type="password" id="login-password" name="password" class="form-input w-full"
                   autocomplete="current-password" maxlength="64" required aria-required="true">
          </div>
          <button type="submit" class="btn btn-primary w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            <?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?>
          </button>
        </form>

        <div class="mt-3">
          <button type="button" class="btn btn-secondary w-full" id="passkey-login-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
            <?php echo ui_text($lang === 'ja' ? 'パスキーでログイン' : 'Login with Passkey'); ?>
          </button>
        </div>

        <?php
          $externalProviders = array_values(array_filter(
              $v->providers,
              fn($p) => ($p->type->value ?? '') !== 'local'
          ));
          $providerNameCounts = [];
          foreach ($externalProviders as $p) {
              $providerNameCounts[$p->name] = ($providerNameCounts[$p->name] ?? 0) + 1;
          }
        ?>
        <?php if ($externalProviders !== []) { ?>
          <div class="relative my-5">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
              <div class="w-full border-t" style="border-color:var(--saso-card-bdr)"></div>
            </div>
            <div class="relative flex justify-center">
              <span class="px-3 text-xs" style="background:var(--saso-card);color:var(--saso-text-sub)"><?php echo ui_text($lang === 'ja' ? '外部サービスでログイン' : 'Sign in with an external service'); ?></span>
            </div>
          </div>
          <div class="flex flex-col gap-2">
            <?php foreach ($externalProviders as $p) { ?>
              <?php
                $providerLabel = $p->name;
                if (($providerNameCounts[$p->name] ?? 0) > 1) {
                    $providerLabel = sprintf(
                        $lang === 'ja' ? '%s（プロバイダー #%d）' : '%s (provider #%d)',
                        $p->name,
                        (int) $p->id->value
                    );
                }
              ?>
              <a href="/auth/start/<?php echo htmlspecialchars($p->id->value, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <?php echo ui_text($providerLabel . ($lang === 'ja' ? ' でログイン' : ' login')); ?>
              </a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>

    <p class="text-xs text-center mt-3" style="color:var(--saso-text-sub)">
      <?php echo ui_text($lang === 'ja'
          ? '検索・在庫管理などの機能はログイン後にご利用いただけます。'
          : 'Search and inventory features are available after login.'); ?>
    </p>
</div>

<script defer src="./js/passkey-login.js"></script>
<?php }; ?>
