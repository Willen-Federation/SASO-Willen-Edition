<?php $this->title = 'AI設定'; ?>
<?php $this->content = function($v) {
  $lang       = $_SESSION['lang'] ?? 'ja';
  $settings   = $v->settings ?? [];
  $authorized = $v->authorized ?? false;
  $saved      = $v->saved ?? false;
  $csrf       = \saso\util\CSRFtoken::current();

  $visionProvider  = $settings['ai_provider_vision']    ?? '';
  $chatProvider    = $settings['ai_provider_chat']      ?? '';
  $promptJa        = $settings['ai_prompt_ja']          ?? '';
  $promptEn        = $settings['ai_prompt_en']          ?? '';
  $rateLimit       = $settings['messaging_rate_limit']  ?? 10;
  $openaiKeys      = $settings['ai_openai_api_keys']    ?? [];
  $geminiKeys      = $settings['ai_gemini_api_keys']    ?? [];
  $anthropicKeys   = $settings['ai_anthropic_api_keys'] ?? [];
?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
  <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'AI設定' : 'AI Settings'; ?></li>
</ol>

<?php if (!$authorized): ?>
  <div class="alert alert-danger" role="note">
    <i class="bi bi-shield-x me-2"></i>
    <?php echo $lang === 'ja' ? 'このページへのアクセス権限がありません。' : 'You do not have permission to access this page.'; ?>
  </div>
  <?php return; ?>
<?php endif; ?>

<?php if ($saved): ?>
  <div class="alert alert-success" role="status">
    <i class="bi bi-check-circle me-2"></i>
    <?php echo $lang === 'ja' ? '設定を保存しました。' : 'Settings saved successfully.'; ?>
  </div>
<?php endif; ?>

<form method="post" action="" x-data="{
  openaiKeys: <?php echo json_encode(array_values($openaiKeys), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>,
  geminiKeys: <?php echo json_encode(array_values($geminiKeys), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>,
  anthropicKeys: <?php echo json_encode(array_values($anthropicKeys), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>,
  newOpenai: '', newGemini: '', newAnthropic: '',
  addKey(list, key) {
    const k = key.trim();
    if (k) { this[list].push(k); }
  },
  removeKey(list, idx) { this[list].splice(idx, 1); }
}">
  <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><?php echo $lang === 'ja' ? 'AIプロバイダー' : 'AI Provider'; ?></h3>
      <p class="card-subtitle text-secondary"><?php echo $lang === 'ja' ? 'ビジョン解析と会話に使用するAIプロバイダーを選択してください。' : 'Choose the AI provider for vision analysis and chat.'; ?></p>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="ai_provider_vision" class="form-label"><?php echo $lang === 'ja' ? 'ビジョンプロバイダー' : 'Vision Provider'; ?></label>
          <select id="ai_provider_vision" name="ai_provider_vision" class="form-select">
            <option value="" <?php echo $visionProvider === ''       ? 'selected' : ''; ?>><?php echo $lang === 'ja' ? 'なし' : 'None'; ?></option>
            <option value="openai" <?php echo $visionProvider === 'openai' ? 'selected' : ''; ?>>OpenAI</option>
            <option value="gemini" <?php echo $visionProvider === 'gemini' ? 'selected' : ''; ?>>Gemini</option>
            <option value="claude" <?php echo $visionProvider === 'claude' ? 'selected' : ''; ?>>Claude</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="ai_provider_chat" class="form-label"><?php echo $lang === 'ja' ? 'チャットプロバイダー' : 'Chat Provider'; ?></label>
          <select id="ai_provider_chat" name="ai_provider_chat" class="form-select">
            <option value="" <?php echo $chatProvider === ''       ? 'selected' : ''; ?>><?php echo $lang === 'ja' ? 'なし' : 'None'; ?></option>
            <option value="openai" <?php echo $chatProvider === 'openai' ? 'selected' : ''; ?>>OpenAI</option>
            <option value="gemini" <?php echo $chatProvider === 'gemini' ? 'selected' : ''; ?>>Gemini</option>
            <option value="claude" <?php echo $chatProvider === 'claude' ? 'selected' : ''; ?>>Claude</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><?php echo $lang === 'ja' ? 'APIキー' : 'API Keys'; ?></h3>
      <p class="card-subtitle text-secondary"><?php echo $lang === 'ja' ? '各プロバイダーのAPIキーを管理します。複数設定でラウンドロビン使用可能。' : 'Manage API keys per provider. Multiple keys round-robin.'; ?></p>
    </div>
    <div class="card-body">

      <?php
        $renderKeyList = static function (string $list, string $stateVar, string $newVar, string $providerLabel, string $badge, string $badgeBg, string $placeholder, string $lang): void { ?>
        <div class="mb-4">
          <h4 class="mb-3 d-flex align-items-center gap-2">
            <span class="badge <?php echo $badgeBg; ?> text-white"><?php echo $badge; ?></span>
            <?php echo $providerLabel; ?>
          </h4>
          <template x-for="(key, idx) in <?php echo $stateVar; ?>" :key="idx">
            <div class="input-group mb-2">
              <input type="hidden" :name="'<?php echo $list; ?>[' + idx + ']'" :value="key">
              <span class="input-group-text font-monospace flex-grow-1 text-start" x-text="key.length > 4 ? '•'.repeat(Math.max(0, key.length - 4)) + key.slice(-4) : key"></span>
              <button type="button" class="btn btn-outline-danger" @click="removeKey('<?php echo $stateVar; ?>', idx)" aria-label="<?php echo $lang === 'ja' ? 'キーを削除' : 'Remove key'; ?>">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </template>
          <div class="input-group">
            <input type="text" x-model="<?php echo $newVar; ?>" class="form-control font-monospace" placeholder="<?php echo $placeholder; ?>" autocomplete="off"
                   @keydown.enter.prevent="addKey('<?php echo $stateVar; ?>', <?php echo $newVar; ?>); <?php echo $newVar; ?> = ''">
            <button type="button" class="btn btn-primary"
                    @click="addKey('<?php echo $stateVar; ?>', <?php echo $newVar; ?>); <?php echo $newVar; ?> = ''">
              <i class="bi bi-plus me-1"></i><?php echo $lang === 'ja' ? '追加' : 'Add'; ?>
            </button>
          </div>
        </div>
      <?php }; ?>

      <?php $renderKeyList('ai_openai_api_keys',    'openaiKeys',    'newOpenai',    'OpenAI',            'AI', 'bg-dark',    'sk-...',     $lang); ?>
      <?php $renderKeyList('ai_gemini_api_keys',    'geminiKeys',    'newGemini',    'Gemini (Google)',   'G',  'bg-primary', 'AIza...',    $lang); ?>
      <?php $renderKeyList('ai_anthropic_api_keys', 'anthropicKeys', 'newAnthropic', 'Anthropic (Claude)', 'AN', 'bg-warning', 'sk-ant-...', $lang); ?>

    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><?php echo $lang === 'ja' ? '抽出プロンプト' : 'Extraction Prompt'; ?></h3>
      <p class="card-subtitle text-secondary"><?php echo $lang === 'ja' ? '商品画像から情報を抽出する際にAIに送信するプロンプト。' : 'Prompt sent to AI when extracting info from product images.'; ?></p>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="ai_prompt_ja" class="form-label"><?php echo $lang === 'ja' ? '日本語プロンプト' : 'Japanese Prompt'; ?></label>
          <textarea id="ai_prompt_ja" name="ai_prompt_ja" rows="6" class="form-control"><?php echo htmlspecialchars($promptJa); ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
          <label for="ai_prompt_en" class="form-label"><?php echo $lang === 'ja' ? '英語プロンプト' : 'English Prompt'; ?></label>
          <textarea id="ai_prompt_en" name="ai_prompt_en" rows="6" class="form-control"><?php echo htmlspecialchars($promptEn); ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><?php echo $lang === 'ja' ? 'バッチ処理' : 'Batch Processing'; ?></h3>
      <p class="card-subtitle text-secondary"><?php echo $lang === 'ja' ? 'AIバッチ処理のレート制限を設定します。' : 'Configure rate limits for AI batch processing.'; ?></p>
    </div>
    <div class="card-body">
      <div class="mb-3" style="max-width: 22em;">
        <label for="messaging_rate_limit" class="form-label">
          <?php echo $lang === 'ja' ? 'レート制限（リクエスト/分）' : 'Rate Limit (requests/minute)'; ?>
        </label>
        <input id="messaging_rate_limit" name="messaging_rate_limit" type="number" min="1" max="1000"
               value="<?php echo (int) $rateLimit; ?>" class="form-control">
        <div class="form-hint"><?php echo $lang === 'ja' ? '1分あたりの最大AIリクエスト数。' : 'Maximum AI requests per minute.'; ?></div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-floppy me-1"></i><?php echo $lang === 'ja' ? '設定を保存' : 'Save Settings'; ?>
    </button>
  </div>

</form>

<?php }; ?>
