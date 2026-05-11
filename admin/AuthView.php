<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;

final class AuthView implements View
{
    use Setter;

    private string $title = '';
    private \Closure $content;

    public function display(): void
    {
        $pdo  = \saso\repository\DBConnection::getPdo();
        $tz   = new \DateTimeZone('Asia/Tokyo');

        $encryptorResult = self::makeEncryptor();
        if ($encryptorResult instanceof SecretEncryptor) {
            $repo           = new PdoAuthProviderRepository($pdo, $encryptorResult, $tz);
            $encryptorError = null;
        } else {
            $repo           = null;
            $encryptorError = $encryptorResult;
        }

        $flashMsg  = null;
        $flashType = 'success';

        if ($repo !== null && !empty($_POST)) {
            $action = $_POST['action'] ?? null;

            switch ($action) {
                case 'toggle':
                    $id     = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
                    $record = ($id !== false) ? $repo->findById(new AuthProviderId((int) $id)) : null;
                    if ($record !== null) {
                        $repo->save($record->withEnabled(!$record->enabled));
                        $flashMsg = $record->enabled ? 'プロバイダを無効にしました。' : 'プロバイダを有効にしました。';
                    }
                    break;

                case 'set_default':
                    $id     = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
                    $target = ($id !== false) ? $repo->findById(new AuthProviderId((int) $id)) : null;
                    if ($target !== null) {
                        // Clear existing default first
                        foreach ($repo->listAll() as $existing) {
                            if ($existing->isDefault) {
                                $repo->save(new AuthProviderRecord(
                                    id: $existing->id,
                                    name: $existing->name,
                                    type: $existing->type,
                                    issuerOrMetadataUrl: $existing->issuerOrMetadataUrl,
                                    clientId: $existing->clientId,
                                    clientSecret: $existing->clientSecret,
                                    scopes: $existing->scopes,
                                    claimMapping: $existing->claimMapping,
                                    enabled: $existing->enabled,
                                    isDefault: false,
                                    createdAt: $existing->createdAt,
                                    updatedAt: $existing->updatedAt,
                                ));
                            }
                        }
                        $repo->save(new AuthProviderRecord(
                            id: $target->id,
                            name: $target->name,
                            type: $target->type,
                            issuerOrMetadataUrl: $target->issuerOrMetadataUrl,
                            clientId: $target->clientId,
                            clientSecret: $target->clientSecret,
                            scopes: $target->scopes,
                            claimMapping: $target->claimMapping,
                            enabled: $target->enabled,
                            isDefault: true,
                            createdAt: $target->createdAt,
                            updatedAt: $target->updatedAt,
                        ));
                        $flashMsg = 'デフォルトプロバイダを変更しました。';
                    }
                    break;

                case 'delete':
                    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
                    if ($id !== false) {
                        $repo->delete(new AuthProviderId((int) $id));
                        $flashMsg = 'プロバイダを削除しました。';
                    }
                    break;

                case 'create':
                    [$flashMsg, $flashType] = self::handleCreate($repo, $pdo, $tz);
                    break;
            }
        }

        $providers = ($repo !== null) ? $repo->listAll() : [];

        $this->title   = '認証プロバイダ管理';
        $this->content = function ($v) use ($providers, $flashMsg, $flashType, $encryptorError): void {
            $h = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            ?>
<?php if ($encryptorError !== null): ?>
<div class="ta-alert ta-alert-warning mb-4" role="alert">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
  <div class="text-sm">
    <strong>APP_KEY 未設定:</strong> <?php echo $h($encryptorError); ?><br>
    <span class="text-xs">クライアントシークレットの暗号化・復号には <code>APP_KEY</code> が必要です。
    <code>.env</code> に <code>APP_KEY=&lt;base64エンコードされた32バイトキー&gt;</code> を追加してください。
    インストーラーを再実行すると自動生成されます。</span>
  </div>
</div>
<?php endif; ?>

<?php if ($flashMsg !== null): ?>
<div class="ta-alert ta-alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?> mb-4"
     x-data="{ open: true }" x-show="open" role="alert">
  <div class="flex-1"><?php echo $flashMsg; ?></div>
  <button type="button" @click="open = false" class="ml-auto shrink-0 p-1 rounded hover:bg-black/10 dark:hover:bg-white/10" aria-label="閉じる">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
  </button>
</div>
<?php endif; ?>

<div class="rounded-2xl border shadow-sm mb-4 overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="px-6 py-4 border-b font-semibold text-base" style="color:var(--saso-text);border-color:var(--saso-card-bdr)">認証プロバイダ一覧</div>
  <div>
    <?php if (empty($providers)): ?>
    <p class="text-sm p-4" style="color:var(--saso-text-sub)">登録されている認証プロバイダはありません。</p>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="ta-table" aria-label="認証プロバイダ一覧">
      <thead>
        <tr>
          <th scope="col">名前</th>
          <th scope="col">種別</th>
          <th scope="col">発行者URL / メタデータURL</th>
          <th scope="col">クライアントID</th>
          <th scope="col">状態</th>
          <th scope="col">デフォルト</th>
          <th scope="col">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($providers as $p): ?>
        <tr>
          <td><?php echo $h($p->name); ?></td>
          <td>
            <span class="ta-badge <?php
              echo match($p->type) {
                  AuthProviderType::Oidc  => 'ta-badge-primary',
                  AuthProviderType::Saml  => 'ta-badge-secondary',
                  AuthProviderType::Local => 'ta-badge-secondary',
              };
            ?>">
              <?php echo strtoupper($p->type->value); ?>
            </span>
          </td>
          <td class="text-sm truncate" style="max-width:220px;color:var(--saso-text-sub)">
            <?php echo $h($p->issuerOrMetadataUrl ?? '—'); ?>
          </td>
          <td class="text-sm truncate" style="max-width:160px;color:var(--saso-text-sub)">
            <?php echo $h($p->clientId ?? '—'); ?>
          </td>
          <td>
            <form method="post" action="./admin/auth/" style="display:inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo $p->id->value; ?>">
              <button type="submit"
                class="btn btn-sm <?php echo $p->enabled ? 'btn-primary' : 'btn-secondary'; ?>"
                title="クリックで<?php echo $p->enabled ? '無効' : '有効'; ?>化">
                <?php echo $p->enabled ? '有効' : '無効'; ?>
              </button>
            </form>
          </td>
          <td>
            <?php if ($p->isDefault): ?>
              <span class="ta-badge ta-badge-warning">デフォルト</span>
            <?php else: ?>
              <form method="post" action="./admin/auth/" style="display:inline">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="id" value="<?php echo $p->id->value; ?>">
                <button type="submit" class="btn btn-warning btn-sm">デフォルトに設定</button>
              </form>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="./admin/auth/" style="display:inline"
              onsubmit="return confirm('プロバイダ「<?php echo $h($p->name); ?>」を削除しますか？')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $p->id->value; ?>">
              <button type="submit" class="btn btn-danger btn-sm">削除</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($encryptorError === null): ?>
<div class="rounded-2xl border shadow-sm" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="px-6 py-4 border-b font-semibold text-base" style="color:var(--saso-text);border-color:var(--saso-card-bdr)">新規プロバイダ追加</div>
  <div class="px-6 py-5">
    <form method="post" action="./admin/auth/" novalidate>
      <input type="hidden" name="action" value="create">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label for="auth_name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">名前 <span class="text-error-500">*</span></label>
          <input type="text" class="form-input w-full" id="auth_name" name="name"
            placeholder="例: Auth0 (Production)" required>
        </div>
        <div>
          <label for="auth_type" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">種別 <span class="text-error-500">*</span></label>
          <select class="form-input w-full" id="auth_type" name="type" required>
            <option value="oidc" selected>OIDC (Auth0, Cognito, Firebase 等)</option>
            <option value="saml">SAML</option>
            <option value="local">ローカル</option>
          </select>
        </div>
        <div>
          <label for="auth_issuer" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">発行者URL / メタデータURL</label>
          <input type="url" class="form-input w-full" id="auth_issuer" name="issuer_or_metadata_url"
            placeholder="例: https://your-domain.auth0.com/"
            aria-describedby="auth_issuer_help">
          <p id="auth_issuer_help" class="mt-1 text-xs" style="color:var(--saso-text-sub)">OIDC: issuer URL / SAML: metadata XML URL</p>
        </div>
        <div>
          <label for="auth_client_id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアントID</label>
          <input type="text" class="form-input w-full" id="auth_client_id" name="client_id"
            placeholder="例: abc123xyz">
        </div>
        <div>
          <label for="auth_secret" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアントシークレット</label>
          <input type="password" class="form-input w-full" id="auth_secret" name="client_secret"
            autocomplete="new-password" placeholder="保存時に暗号化されます">
        </div>
        <div>
          <label for="auth_scopes" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">スコープ</label>
          <input type="text" class="form-input w-full" id="auth_scopes" name="scopes"
            value="openid email profile" placeholder="スペース区切り">
        </div>
        <div class="flex items-center gap-6 sm:col-span-2 lg:col-span-3">
          <label class="flex items-center gap-2 text-sm cursor-pointer" style="color:var(--saso-text)">
            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" id="auth_enabled" name="enabled" checked>
            有効化
          </label>
          <label class="flex items-center gap-2 text-sm cursor-pointer" style="color:var(--saso-text)">
            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" id="auth_default" name="is_default">
            デフォルトに設定
          </label>
          <button type="submit" class="btn btn-primary ml-auto">プロバイダを追加</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
            <?php
        };
    }

