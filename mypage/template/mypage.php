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

<ol class="breadcrumb mb-3" aria-label="<?php echo ui_attr($t('パンくずリスト', 'Breadcrumbs')); ?>">
  <li class="breadcrumb-item"><a href="./"><?php echo ui_text($t('ホーム', 'Home')); ?></a></li>
  <li class="breadcrumb-item active" aria-current="page"><?php echo ui_text($t('マイページ', 'My Page')); ?></li>
</ol>

<?php if (!$v->member): ?>
  <div class="alert alert-danger" role="alert">
    <?php echo ui_text($t('メンバー情報が見つかりません。もう一度ログインしてください。', 'Member data was not found. Please sign in again.')); ?>
  </div>
<?php return; endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">
        <h2 class="card-title"><?php echo ui_text($t('プロフィール', 'Profile')); ?></h2>
        <div class="card-actions">
          <a href="./mypage/editProfile/" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1" aria-hidden="true"></i><?php echo ui_text($t('編集', 'Edit')); ?>
          </a>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-center">
          <div class="col-auto">
            <?php echo \saso\util\AvatarHelper::render($v->member); ?>
          </div>
          <div class="col">
            <dl class="row mb-0">
              <dt class="col-sm-4"><?php echo ui_text($t('ユーザーID', 'User ID')); ?></dt>
              <dd class="col-sm-8 font-monospace"><?php echo ui_text($v->member->id); ?></dd>
              <dt class="col-sm-4"><?php echo ui_text($t('表示名', 'Display Name')); ?></dt>
              <dd class="col-sm-8"><?php echo ui_text($v->member->displayName ?: $v->member->name); ?></dd>
              <dt class="col-sm-4"><?php echo ui_text($t('ロール', 'Role')); ?></dt>
              <dd class="col-sm-8"><span class="badge bg-primary"><?php echo ui_text($v->member->role); ?></span></dd>
              <?php if ($v->member->bio): ?>
                <dt class="col-sm-4"><?php echo ui_text($t('自己紹介', 'Bio')); ?></dt>
                <dd class="col-sm-8"><?php echo nl2br(ui_text($v->member->bio)); ?></dd>
              <?php endif; ?>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h2 class="card-title"><?php echo ui_text($t('認証方法', 'Authentication Methods')); ?></h2>
      </div>
      <div class="card-body vstack gap-3">
        <div class="saso-auth-method">
          <div class="d-flex justify-content-between gap-3">
            <div>
              <h3 class="h4 mb-1"><i class="bi bi-key me-2" aria-hidden="true"></i><?php echo ui_text($t('ローカル認証', 'Local Authentication')); ?></h3>
              <p class="text-muted mb-0"><?php echo ui_text($t('ID とパスワードでログインできます。', 'You can sign in with your ID and password.')); ?></p>
            </div>
            <a href="./start/password/" class="btn btn-outline-primary btn-sm align-self-start"><?php echo ui_text($t('パスワード変更', 'Change Password')); ?></a>
          </div>
        </div>

        <?php foreach ($v->authMethods as $method): ?>
          <div class="saso-auth-method">
            <div class="saso-action-row justify-content-between">
              <div>
                <h3 class="h4 mb-1"><i class="bi bi-shield-lock me-2" aria-hidden="true"></i><?php echo ui_text((string) $method['name']); ?></h3>
                <div class="text-muted small"><?php echo ui_text((string) $method['external_subject']); ?></div>
                <div class="text-muted small"><?php echo ui_text($t('最終ログイン', 'Last login')); ?>: <?php echo ui_text((string) ($method['last_login_at'] ?? '-')); ?></div>
              </div>
              <form method="post" action="./mypage/unlinkProvider/" onsubmit="return confirm('<?php echo ui_attr($t('この認証方法を削除しますか？', 'Remove this authentication method?')); ?>');">
                <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                <input type="hidden" name="providerId" value="<?php echo (int) $method['id']; ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                  <i class="bi bi-trash me-1" aria-hidden="true"></i><?php echo ui_text($t('削除', 'Remove')); ?>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ($v->availableProviders !== []): ?>
          <div class="saso-auth-method">
            <h3 class="h4"><?php echo ui_text($t('外部認証を追加', 'Add External Authentication')); ?></h3>
            <div class="saso-action-row">
              <?php foreach ($v->availableProviders as $provider): ?>
                <a class="btn btn-outline-secondary btn-sm" href="./mypage/linkProvider/id/<?php echo (int) $provider['id']; ?>/">
                  <i class="bi bi-plus-circle me-1" aria-hidden="true"></i><?php echo ui_text((string) $provider['name']); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title"><?php echo ui_text($t('パスキー', 'Passkeys')); ?></h2>
      </div>
      <div class="card-body vstack gap-3">
        <p class="text-muted mb-0"><?php echo ui_text($t('Windows Hello、Touch ID、Face ID などでログインできます。', 'Sign in with Windows Hello, Touch ID, Face ID, or a security key.')); ?></p>
        <button type="button" class="btn btn-primary w-100" id="register-passkey-btn">
          <i class="bi bi-fingerprint me-2" aria-hidden="true"></i><?php echo ui_text($t('新しいパスキーを登録', 'Register New Passkey')); ?>
        </button>
        <?php if ($v->passkeys === []): ?>
          <div class="alert alert-info mb-0" role="status"><?php echo ui_text($t('登録済みパスキーはありません。', 'No passkeys are registered.')); ?></div>
        <?php else: ?>
          <?php foreach ($v->passkeys as $passkey): ?>
            <div class="border rounded p-2">
              <div class="fw-semibold"><?php echo ui_text((string) $passkey['name']); ?></div>
              <div class="text-muted small"><?php echo ui_text($t('登録', 'Created')); ?>: <?php echo ui_text((string) $passkey['created_at']); ?></div>
              <form method="post" action="./mypage/passkeyDelete/" class="mt-2">
                <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $passkey['id']; ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"><?php echo ui_text($t('削除', 'Delete')); ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script defer src="./js/passkey-register.js"></script>
<?php }; ?>
