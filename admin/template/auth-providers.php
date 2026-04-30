<?php $this->title = '外部認証設定'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
  $providers = $v->providers ?? [];
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? '外部認証設定' : 'Auth Providers'; ?></li>
  </ol>
</nav>

<div class="mb-6 alert alert-warning">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
  <span class="text-sm"><?php echo $lang === 'ja' ? 'シークレット情報は暗号化して保存されます。設定変更後は必ず接続テストを実行してください。' : 'Secrets are stored encrypted. Always run a connection test after changing settings.'; ?></span>
</div>

<!-- Add provider button -->
<div class="mb-4 flex justify-end">
  <button
    type="button"
    x-data
    @click="$dispatch('open-add-provider')"
    class="btn-primary px-6"
  >
    + <?php echo $lang === 'ja' ? '認証プロバイダーを追加' : 'Add Auth Provider'; ?>
  </button>
</div>

<!-- Providers list -->
<div
  x-data="{
    showAddModal: false,
    editProvider: null,
    providerType: 'oidc',
    testStatus: {},
    async testConnection(id) {
      this.testStatus[id] = 'loading';
      try {
        const r = await fetch('./admin/auth-providers/test/' + id, {method:'POST'});
        const d = await r.json();
        this.testStatus[id] = d.ok ? 'ok' : 'fail';
      } catch(e) { this.testStatus[id] = 'fail'; }
    }
  }"
  @open-add-provider.window="showAddModal = true"
