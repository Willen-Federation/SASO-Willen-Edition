<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? 'ログイン' : 'Login';
?>
<?php $this->content = function ($v) { ?>
<?php $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'); ?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">

    <!-- Theme toggle row -->
    <div class="d-flex justify-content-end mb-3">
      <button type="button"
              @click="toggle()"
              class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border"
              style="background:var(--saso-card);
                     border-color:var(--saso-card-bdr);
                     color:var(--saso-text);
                     font-size:0.8125rem;
                     cursor:pointer;
                     box-shadow:0 1px 4px rgba(0,0,0,0.1);
                     transition:background 150ms"
              onmouseover="this.style.background='var(--saso-ctrl-hover)'"
              onmouseout="this.style.background='var(--saso-card)'"
              :aria-label="theme==='dark'
                ? '<?php echo ui_attr(__('ui.a11y.switch_to_light', [], null, 'Switch to light mode')); ?>'
                : '<?php echo ui_attr(__('ui.a11y.switch_to_dark',  [], null, 'Switch to dark mode')); ?>'">
        <!-- Sun icon (shown in dark mode → switch to light) -->
        <svg x-show="theme==='dark'" class="flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <!-- Moon icon (shown in light mode → switch to dark) -->
        <svg x-show="theme!='dark'" x-cloak class="flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span x-text="theme==='dark'
          ? '<?php echo ui_attr($lang === 'ja' ? 'ライトモード' : 'Light mode'); ?>'
          : '<?php echo ui_attr($lang === 'ja' ? 'ダークモード' : 'Dark mode'); ?>'"></span>
      </button>
    </div>

    <div class="card card-md"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr);box-shadow:0 4px 24px rgba(0,0,0,0.14)">
      <div class="card-body">
        <h2 class="h2 text-center mb-4" style="color:var(--saso-text)"><?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?></h2>

        <?php if ($v->isError) { ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'ID、パスワードが違います。' : 'The user ID or password is incorrect.'); ?>
          </div>
        <?php } ?>

        <form method="post" action="<?php echo htmlspecialchars($v->restoredPath, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="on">
          <div class="mb-3">
            <label for="login-id" class="form-label" style="color:var(--saso-text)"><?php echo ui_text($lang === 'ja' ? 'ログインID' : 'Login ID'); ?></label>
            <input type="text" id="login-id" name="id" class="form-control"
                   style="background:var(--saso-ctrl-bg);border-color:var(--saso-ctrl-bdr);color:var(--saso-text)"
                   autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label for="login-password" class="form-label" style="color:var(--saso-text)"><?php echo ui_text($lang === 'ja' ? 'パスワード' : 'Password'); ?></label>
            <input type="password" id="login-password" name="password" class="form-control"
                   style="background:var(--saso-ctrl-bg);border-color:var(--saso-ctrl-bdr);color:var(--saso-text)"
                   autocomplete="current-password" maxlength="64" required>
          </div>
          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?>
            </button>
          </div>
        </form>

        <div class="mt-3">
          <button type="button" class="btn btn-outline-primary w-100" id="passkey-login-btn">
            <i class="bi bi-fingerprint me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'パスキーでログイン' : 'Login with Passkey'); ?>
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
          <div class="hr-text mt-4"><?php echo ui_text($lang === 'ja' ? '外部サービスでログイン' : 'Sign in with an external service'); ?></div>
          <div class="d-flex flex-column gap-2 mt-3">
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
              <a href="/auth/start/<?php echo htmlspecialchars($p->id->value, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-key me-2" aria-hidden="true"></i>
                <?php echo ui_text($providerLabel . ($lang === 'ja' ? ' でログイン' : ' login')); ?>
              </a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
    <p class="small mt-3 text-center" style="color:var(--saso-text-sub)">
      <?php echo ui_text($lang === 'ja'
          ? '検索・在庫管理などの機能はログイン後にご利用いただけます。'
          : 'Search and inventory features are available after login.'); ?>
    </p>
  </div>
</div>

<script defer src="./js/passkey-login.js"></script>
<?php }; ?>