    /** @return SecretEncryptor|string  SecretEncryptor on success, error message string on failure */
    private static function makeEncryptor(): SecretEncryptor|string
    {
        $env    = \saso\util\EnvLoader::loadFile('.env');
        $appKey = \saso\util\EnvLoader::get($env, 'APP_KEY');
        if ($appKey === null || $appKey === '') {
            return '.env に APP_KEY が設定されていません。';
        }

        // Accept base64-encoded 32-byte key (44 chars with padding)
        $raw = base64_decode($appKey, strict: true);
        if ($raw !== false && strlen($raw) === 32) {
            return new SecretEncryptor($raw);
        }

        // Accept hex-encoded 32-byte key (64 chars)
        if (preg_match('/^[0-9a-fA-F]{64}$/', $appKey)) {
            $raw = hex2bin($appKey);
            if ($raw !== false && strlen($raw) === 32) {
                return new SecretEncryptor($raw);
            }
        }

        // Derive 32 bytes via SHA-256 (allows any string as APP_KEY)
        return new SecretEncryptor(hash('sha256', $appKey, binary: true));
    }

    /** @return array{string|null, string}  [flashMsg, flashType] */
    private static function handleCreate(
        PdoAuthProviderRepository $repo,
        \PDO $pdo,
        \DateTimeZone $tz,
    ): array {
        $name       = trim($_POST['name'] ?? '');
        $typeStr    = $_POST['type'] ?? 'oidc';
        $issuer     = trim($_POST['issuer_or_metadata_url'] ?? '') ?: null;
        $clientId   = trim($_POST['client_id'] ?? '') ?: null;
        $secret     = $_POST['client_secret'] ?? '';
        $scopes     = trim($_POST['scopes'] ?? '') ?: null;
        $enabled    = !empty($_POST['enabled']);
        $isDefault  = !empty($_POST['is_default']);

        $errors = [];
        if ($name === '') {
            $errors[] = '名前を入力してください。';
        }

        $type = AuthProviderType::tryFrom($typeStr);
        if ($type === null) {
            $errors[] = '種別が無効です。';
        }

        if (!empty($errors)) {
            return [implode(' ', array_map(
                fn ($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'),
                $errors,
            )), 'danger'];
        }

        $nextIdStmt = $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM auth_provider');
        $nextId     = (int) ($nextIdStmt ? $nextIdStmt->fetchColumn() : 1);
        $now        = new \DateTimeImmutable('now', $tz);

        try {
            $repo->save(new AuthProviderRecord(
                id: new AuthProviderId($nextId),
                name: $name,
                type: $type,
                issuerOrMetadataUrl: $issuer,
                clientId: $clientId,
                clientSecret: ($secret !== '') ? $secret : null,
                scopes: $scopes,
                claimMapping: null,
                enabled: $enabled,
                isDefault: $isDefault,
                createdAt: $now,
                updatedAt: $now,
            ));
            return ['認証プロバイダを追加しました。', 'success'];
        } catch (\Exception $e) {
            return [htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), 'danger'];
        }
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
