<?php $this->content = function ($v) { ?>

<?php
  $lang   = $_SESSION['lang'] ?? 'ja';
  $isEdit = $v->mode === 'edit';
  $title  = $isEdit ? ($lang === 'ja' ? '認証プロバイダ編集' : 'Edit Auth Provider')
                     : ($lang === 'ja' ? '認証プロバイダ追加' : 'Add Auth Provider');

  // Parse claim_mapping for structured fields
  $claimRaw = $v->provider['claim_mapping'] ?? '{}';
  if (is_string($claimRaw)) {
      $claimDecoded = json_decode($claimRaw, true);
  } else {
      $claimDecoded = is_array($claimRaw) ? $claimRaw : [];
  }
  if (!is_array($claimDecoded)) $claimDecoded = [];
  $cfg = $claimDecoded['_config'] ?? [];
  if (!is_array($cfg)) $cfg = [];

  // Strip _config for the "raw overrides" textarea
  $claimOverrides = $claimDecoded;
  unset($claimOverrides['_config']);
  $claimOverridesJson = json_encode($claimOverrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($claimOverridesJson === '[]' || $claimOverridesJson === false) $claimOverridesJson = '{}';

  $provType = $v->provider['type'] ?? 'oidc';
?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.auth_providers.forbidden_title', [], null, '管理者権限が必要です'),
    'body'    => __('ui.auth_providers.forbidden_body', [], null, '認証プロバイダを管理するには role=admin のユーザーでサインインしてください。'),
  ]); ?>
