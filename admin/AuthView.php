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
<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active" aria-current="page">認証プロバイダ管理</li>
</ol>
</nav>

<?php if ($encryptorError !== null): ?>
<div class="alert alert-warning" role="alert">
  <strong>APP_KEY 未設定:</strong> <?php echo $h($encryptorError); ?><br>
  <small>クライアントシークレットの暗号化・復号には <code>APP_KEY</code> が必要です。
  <code>.env</code> に <code>APP_KEY=&lt;base64エンコードされた32バイトキー&gt;</code> を追加してください。
  インストーラーを再実行すると自動生成されます。</small>
</div>
<?php endif; ?>

<?php if ($flashMsg !== null): ?>
<div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show" role="alert">
  <?php echo $flashMsg; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header fw-bold">認証プロバイダ一覧</div>
  <div class="card-body p-0">
    <?php if (empty($providers)): ?>
    <p class="text-muted p-3 mb-0">登録されている認証プロバイダはありません。</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0" aria-label="認証プロバイダ一覧">
      <thead class="table-dark">
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
            <span class="badge <?php
              echo match($p->type) {
                  AuthProviderType::Oidc  => 'bg-primary',
                  AuthProviderType::Saml  => 'bg-info text-dark',
                  AuthProviderType::Local => 'bg-secondary',
              };
            ?>">
              <?php echo strtoupper($p->type->value); ?>
            </span>
          </td>
          <td class="small text-truncate" style="max-width:220px">
            <?php echo $h($p->issuerOrMetadataUrl ?? '—'); ?>
          </td>
          <td class="small text-truncate" style="max-width:160px">
            <?php echo $h($p->clientId ?? '—'); ?>
          </td>
          <td>
            <form method="post" action="./admin/auth/" class="d-inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo $p->id->value; ?>">
              <button type="submit"
                class="btn btn-sm <?php echo $p->enabled ? 'btn-success' : 'btn-secondary'; ?>"
                title="クリックで<?php echo $p->enabled ? '無効' : '有効'; ?>化">
                <?php echo $p->enabled ? '有効' : '無効'; ?>
              </button>
            </form>
          </td>
          <td>
            <?php if ($p->isDefault): ?>
              <span class="badge bg-warning text-dark">デフォルト</span>
            <?php else: ?>
              <form method="post" action="./admin/auth/" class="d-inline">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="id" value="<?php echo $p->id->value; ?>">
                <button type="submit" class="btn btn-sm btn-outline-warning">デフォルトに設定</button>
              </form>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="./admin/auth/" class="d-inline"
              onsubmit="return confirm('プロバイダ「<?php echo $h($p->name); ?>」を削除しますか？')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $p->id->value; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
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
<div class="card">
  <div class="card-header fw-bold">新規プロバイダ追加</div>
  <div class="card-body">
    <form method="post" action="./admin/auth/" novalidate>
      <input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="auth_name" class="form-label">名前 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="auth_name" name="name"
            placeholder="例: Auth0 (Production)" required>
        </div>
        <div class="col-md-3">
          <label for="auth_type" class="form-label">種別 <span class="text-danger">*</span></label>
          <select class="form-select" id="auth_type" name="type" required>
            <option value="oidc" selected>OIDC (Auth0, Cognito, Firebase 等)</option>
            <option value="saml">SAML</option>
            <option value="local">ローカル</option>
          </select>
        </div>
        <div class="col-md-5">
          <label for="auth_issuer" class="form-label">発行者URL / メタデータURL</label>
          <input type="url" class="form-control" id="auth_issuer" name="issuer_or_metadata_url"
            placeholder="例: https://your-domain.auth0.com/"
            aria-describedby="auth_issuer_help">
          <div id="auth_issuer_help" class="form-text">OIDC: issuer URL / SAML: metadata XML URL</div>
        </div>
        <div class="col-md-4">
          <label for="auth_client_id" class="form-label">クライアントID</label>
          <input type="text" class="form-control" id="auth_client_id" name="client_id"
            placeholder="例: abc123xyz">
        </div>
        <div class="col-md-4">
          <label for="auth_secret" class="form-label">クライアントシークレット</label>
          <input type="password" class="form-control" id="auth_secret" name="client_secret"
            autocomplete="new-password" placeholder="保存時に暗号化されます">
        </div>
        <div class="col-md-4">
          <label for="auth_scopes" class="form-label">スコープ</label>
          <input type="text" class="form-control" id="auth_scopes" name="scopes"
            value="openid email profile" placeholder="スペース区切り">
        </div>
        <div class="col-12 d-flex gap-4 align-items-center">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="auth_enabled" name="enabled" checked>
            <label class="form-check-label" for="auth_enabled">有効化</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="auth_default" name="is_default">
            <label class="form-check-label" for="auth_default">デフォルトに設定</label>
          </div>
          <button type="submit" class="btn btn-primary ms-auto">プロバイダを追加</button>
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
