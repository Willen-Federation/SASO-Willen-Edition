<?php $this->title = 'ログイン'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>
<div class="w-full max-w-md">
  <!-- Logo / title -->
  <div class="text-center mb-8">
    <div class="flex items-center justify-center gap-3 mb-4">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-black dark:text-white">SASO 在庫管理</h1>
    </div>
    <p class="text-sm text-body dark:text-bodydark">
      <?php echo $lang === 'ja' ? 'アカウントにサインインしてください' : 'Sign in to your account'; ?>
    </p>
  </div>

  <!-- Error message -->
  <?php if($v->isError): ?>
  <div class="alert alert-danger mb-6" role="alert" aria-live="polite">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span><?php echo $lang === 'ja' ? 'IDまたはパスワードが正しくありません' : 'Invalid ID or password'; ?></span>
  </div>
  <?php endif; ?>

  <!-- Login card -->
  <div class="card">
    <div class="card-body">
      <form method="post" action="<?php echo './' . htmlspecialchars($v->restoredPath); ?>" novalidate>
        <div class="mb-4">
          <label for="login-id" class="form-label">
            <?php echo $lang === 'ja' ? 'ログインID' : 'Login ID'; ?>
          </label>
          <div class="relative">
            <input
              id="login-id"
              type="text"
              name="id"
              class="form-input pl-11"
              placeholder="<?php echo $lang === 'ja' ? 'IDを入力' : 'Enter your ID'; ?>"
              autocomplete="username"
              required
              aria-required="true"
            >
            <span class="absolute left-4 top-4 text-body dark:text-bodydark">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
          </div>
        </div>

        <div class="mb-6">
          <label for="login-password" class="form-label">
            <?php echo $lang === 'ja' ? 'パスワード' : 'Password'; ?>
          </label>
          <div class="relative" x-data="{ show: false }">
            <input
              id="login-password"
              :type="show ? 'text' : 'password'"
              name="password"
              class="form-input pl-11 pr-11"
              placeholder="••••••••"
              autocomplete="current-password"
              required
              aria-required="true"
            >
            <span class="absolute left-4 top-4 text-body dark:text-bodydark">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <button type="button" @click="show = !show" class="absolute right-4 top-4 text-body dark:text-bodydark" :aria-label="show ? 'パスワードを隠す' : 'パスワードを表示'">
              <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary w-full">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
          <?php echo $lang === 'ja' ? 'ログイン' : 'Sign In'; ?>
        </button>
      </form>

      <!-- External Auth providers (shown if configured) -->
      <?php
      // Show external auth buttons if providers exist in session/config
      $externalProviders = $v->authProviders ?? [];
      if (!empty($externalProviders)): ?>
      <div class="mt-6">
        <div class="relative flex items-center py-2">
          <div class="flex-grow border-t border-stroke dark:border-strokedark"></div>
          <span class="mx-3 text-xs text-body dark:text-bodydark"><?php echo $lang === 'ja' ? 'または' : 'OR'; ?></span>
          <div class="flex-grow border-t border-stroke dark:border-strokedark"></div>
        </div>
        <div class="flex flex-col gap-3 mt-4">
          <?php foreach($externalProviders as $provider): ?>
          <a href="<?php echo htmlspecialchars($provider->loginUrl ?? './auth/external/' . $provider->id); ?>"
             class="btn-secondary w-full flex items-center justify-center gap-2">
            <?php if($provider->type === 'google' || $provider->name === 'Google'): ?>
            <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            <?php elseif($provider->type === 'auth0'): ?>
            <span class="font-bold text-primary">Auth0</span>
            <?php elseif($provider->type === 'cognito'): ?>
            <span class="font-bold text-orange-500">AWS Cognito</span>
            <?php elseif($provider->type === 'firebase'): ?>
            <span class="font-bold text-yellow-500">Firebase</span>
            <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <?php endif; ?>
            <?php echo htmlspecialchars($provider->name ?? $provider->type); ?> <?php echo $lang === 'ja' ? 'でログイン' : 'Login'; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Footer -->
  <p class="text-center text-xs text-body dark:text-bodydark mt-6">
    SASO 在庫管理システム &copy; <?php echo date('Y'); ?>
  </p>
</div>
<?php }; ?>
