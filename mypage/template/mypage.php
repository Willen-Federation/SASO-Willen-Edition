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

    <!-- API Access card -->
    <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
         x-data="{ copied: false, async copy(text) { try { await navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 1500); } catch (e) { console.warn('clipboard failed', e); } } }">
      <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold text-base" style="color:var(--saso-text)"><?php echo ui_text($t('API アクセス', 'API Access')); ?></h2>
        <span class="text-xs" style="color:var(--saso-text-sub)" x-show="copied" x-transition>
          <?php echo ui_text($t('コピーしました', 'Copied')); ?>
        </span>
      </div>
      <div class="px-6 py-5 flex flex-col gap-4 text-sm">
        <p style="color:var(--saso-text-sub)">
          <?php echo ui_text($t('外部アプリケーションや連携端末から SASO の REST API を利用するためのエンドポイント情報です。', 'Endpoints and credentials needed to call the SASO REST API from external apps or paired devices.')); ?>
        </p>

        <div>
          <div class="font-medium mb-1" style="color:var(--saso-text)"><?php echo ui_text($t('ベース URL', 'Base URL')); ?></div>
          <div class="flex items-center gap-2">
            <code class="font-mono px-2 py-1 rounded border flex-1 break-all" style="background:var(--saso-card-bdr);border-color:var(--saso-card-bdr);color:var(--saso-text)"><?php echo ui_text($v->apiBaseUrl); ?></code>
            <button type="button" class="btn btn-secondary btn-sm" @click="copy('<?php echo ui_attr($v->apiBaseUrl); ?>')">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              <span class="ml-1"><?php echo ui_text($t('コピー', 'Copy')); ?></span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <a href="<?php echo ui_attr($v->apiDocsUrl); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m0-7L10 14m-3-9H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-2"/></svg>
            <?php echo ui_text($t('Swagger UI を開く', 'Open Swagger UI')); ?>
          </a>
          <a href="<?php echo ui_attr($v->openApiUrl); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            <?php echo ui_text($t('OpenAPI YAML', 'OpenAPI YAML')); ?>
          </a>
        </div>

        <div>
          <div class="font-medium mb-1" style="color:var(--saso-text)"><?php echo ui_text($t('認証方式', 'Authentication')); ?></div>
          <p style="color:var(--saso-text-sub)">
            <?php echo ui_text($t('下の「連携端末」セクションでペアリングし、発行されたアクセストークンを次のヘッダーで送信してください。', 'Pair a device in the section below and send the issued access token in the following header:')); ?>
          </p>
          <code class="block mt-2 font-mono px-2 py-1 rounded border break-all" style="background:var(--saso-card-bdr);border-color:var(--saso-card-bdr);color:var(--saso-text)">Authorization: Bearer &lt;access_token&gt;</code>
        </div>

        <?php if ($v->defaultScopes !== []): ?>
        <div>
          <div class="font-medium mb-1" style="color:var(--saso-text)"><?php echo ui_text($t('既定で付与されるスコープ', 'Default scopes granted')); ?></div>
          <div class="flex flex-wrap gap-1.5">
            <?php foreach ($v->defaultScopes as $scope): ?>
              <span class="ta-badge ta-badge-primary font-mono text-xs"><?php echo ui_text((string) $scope); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Linked Devices card -->
    <?php
        $deviceStatus = (string) ($_GET['device'] ?? '');
        $statusBanner = match ($deviceStatus) {
            'revoked'  => ['ta-alert-success', $t('連携端末を失効しました。', 'The device has been revoked.')],
            'notfound' => ['ta-alert-warning', $t('対象の端末が見つかりませんでした。', 'The requested device was not found.')],
            'blocked'  => ['ta-alert-danger',  $t('リクエストが無効です。再度ログインしてからお試しください。', 'The request was rejected. Please sign in again and retry.')],
            'error'    => ['ta-alert-danger',  $t('処理中にエラーが発生しました。', 'An error occurred while processing the request.')],
            default    => null,
        };
        ?>
    <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
         x-data='devicePairing(<?php echo ui_attr(json_encode([
             "csrfToken" => \saso\util\CSRFtoken::current(),
             "endpoint" => "./mypage/devicePair/",
             "labels" => [
                 "deviceName" => $t('端末名', 'Device name'),
                 "deviceNamePlaceholder" => $t('例: 作業用 iPhone', 'e.g. Work iPhone'),
                 "generate" => $t('コードを生成', 'Generate code'),
                 "generating" => $t('生成中…', 'Generating…'),
                 "expiresIn" => $t('有効期限', 'Expires in'),
                 "qrInstructions" => $t('SASO モバイルアプリでこの QR を読み取るか、トークンを入力してください。10 分以内に完了する必要があります。', 'Scan this QR with the SASO mobile app, or enter the token manually. Must complete within 10 minutes.'),
                 "payload" => $t('ペアリングペイロード', 'Pairing payload'),
                 "token" => $t('生トークン', 'Raw token'),
                 "copy" => $t('コピー', 'Copy'),
                 "copied" => $t('コピーしました', 'Copied'),
                 "close" => $t('閉じる', 'Close'),
                 "regenerate" => $t('新しいコードを生成', 'Generate a new code'),
                 "expired" => $t('コードの有効期限が切れました。', 'This code has expired.'),
                 "errorGeneric" => $t('コード生成に失敗しました。', 'Failed to generate code.'),
                 "errorCsrf" => $t('セッションが無効です。ページを再読み込みしてください。', 'Your session is invalid. Please reload the page.'),
                 "errorAuth" => $t('認証されていません。再度ログインしてください。', 'You are not signed in. Please sign in again.'),
                 "newDeviceTitle" => $t('新しい端末を連携', 'Link a new device'),
             ],
         ])); ?>)'
         x-init="init()">
      <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
        <h2 class="font-semibold text-base" style="color:var(--saso-text)"><?php echo ui_text($t('連携端末', 'Linked Devices')); ?></h2>
        <button type="button" class="btn btn-primary btn-sm" @click="openModal()">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <?php echo ui_text($t('新しい端末を連携', 'Link new device')); ?>
        </button>
      </div>
      <?php if ($statusBanner !== null): ?>
        <div class="px-6 pt-4">
          <div class="ta-alert <?php echo ui_attr($statusBanner[0]); ?>" role="status">
            <span><?php echo ui_text($statusBanner[1]); ?></span>
          </div>
        </div>
      <?php endif; ?>
      <div class="px-6 py-5">
        <?php if ($v->devices === []): ?>
          <div class="text-center py-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--saso-text-sub)" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <p class="text-sm" style="color:var(--saso-text-sub)"><?php echo ui_text($t('まだ連携された端末はありません。', 'No devices are linked yet.')); ?></p>
            <p class="text-xs mt-1" style="color:var(--saso-text-sub)"><?php echo ui_text($t('右上の「新しい端末を連携」ボタンから開始できます。', 'Click "Link new device" above to get started.')); ?></p>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b" style="border-color:var(--saso-card-bdr);color:var(--saso-text-sub)">
                  <th class="py-2 pr-4 font-medium"><?php echo ui_text($t('端末名', 'Device name')); ?></th>
                  <th class="py-2 pr-4 font-medium"><?php echo ui_text($t('登録日時', 'Registered')); ?></th>
                  <th class="py-2 pr-4 font-medium"><?php echo ui_text($t('最終利用', 'Last used')); ?></th>
                  <th class="py-2 pr-4 font-medium"><?php echo ui_text($t('有効期限', 'Expires')); ?></th>
                  <th class="py-2 font-medium text-right"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($v->devices as $device): ?>
                  <tr class="border-b" style="border-color:var(--saso-card-bdr)">
                    <td class="py-2 pr-4" style="color:var(--saso-text)"><?php echo ui_text((string) $device['device_name']); ?></td>
                    <td class="py-2 pr-4 font-mono text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text((string) $device['created_at']); ?></td>
                    <td class="py-2 pr-4 font-mono text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text((string) ($device['last_used_at'] ?? '-')); ?></td>
                    <td class="py-2 pr-4 font-mono text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text((string) $device['expires_at']); ?></td>
                    <td class="py-2 text-right">
                      <form method="post" action="./mypage/deviceRevoke/" onsubmit="return confirm('<?php echo ui_attr($t('この端末の連携を解除しますか？', 'Revoke this device?')); ?>');" style="display:inline">
                        <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
                        <input type="hidden" name="device_id" value="<?php echo (int) $device['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                          <?php echo ui_text($t('失効', 'Revoke')); ?>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Pairing modal -->
      <div x-show="open" x-cloak x-transition.opacity
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           style="background:rgba(0,0,0,0.5)"
           @keydown.escape.window="closeModal()"
           role="dialog" aria-modal="true">
        <div class="rounded-2xl border shadow-lg w-full max-w-lg overflow-hidden"
             style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
             @click.outside="closeModal()">
          <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
            <h3 class="font-semibold text-base" style="color:var(--saso-text)" x-text="labels.newDeviceTitle"></h3>
            <button type="button" class="text-sm" style="color:var(--saso-text-sub)" @click="closeModal()" aria-label="close">×</button>
          </div>
          <div class="px-6 py-5 flex flex-col gap-4">
            <!-- Step 1: input device name -->
            <template x-if="!qr">
              <div class="flex flex-col gap-3">
                <label class="text-sm font-medium" style="color:var(--saso-text)" x-text="labels.deviceName"></label>
                <input type="text" x-model="deviceName" :placeholder="labels.deviceNamePlaceholder" maxlength="200"
                       class="rounded border px-3 py-2 text-sm w-full"
                       style="background:var(--saso-card);border-color:var(--saso-card-bdr);color:var(--saso-text)"
                       @keydown.enter.prevent="generate()">
                <template x-if="error">
                  <div class="ta-alert ta-alert-danger text-sm" role="alert" x-text="error"></div>
                </template>
                <div class="flex justify-end gap-2 mt-1">
                  <button type="button" class="btn btn-secondary btn-sm" @click="closeModal()" x-text="labels.close"></button>
                  <button type="button" class="btn btn-primary btn-sm" @click="generate()" :disabled="loading">
                    <span x-show="!loading" x-text="labels.generate"></span>
                    <span x-show="loading" x-text="labels.generating"></span>
                  </button>
                </div>
              </div>
            </template>
            <!-- Step 2: display QR -->
            <template x-if="qr">
              <div class="flex flex-col gap-3">
                <p class="text-sm" style="color:var(--saso-text-sub)" x-text="labels.qrInstructions"></p>
                <div class="flex justify-center bg-white rounded p-3">
                  <img :src="qr.qrDataUri" alt="pairing QR" class="w-56 h-56 object-contain">
                </div>
                <div>
                  <div class="text-xs font-medium mb-1" style="color:var(--saso-text-sub)" x-text="labels.payload"></div>
                  <div class="flex items-center gap-2">
                    <code class="font-mono text-xs px-2 py-1 rounded border flex-1 break-all"
                          style="background:var(--saso-card-bdr);border-color:var(--saso-card-bdr);color:var(--saso-text)"
                          x-text="qr.qrPayload"></code>
                    <button type="button" class="btn btn-secondary btn-sm" @click="copyText(qr.qrPayload)">
                      <span x-show="!copiedPayload" x-text="labels.copy"></span>
                      <span x-show="copiedPayload" x-text="labels.copied"></span>
                    </button>
                  </div>
                </div>
                <div>
                  <div class="text-xs font-medium mb-1" style="color:var(--saso-text-sub)" x-text="labels.token"></div>
                  <div class="flex items-center gap-2">
                    <code class="font-mono text-xs px-2 py-1 rounded border flex-1 break-all"
                          style="background:var(--saso-card-bdr);border-color:var(--saso-card-bdr);color:var(--saso-text)"
                          x-text="qr.rawToken"></code>
                    <button type="button" class="btn btn-secondary btn-sm" @click="copyText(qr.rawToken, 'token')">
                      <span x-show="!copiedToken" x-text="labels.copy"></span>
                      <span x-show="copiedToken" x-text="labels.copied"></span>
                    </button>
                  </div>
                </div>
                <div class="flex items-center justify-between text-sm" style="color:var(--saso-text-sub)">
                  <span x-text="labels.expiresIn + ': ' + remainingLabel"></span>
                  <span x-show="expired" class="ta-badge ta-badge-danger" x-text="labels.expired"></span>
                </div>
                <div class="flex justify-end gap-2 mt-1">
                  <button type="button" class="btn btn-secondary btn-sm" @click="closeModal()" x-text="labels.close"></button>
                  <button type="button" class="btn btn-primary btn-sm" @click="reset()" x-text="labels.regenerate"></button>
                </div>
              </div>
            </template>
          </div>
        </div>
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