<?php } else { ?>

  <?php
    ui('card', [
      'title'   => $title,
      'actions' => function () use ($lang) {
          ui('button', [
              'label'   => $lang === 'ja' ? '一覧に戻る' : 'Back to list',
              'href'    => './auth/providers/',
              'type'    => 'link',
              'variant' => 'secondary',
          ]);
      },
      'body'    => function () use ($v, $isEdit, $lang, $cfg, $claimOverridesJson, $provType) {
  ?>
    <form method="POST" action="" x-data="{
        providerType: '<?php echo ui_attr($provType); ?>',
        flavor: '<?php echo ui_attr($cfg['flavor'] ?? 'oidc'); ?>',
        auth0Domain: '<?php echo ui_attr($cfg['domain'] ?? ''); ?>',
        get auth0IssuerUrl() {
            return this.auth0Domain ? 'https://' + this.auth0Domain + '/.well-known/openid-configuration' : '';
        },
        onFlavorChange() {
            if (this.flavor === 'auth0') {
                var s = document.getElementById('scopes');
                if (s && s.value.trim() === '') s.value = 'openid profile email offline_access';
            }
            if (this.flavor === 'auth0' && this.auth0Domain) this.syncIssuer();
        },
        syncIssuer() {
            if (this.flavor !== 'auth0' || !this.auth0Domain) return;
            var f = document.getElementById('issuer_or_metadata_url');
            if (f) f.value = this.auth0IssuerUrl;
        }
    }">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current()); ?>">

      <?php if (!empty($v->message)): ?>
        <?php ui('alert', ['variant' => 'danger', 'body' => $v->message]); ?>
      <?php endif; ?>

      <!-- ── Common Fields ── -->

      <?php
      ui('formField', [
        'name'        => 'name',
        'label'       => $lang === 'ja' ? 'プロバイダ名' : 'Provider Name',
        'value'       => $v->provider['name'] ?? '',
        'required'    => true,
        'placeholder' => $lang === 'ja' ? 'プロバイダ名を入力' : 'Enter provider name',
      ]);
      ?>

      <div class="mb-4">
        <label for="type" class="mb-2.5 block font-medium text-black dark:text-white">
          <?php echo $lang === 'ja' ? 'タイプ' : 'Type'; ?>
        </label>
        <select id="type" name="type"
                x-model="providerType"
                class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
          <option value="oidc">OIDC</option>
          <option value="saml">SAML</option>
        </select>
      </div>

      <?php
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => $lang === 'ja' ? 'Issuer / メタデータ URL' : 'Issuer / Metadata URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://example.com/.well-known/openid-configuration',
        'help'        => $lang === 'ja' ? 'OIDC: Discovery URL、SAML: IdP Entity ID / SSO URL' : 'OIDC: Discovery URL, SAML: IdP Entity ID / SSO URL',
      ]);
      ?>

      <!-- ── OIDC-specific fields ── -->
      <div x-show="providerType === 'oidc'" x-cloak>
        <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
            <?php echo $lang === 'ja' ? 'OIDC 設定' : 'OIDC Settings'; ?>
          </h4>
        </div>

        <?php
        ?>
        <div class="mb-4">
          <label for="flavor" class="mb-2.5 block font-medium text-black dark:text-white">
            <?php echo $lang === 'ja' ? 'フレーバー' : 'Flavor'; ?>
          </label>
          <select id="flavor" name="flavor"
                  x-model="flavor"
                  @change="onFlavorChange()"
                  class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
            <option value="oidc">Generic OIDC</option>
            <option value="auth0">Auth0</option>
            <option value="cognito">AWS Cognito</option>
            <option value="firebase">Firebase / Google</option>
          </select>
          <p class="mt-1 text-sm text-bodydark2">
            <?php echo $lang === 'ja' ? 'プロバイダ固有の動作を設定します' : 'Selects provider-specific behavior'; ?>
          </p>
        </div>

        <!-- ── Auth0-specific fields ── -->
        <div x-show="flavor === 'auth0'" x-cloak>
          <div class="mb-4 rounded border border-stroke bg-whiter p-4 dark:border-form-strokedark dark:bg-form-input">
            <h5 class="mb-3 text-xs font-semibold uppercase tracking-wider text-bodydark2">Auth0</h5>

            <div class="mb-4">
              <label for="auth0_domain" class="mb-2.5 block font-medium text-black dark:text-white">
                <?php echo $lang === 'ja' ? 'Auth0 ドメイン' : 'Auth0 Domain'; ?> <span class="text-meta-1">*</span>
              </label>
              <input type="text" id="auth0_domain" name="auth0_domain"
                     x-model="auth0Domain"
                     @input="syncIssuer()"
                     value="<?php echo ui_attr($cfg['domain'] ?? ''); ?>"
                     placeholder="acme.auth0.com"
                     class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
              <p class="mt-1 text-sm text-bodydark2">
                <?php echo $lang === 'ja'
                  ? 'Auth0 テナントドメイン（例: acme.auth0.com）。入力すると Issuer URL が自動補完されます。'
                  : 'Your Auth0 tenant domain (e.g. acme.auth0.com). Fills the Issuer URL automatically.'; ?>
              </p>
            </div>

            <div class="mb-2">
              <label for="auth0_audience" class="mb-2.5 block font-medium text-black dark:text-white">
                <?php echo $lang === 'ja' ? 'API Audience（任意）' : 'API Audience (optional)'; ?>
              </label>
              <input type="text" id="auth0_audience" name="auth0_audience"
                     value="<?php echo ui_attr($cfg['audience'] ?? ''); ?>"
                     placeholder="https://api.example.com"
                     class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
              <p class="mt-1 text-sm text-bodydark2">
                <?php echo $lang === 'ja'
                  ? 'API アクセストークンが必要な場合のみ設定してください。'
                  : 'Set only when you need access tokens for an API. Leave blank for ID-token-only flows.'; ?>
              </p>
            </div>
          </div>
        </div>
        <?php

        ui('formField', [
          'name'        => 'client_id',
          'label'       => 'Client ID',
          'value'       => $v->provider['client_id'] ?? '',
          'placeholder' => 'your-client-id',
        ]);
        ?>

        <div class="mb-4">
          <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
            Client Secret
          </label>
          <input type="password" id="client_secret" name="client_secret"
                 value=""
                 placeholder="<?php echo $v->hasSecret ? '●●●●●●●● (leave blank to keep current)' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter client secret'); ?>"
                 autocomplete="new-password"
                 class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
          <?php if ($v->hasSecret): ?>
            <p class="mt-1 text-sm text-bodydark2">
              <?php echo $lang === 'ja' ? '既にシークレットが設定されています。変更する場合のみ入力してください。' : 'A secret is already set. Enter a value only to replace it.'; ?>
            </p>
          <?php endif; ?>
        </div>

        <?php
        ?>
        <div class="mb-4">
          <label for="scopes" class="mb-2.5 block font-medium text-black dark:text-white">
            <?php echo $lang === 'ja' ? 'スコープ' : 'Scopes'; ?>
          </label>
          <input type="text" id="scopes" name="scopes"
                 value="<?php echo ui_attr($v->provider['scopes'] ?? ''); ?>"
                 placeholder="openid profile email"
                 class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
          <p class="mt-1 text-sm text-bodydark2" x-show="flavor !== 'auth0'">
            <?php echo $lang === 'ja' ? '空白区切り。空の場合は openid profile email がデフォルトです' : 'Space-separated. Defaults to "openid profile email" if empty.'; ?>
          </p>
          <p class="mt-1 text-sm text-bodydark2" x-show="flavor === 'auth0'" x-cloak>
            <?php echo $lang === 'ja'
              ? '空の場合は openid profile email offline_access がデフォルトです（Auth0 推奨）。'
              : 'Defaults to "openid profile email offline_access" for Auth0 (enables refresh tokens).'; ?>
          </p>
        </div>
        <?php
        ?>

        <?php if ($isEdit && $v->callbackUrl !== ''): ?>
          <div class="mb-4">
            <label class="mb-2.5 block font-medium text-black dark:text-white">
              Callback URL
            </label>
            <div class="flex items-center gap-2">
              <input type="text" readonly
                     value="<?php echo ui_attr($v->callbackUrl); ?>"
                     class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm outline-none dark:border-form-strokedark dark:bg-meta-4 text-black dark:text-white"
                     onclick="this.select()">
              <button type="button"
                      class="btn btn-ghost btn-sm shrink-0"
                      onclick="navigator.clipboard.writeText('<?php echo ui_attr($v->callbackUrl); ?>')"
                      title="Copy">
                📋
              </button>
            </div>
            <p class="mt-1 text-sm text-bodydark2">
              <?php echo $lang === 'ja' ? 'IdP の Allowed Callback URLs に登録してください' : 'Register this URL in your IdP\'s Allowed Callback URLs'; ?>
            </p>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── SAML-specific fields ── -->
      <div x-show="providerType === 'saml'" x-cloak>
        <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
            <?php echo $lang === 'ja' ? 'SAML 設定' : 'SAML Settings'; ?>
          </h4>
        </div>

        <?php
        ui('formField', [
          'name'        => 'entity_id',
          'label'       => 'SP Entity ID',
          'value'       => $cfg['entity_id'] ?? '',
          'placeholder' => 'https://your-app.example.com/saml/metadata',
          'help'        => $lang === 'ja' ? '空の場合は ACS URL がデフォルトになります' : 'Defaults to the ACS URL if empty',
        ]);

        ui('formField', [
          'name'    => 'nameid_format',
          'label'   => 'NameID Format',
          'type'    => 'select',
          'value'   => $cfg['nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
          'options' => [
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'        => 'Email Address',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'          => 'Persistent',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'           => 'Transient',
            'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'         => 'Unspecified',
          ],
        ]);

        ui('formField', [
          'name'        => 'idp_x509_cert',
          'label'       => $lang === 'ja' ? 'IdP 証明書 (X.509 PEM)' : 'IdP Certificate (X.509 PEM)',
          'type'        => 'textarea',
          'value'       => $cfg['idp_x509_cert'] ?? '',
          'rows'        => 6,
          'placeholder' => "-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----",
          'help'        => $lang === 'ja' ? 'IdP のメタデータから X.509 証明書を貼り付けてください' : 'Paste the X.509 certificate from your IdP metadata',
        ]);

        ui('formField', [
          'name'        => 'sp_x509_cert',
          'label'       => $lang === 'ja' ? 'SP 証明書 (X.509 PEM)' : 'SP Certificate (X.509 PEM)',
          'type'        => 'textarea',
          'value'       => $cfg['sp_x509_cert'] ?? '',
          'rows'        => 4,
          'placeholder' => "-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----",
          'help'        => $lang === 'ja' ? 'SP のリクエスト署名用 (オプション)' : 'For SP request signing (optional)',
        ]);

        ui('formField', [
          'name'        => 'sp_private_key',
          'label'       => $lang === 'ja' ? 'SP 秘密鍵 (PEM)' : 'SP Private Key (PEM)',
          'type'        => 'textarea',
          'value'       => $cfg['sp_private_key'] ?? '',
          'rows'        => 4,
          'placeholder' => "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----",
          'help'        => $lang === 'ja' ? 'SP 証明書に対応する秘密鍵 (オプション)' : 'Matching private key for the SP certificate (optional)',
        ]);
        ?>

        <?php if ($isEdit && $v->acsUrl !== ''): ?>
          <div class="mb-4">
            <label class="mb-2.5 block font-medium text-black dark:text-white">
              ACS URL (Assertion Consumer Service)
            </label>
            <div class="flex items-center gap-2">
              <input type="text" readonly
                     value="<?php echo ui_attr($v->acsUrl); ?>"
                     class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm outline-none dark:border-form-strokedark dark:bg-meta-4 text-black dark:text-white"
                     onclick="this.select()">
              <button type="button"
                      class="btn btn-ghost btn-sm shrink-0"
                      onclick="navigator.clipboard.writeText('<?php echo ui_attr($v->acsUrl); ?>')"
                      title="Copy">
                📋
              </button>
            </div>
          </div>

          <div class="mb-4">
            <label class="mb-2.5 block font-medium text-black dark:text-white">
              SLS URL (Single Logout Service)
            </label>
            <div class="flex items-center gap-2">
              <input type="text" readonly
                     value="<?php echo ui_attr($v->slsUrl); ?>"
                     class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm outline-none dark:border-form-strokedark dark:bg-meta-4 text-black dark:text-white"
                     onclick="this.select()">
              <button type="button"
                      class="btn btn-ghost btn-sm shrink-0"
                      onclick="navigator.clipboard.writeText('<?php echo ui_attr($v->slsUrl); ?>')"
                      title="Copy">
                📋
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── Advanced: Claim Mapping overrides ── -->
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
          <?php echo $lang === 'ja' ? '詳細設定' : 'Advanced'; ?>
        </h4>
      </div>

      <?php
      ui('formField', [
        'name'        => 'claim_mapping_raw',
        'label'       => $lang === 'ja' ? 'クレームマッピング (JSON)' : 'Claim Mapping Overrides (JSON)',
        'type'        => 'textarea',
        'value'       => $claimOverridesJson,
        'rows'        => 4,
        'placeholder' => '{"subject": "sub", "email": "email", "display_name": "name"}',
        'help'        => $lang === 'ja' ? 'IdP クレーム名のカスタムマッピング。_config は上の設定から自動生成されます' : 'Custom IdP claim name overrides. _config is built automatically from the fields above',
      ]);
      ?>

      <!-- ── Toggles ── -->
      <div class="mb-5.5 flex flex-wrap items-center gap-6">
        <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
          <input type="checkbox" name="enabled" class="mr-1" <?php echo !empty($v->provider['enabled']) ? 'checked' : ''; ?>>
          <?php echo $lang === 'ja' ? '有効' : 'Enabled'; ?>
        </label>
        <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
          <input type="checkbox" name="is_default" class="mr-1" <?php echo !empty($v->provider['is_default']) ? 'checked' : ''; ?>>
          <?php echo $lang === 'ja' ? 'デフォルトに設定' : 'Set as Default'; ?>
        </label>
      </div>

      <?php
      ui('button', [
        'label'      => $isEdit ? ($lang === 'ja' ? '更新する' : 'Update') : ($lang === 'ja' ? '追加する' : 'Save'),
        'type'       => 'submit',
        'variant'    => 'primary',
        'extraClass' => 'w-full justify-center',
      ]);
      ?>
    </form>
  <?php
      },
    ]);
  ?>

<?php } ?>

<?php }; ?>
