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
            <?php echo ui_text($lang === 'ja' ? 'ユーザー名またはパスワードが正しくありません。' : 'The username or password is likely incorrect.'); ?>
          </div>
        <?php } ?>

        <?php
          // Force the form action to a leading slash. Without this, the browser
          // resolves the relative `restoredPath` against the current URL — which
          // diverges depending on whether the user landed on `/auth/start/` or
          // was bounced here while requesting a deep path. Embedded webviews
          // (desktop / mobile) are particularly sensitive to that base-URL
          // ambiguity; the absolute path keeps them locked to the front
          // controller. An empty restoredPath falls back to `/auth/start/` so
          // the POST cannot escape into a route that 404s.
          $formAction = (string) $v->restoredPath;
          $formAction = $formAction === '' ? '/auth/start/' : '/' . ltrim($formAction, '/');
        ?>
        <form method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="on">
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

<?php }; ?>