<script>
  (function () {
    if (typeof window.devicePairing === 'function') return;
    window.devicePairing = function (config) {
      return {
        open: false,
        loading: false,
        qr: null,
        deviceName: '',
        error: '',
        copiedPayload: false,
        copiedToken: false,
        expired: false,
        remainingMs: 0,
        remainingLabel: '',
        _timer: null,
        labels: config.labels,
        endpoint: config.endpoint,
        csrfToken: config.csrfToken,
        init() {},
        openModal() {
          this.open = true;
          this.error = '';
        },
        closeModal() {
          this.open = false;
          this.reset();
        },
        reset() {
          this.qr = null;
          this.deviceName = '';
          this.error = '';
          this.copiedPayload = false;
          this.copiedToken = false;
          this.expired = false;
          this.remainingMs = 0;
          this.remainingLabel = '';
          if (this._timer) {
            clearInterval(this._timer);
            this._timer = null;
          }
        },
        async generate() {
          if (this.loading) return;
          this.loading = true;
          this.error = '';
          try {
            const body = new FormData();
            body.append('csrftoken', this.csrfToken);
            body.append('device_name', this.deviceName || 'My Device');
            const res = await fetch(this.endpoint, {
              method: 'POST',
              body: body,
              credentials: 'same-origin',
              headers: { 'Accept': 'application/json' },
            });
            if (res.status === 401) {
              this.error = this.labels.errorAuth;
              return;
            }
            if (res.status === 403) {
              this.error = this.labels.errorCsrf;
              return;
            }
            if (!res.ok) {
              this.error = this.labels.errorGeneric;
              return;
            }
            const data = await res.json();
            this.qr = data;
            this.startCountdown(data.ttlSeconds || 600);
          } catch (e) {
            console.warn('device pairing failed', e);
            this.error = this.labels.errorGeneric;
          } finally {
            this.loading = false;
          }
        },
        startCountdown(seconds) {
          const deadline = Date.now() + seconds * 1000;
          this.expired = false;
          const tick = () => {
            const left = deadline - Date.now();
            this.remainingMs = left;
            if (left <= 0) {
              this.expired = true;
              this.remainingLabel = '0:00';
              if (this._timer) {
                clearInterval(this._timer);
                this._timer = null;
              }
              return;
            }
            const totalSec = Math.floor(left / 1000);
            const m = Math.floor(totalSec / 60);
            const s = totalSec % 60;
            this.remainingLabel = m + ':' + (s < 10 ? '0' + s : s);
          };
          tick();
          if (this._timer) clearInterval(this._timer);
          this._timer = setInterval(tick, 1000);
        },
        async copyText(text, scope) {
          try {
            await navigator.clipboard.writeText(text);
            if (scope === 'token') {
              this.copiedToken = true;
              setTimeout(() => { this.copiedToken = false; }, 1500);
            } else {
              this.copiedPayload = true;
              setTimeout(() => { this.copiedPayload = false; }, 1500);
            }
          } catch (e) {
            console.warn('clipboard failed', e);
          }
        },
      };
    };
  })();
</script>

<?php }; ?>