>

  <?php if(empty($providers)): ?>
  <div class="card flex flex-col items-center gap-4 py-16 text-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-stroke dark:text-strokedark" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
    <p class="text-body dark:text-bodydark"><?php echo $lang === 'ja' ? '外部認証プロバイダーが設定されていません' : 'No external auth providers configured'; ?></p>
    <p class="text-sm text-body dark:text-bodydark"><?php echo $lang === 'ja' ? 'Auth0, AWS Cognito, Firebase Auth, OIDC, SAML などを追加できます' : 'You can add Auth0, AWS Cognito, Firebase Auth, OIDC, SAML, etc.'; ?></p>
    <button type="button" @click="showAddModal = true" class="btn-primary px-8">
      + <?php echo $lang === 'ja' ? '最初のプロバイダーを追加' : 'Add First Provider'; ?>
    </button>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <?php foreach($providers as $p): ?>
    <div class="card">
      <div class="card-header flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Provider icon -->
          <?php if($p->type === 'auth0'): ?>
          <div class="flex h-10 w-10 items-center justify-center rounded bg-black">
            <span class="text-white font-bold text-xs">A0</span>
          </div>
          <?php elseif($p->type === 'cognito'): ?>
          <div class="flex h-10 w-10 items-center justify-center rounded bg-orange-500">
            <span class="text-white font-bold text-xs">AWS</span>
          </div>
          <?php elseif($p->type === 'firebase'): ?>
          <div class="flex h-10 w-10 items-center justify-center rounded bg-yellow-500">
            <span class="text-white font-bold text-xs">FB</span>
          </div>
          <?php elseif($p->type === 'oidc'): ?>
          <div class="flex h-10 w-10 items-center justify-center rounded bg-primary">
            <span class="text-white font-bold text-xs">OIDC</span>
          </div>
          <?php elseif($p->type === 'saml'): ?>
          <div class="flex h-10 w-10 items-center justify-center rounded bg-meta-5">
            <span class="text-white font-bold text-xs">SAML</span>
          </div>
          <?php else: ?>
          <div class="flex h-10 w-10 items-center justify-center rounded bg-bodydark">
            <span class="text-white font-bold text-xs">SSO</span>
          </div>
          <?php endif; ?>
          <div>
            <p class="font-semibold text-black dark:text-white"><?php echo htmlspecialchars($p->name ?? $p->type); ?></p>
            <p class="text-xs text-body dark:text-bodydark uppercase"><?php echo htmlspecialchars($p->type); ?></p>
          </div>
        </div>
        <!-- Enabled toggle -->
        <form method="post" action="./admin/auth-providers/toggle/<?php echo (int)$p->id; ?>/">
          <label class="toggle" aria-label="<?php echo $lang === 'ja' ? 'プロバイダーを有効化' : 'Enable provider'; ?>">
            <input type="checkbox" name="enabled" value="1" <?php echo $p->enabled ? 'checked' : ''; ?> onchange="this.form.submit()">
            <span class="toggle-slider"></span>
          </label>
        </form>
      </div>
      <div class="card-body">
        <dl class="grid grid-cols-1 gap-1 text-sm">
          <?php if(!empty($p->issuerUrl)): ?>
          <div class="flex gap-2">
            <dt class="text-body dark:text-bodydark w-28 shrink-0"><?php echo $lang === 'ja' ? '発行者URL' : 'Issuer URL'; ?></dt>
            <dd class="font-medium text-black dark:text-white truncate"><?php echo htmlspecialchars($p->issuerUrl); ?></dd>
          </div>
          <?php endif; ?>
          <?php if(!empty($p->clientId)): ?>
          <div class="flex gap-2">
            <dt class="text-body dark:text-bodydark w-28 shrink-0"><?php echo $lang === 'ja' ? 'クライアントID' : 'Client ID'; ?></dt>
            <dd class="font-medium text-black dark:text-white truncate"><?php echo htmlspecialchars($p->clientId); ?></dd>
          </div>
          <?php endif; ?>
        </dl>
        <div class="mt-4 flex gap-2">
          <button type="button" @click="testConnection(<?php echo (int)$p->id; ?>)"
            class="btn-secondary btn-sm text-xs px-3 py-1.5 flex items-center gap-1">
            <span x-show="testStatus[<?php echo (int)$p->id; ?>] === 'loading'">
              <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </span>
            <span x-show="testStatus[<?php echo (int)$p->id; ?>] === 'ok'" class="text-success">✓</span>
            <span x-show="testStatus[<?php echo (int)$p->id; ?>] === 'fail'" class="text-danger">✗</span>
            <span x-show="!testStatus[<?php echo (int)$p->id; ?>]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <?php echo $lang === 'ja' ? '接続テスト' : 'Test'; ?>
          </button>
          <a href="./admin/auth-providers/edit/<?php echo (int)$p->id; ?>/" class="btn-secondary btn-sm text-xs px-3 py-1.5">
            <?php echo $lang === 'ja' ? '編集' : 'Edit'; ?>
          </a>
          <form method="post" action="./admin/auth-providers/delete/<?php echo (int)$p->id; ?>/" onsubmit="return confirm('<?php echo $lang === 'ja' ? '削除してよろしいですか？' : 'Are you sure to delete?'; ?>')">
            <button type="submit" class="btn-danger btn-sm text-xs px-3 py-1.5">
              <?php echo $lang === 'ja' ? '削除' : 'Delete'; ?>
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Add/Edit modal -->
  <div
    x-show="showAddModal"
    x-transition
    class="fixed inset-0 z-99999 flex items-center justify-center bg-black bg-opacity-50"
    role="dialog"
    aria-modal="true"
    aria-label="<?php echo $lang === 'ja' ? '認証プロバイダー追加' : 'Add Auth Provider'; ?>"
  >
    <div class="w-full max-w-lg rounded-sm bg-white dark:bg-boxdark shadow-default mx-4 max-h-screen overflow-y-auto" @click.away="showAddModal = false">
      <div class="flex items-center justify-between border-b border-stroke dark:border-strokedark px-6 py-4">
        <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '認証プロバイダーを追加' : 'Add Auth Provider'; ?></h3>
        <button @click="showAddModal = false" class="text-body hover:text-black dark:hover:text-white" aria-label="閉じる">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form method="post" action="./admin/auth-providers/create/" class="p-6 space-y-4">
        <div>
          <label class="form-label"><?php echo $lang === 'ja' ? 'プロバイダーの種類' : 'Provider Type'; ?> <span class="text-danger">*</span></label>
          <select name="type" x-model="providerType" class="form-select" required aria-required="true">
            <option value="oidc">OpenID Connect (OIDC)</option>
            <option value="saml">SAML 2.0</option>
            <option value="auth0">Auth0</option>
            <option value="cognito">AWS Cognito</option>
            <option value="firebase">Firebase Auth</option>
          </select>
        </div>
        <div>
          <label class="form-label"><?php echo $lang === 'ja' ? '表示名' : 'Display Name'; ?> <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-input" required aria-required="true" placeholder="<?php echo $lang === 'ja' ? '例: 社内Auth0' : 'e.g. Company Auth0'; ?>">
        </div>

        <!-- OIDC / Auth0 / Cognito / Firebase fields -->
        <div x-show="['oidc','auth0','cognito','firebase'].includes(providerType)">
          <label class="form-label"><?php echo $lang === 'ja' ? '発行者URL (Issuer URL)' : 'Issuer URL'; ?></label>
          <input type="url" name="issuerUrl" class="form-input" placeholder="https://your-domain.auth0.com">
        </div>
        <div x-show="['oidc','auth0','cognito','firebase'].includes(providerType)">
          <label class="form-label"><?php echo $lang === 'ja' ? 'クライアントID' : 'Client ID'; ?> <span class="text-danger" x-show="providerType !== 'saml'">*</span></label>
          <input type="text" name="clientId" class="form-input" placeholder="your-client-id">
        </div>
        <div x-show="['oidc','auth0','cognito'].includes(providerType)">
          <label class="form-label"><?php echo $lang === 'ja' ? 'クライアントシークレット' : 'Client Secret'; ?> <span class="text-danger">*</span></label>
          <input type="password" name="clientSecret" class="form-input" placeholder="••••••••" autocomplete="new-password">
          <p class="mt-1 text-xs text-body"><?php echo $lang === 'ja' ? '暗号化して保存されます' : 'Stored encrypted'; ?></p>
        </div>

        <!-- SAML fields -->
        <div x-show="providerType === 'saml'">
          <label class="form-label"><?php echo $lang === 'ja' ? 'IdP メタデータ URL' : 'IdP Metadata URL'; ?></label>
          <input type="url" name="metadataUrl" class="form-input" placeholder="https://idp.example.com/metadata.xml">
        </div>
        <div x-show="providerType === 'saml'">
          <label class="form-label"><?php echo $lang === 'ja' ? 'エンティティID' : 'Entity ID'; ?></label>
          <input type="text" name="entityId" class="form-input" placeholder="your-entity-id">
        </div>

        <!-- Firebase specific -->
        <div x-show="providerType === 'firebase'">
          <label class="form-label"><?php echo $lang === 'ja' ? 'Firebaseプロジェクト ID' : 'Firebase Project ID'; ?></label>
          <input type="text" name="firebaseProjectId" class="form-input" placeholder="my-firebase-project">
        </div>

        <!-- Cognito specific -->
        <div x-show="providerType === 'cognito'">
          <label class="form-label"><?php echo $lang === 'ja' ? 'ユーザープール ID' : 'User Pool ID'; ?></label>
          <input type="text" name="userPoolId" class="form-input" placeholder="us-east-1_xxxxxxxxx">
        </div>
        <div x-show="providerType === 'cognito'">
          <label class="form-label"><?php echo $lang === 'ja' ? 'リージョン' : 'AWS Region'; ?></label>
          <input type="text" name="region" class="form-input" placeholder="ap-northeast-1">
        </div>

        <!-- Redirect URI info -->
        <div class="rounded border border-stroke dark:border-strokedark bg-gray-2 dark:bg-meta-4 p-3 text-xs text-body dark:text-bodydark">
          <p class="font-semibold text-black dark:text-white mb-1"><?php echo $lang === 'ja' ? 'コールバックURL（プロバイダーに登録）' : 'Callback URL (register with provider)'; ?></p>
          <code class="break-all"><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain') . '/auth/callback/'; ?></code>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="btn-primary flex-1">
            <?php echo $lang === 'ja' ? '追加する' : 'Add Provider'; ?>
          </button>
          <button type="button" @click="showAddModal = false" class="btn-secondary flex-1">
            <?php echo $lang === 'ja' ? 'キャンセル' : 'Cancel'; ?>
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php }; ?>
