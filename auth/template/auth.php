<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? 'ログイン' : 'Login';
?>
<?php $this->content = function ($v) { ?>
<?php $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'); ?>

<div class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-8">
  <div class="w-full max-w-md">

    <!-- Theme toggle -->
    <?php
      $labelLight  = ui_attr($lang === 'ja' ? 'ライトモード' : 'Light mode');
      $labelDark   = ui_attr($lang === 'ja' ? 'ダークモード' : 'Dark mode');
      $ariaToLight = ui_attr(__('ui.a11y.switch_to_light', [], null, 'Switch to light mode'));
      $ariaToDark  = ui_attr(__('ui.a11y.switch_to_dark',  [], null, 'Switch to dark mode'));
    ?>
    <div class="mb-4 flex justify-end">
      <button type="button"
              @click="toggle()"
              class="saso-header-btn flex items-center gap-2 px-3 py-2 text-sm"
              :aria-label="theme === 'dark' ? '<?php echo $ariaToLight; ?>' : '<?php echo $ariaToDark; ?>'">
        <svg x-show="theme === 'dark'" x-cloak class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <svg x-show="theme !== 'dark'" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span x-text="theme === 'dark' ? '<?php echo $labelLight; ?>' : '<?php echo $labelDark; ?>'"></span>
      </button>
    </div>

    <!-- Login card -->
    <div class="rounded-2xl border"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr);box-shadow:0 4px 24px rgba(0,0,0,0.14)">
      <div class="px-6 py-7 sm:px-8 sm:py-8">
        <h2 class="mb-6 text-center text-xl font-semibold" style="color:var(--saso-text)">
          <?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?>
        </h2>

        <!-- Error alert -->
        <?php if ($v->isError) { ?>
          <div role="alert"
               class="ta-alert ta-alert-danger mb-5">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-.75-5.25a.75.75 0 0 0 1.5 0V10a.75.75 0 0 0-1.5 0v2.75zm0-5.5a.75.75 0 0 0 1.5 0v-.25a.75.75 0 0 0-1.5 0v.25z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo ui_text($lang === 'ja' ? 'ID、パスワードが違います。' : 'The user ID or password is incorrect.'); ?></span>
          </div>
        <?php } ?>

        <!-- Credential form -->
        <form method="post" action="<?php echo htmlspecialchars($v->restoredPath, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="on" novalidate>
          <div class="mb-4">
            <label for="login-id" class="form-label">
              <?php echo ui_text($lang === 'ja' ? 'ログインID' : 'Login ID'); ?>
            </label>
            <input type="text" id="login-id" name="id"
                   class="form-input"
                   autocomplete="username"
                   required
                   aria-required="true"
                   <?php if ($v->isError): ?>aria-invalid="true" aria-describedby="login-error"<?php endif; ?>>
          </div>

          <div class="mb-6">
            <label for="login-password" class="form-label">
              <?php echo ui_text($lang === 'ja' ? 'パスワード' : 'Password'); ?>
            </label>
            <input type="password" id="login-password" name="password"
                   class="form-input"
                   autocomplete="current-password"
                   maxlength="64"
                   required
                   aria-required="true"
                   <?php if ($v->isError): ?>aria-invalid="true" aria-describedby="login-error"<?php endif; ?>>
          </div>

          <?php if ($v->isError): ?>
            <p id="login-error" class="sr-only">
              <?php echo ui_text($lang === 'ja' ? 'ID、パスワードが違います。' : 'Incorrect login ID or password.'); ?>
            </p>
          <?php endif; ?>

          <button type="submit"
                  class="btn btn-primary w-full">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?></span>
          </button>
        </form>

        <!-- Passkey login -->
        <div class="mt-3">
          <button type="button"
                  id="passkey-login-btn"
                  class="btn btn-secondary w-full">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 1C8.676 1 6 3.676 6 7c0 2.386 1.342 4.453 3.313 5.5L9 21h2l.5-2H13l.5 2H16l-.313-8.5C17.658 11.453 19 9.386 19 7c0-3.324-2.676-6-7-6z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <span><?php echo ui_text($lang === 'ja' ? 'パスキーでログイン' : 'Login with Passkey'); ?></span>
          </button>
        </div>

        <!-- External OAuth providers -->
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
          <!-- Divider -->
          <div class="relative my-5 flex items-center" role="separator" aria-label="<?php echo ui_attr($lang === 'ja' ? '外部サービス' : 'Or sign in with'); ?>">
            <div class="flex-grow border-t border-gray-200 dark:border-gray-700" aria-hidden="true"></div>
            <span class="mx-3 shrink-0 text-xs text-gray-500 dark:text-gray-400">
              <?php echo ui_text($lang === 'ja' ? '外部サービスでログイン' : 'Sign in with'); ?>
            </span>
            <div class="flex-grow border-t border-gray-200 dark:border-gray-700" aria-hidden="true"></div>
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
                $loginLabel = $lang === 'ja'
                    ? $providerLabel . ' でログイン'
                    : 'Sign in with ' . $providerLabel;
              ?>
              <a href="/auth/start/<?php echo htmlspecialchars($p->id->value, ENT_QUOTES, 'UTF-8'); ?>"
                 class="btn btn-secondary w-full">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span><?php echo ui_text($loginLabel); ?></span>
              </a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- Hint text -->
    <p class="mt-4 text-center text-xs" style="color:var(--saso-text-sub)">
      <?php echo ui_text($lang === 'ja'
          ? '検索・在庫管理などの機能はログイン後にご利用いただけます。'
          : 'Search and inventory features are available after login.'); ?>
    </p>
  </div>
</div>

<script defer src="./js/passkey-login.js"></script>
<?php }; ?>
