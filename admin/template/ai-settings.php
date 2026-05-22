<?php $this->title = 'AI設定'; ?>
<?php $this->content = function($v) {
  $lang       = $_SESSION['lang'] ?? 'ja';
  $settings   = $v->settings ?? [];
  $authorized = $v->authorized ?? false;
  $saved      = $v->saved ?? false;
  $loadError  = $v->loadError ?? null;

  // Current settings with defaults
  $visionProvider  = $settings['ai_provider_vision']    ?? '';
  $chatProvider    = $settings['ai_provider_chat']      ?? '';
  $promptJa        = $settings['ai_prompt_ja']          ?? '';
  $promptEn        = $settings['ai_prompt_en']          ?? '';
  $rateLimit       = $settings['messaging_rate_limit']  ?? 10;
  $openaiKeys      = $settings['ai_openai_api_keys']    ?? [];
  $geminiKeys      = $settings['ai_gemini_api_keys']    ?? [];
  $anthropicKeys   = $settings['ai_anthropic_api_keys'] ?? [];

  // Masking helper: show only last 4 chars
  $maskKey = fn(string $key): string =>
    $key === '' ? '' : str_repeat('•', max(0, strlen($key) - 4)) . substr($key, -4);
?>

<?php if ($loadError !== null): ?>
<div class="mb-6 rounded-sm border border-error-500 bg-error-500 bg-opacity-10 px-4 py-3 text-error-500">
  <strong><?php echo $lang === 'ja' ? '設定の読み込み中にエラーが発生しました: ' : 'Error loading settings: '; ?></strong>
  <?php echo htmlspecialchars((string) $loadError, ENT_QUOTES, 'UTF-8'); ?>
  <p class="mt-2 text-sm">
    <?php echo $lang === 'ja'
      ? 'APP_KEY が変更された場合は、暗号化済みの設定値（Firebase APIキー等）を再入力してください。AIプロバイダーAPIキーは暗号化対象外のため、引き続き編集できます。'
      : 'If APP_KEY has changed, re-enter encrypted settings (e.g., Firebase API key). AI provider API keys are stored unencrypted and can still be edited below.'; ?>
  </p>
</div>
<?php endif; ?>

<?php if (!$authorized): ?>
<div class="rounded-sm border border-error-500 bg-error-500 bg-opacity-10 p-4 text-error-500">
  <?php echo $lang === 'ja' ? 'このページへのアクセス権限がありません。' : 'You do not have permission to access this page.'; ?>
</div>
<?php return; ?>
<?php endif; ?>

<?php if ($saved): ?>
<div class="mb-6 rounded-sm border border-success bg-success bg-opacity-10 px-4 py-3 text-success flex items-center gap-3">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
  <span><?php echo $lang === 'ja' ? '設定を保存しました。' : 'Settings saved successfully.'; ?></span>
</div>
<?php endif; ?>

<form method="post" action="" x-data="{
  openaiKeys: <?php echo htmlspecialchars(json_encode(array_values($openaiKeys), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>,
  geminiKeys: <?php echo htmlspecialchars(json_encode(array_values($geminiKeys), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>,
  anthropicKeys: <?php echo htmlspecialchars(json_encode(array_values($anthropicKeys), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>,
  newOpenai: '',
  newGemini: '',
  newAnthropic: '',
  addKey(list, newKey) {
    const k = newKey.trim();
    if (k) { this[list].push(k); this['new' + list.charAt(0).toUpperCase() + list.slice(1, -4)] = ''; }
  },
  removeKey(list, idx) { this[list].splice(idx, 1); }
}">
  <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">

  <!-- ===== Section 1: AI Provider ===== -->
  <div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'AIプロバイダー' : 'AI Provider'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? 'ビジョン解析と会話に使用するAIプロバイダーを選択してください。' : 'Choose the AI provider to use for vision analysis and chat.'; ?></p>
    </div>
    <div class="p-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
      <div>
        <label class="mb-2.5 block font-medium text-black dark:text-white" for="ai_provider_vision">
          <?php echo $lang === 'ja' ? 'ビジョンプロバイダー' : 'Vision Provider'; ?>
        </label>
        <select id="ai_provider_vision" name="ai_provider_vision"
          class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white">
          <option value="" <?php echo $visionProvider === '' ? 'selected' : ''; ?>><?php echo $lang === 'ja' ? 'なし' : 'None'; ?></option>
          <option value="openai"  <?php echo $visionProvider === 'openai'  ? 'selected' : ''; ?>>OpenAI</option>
          <option value="gemini"  <?php echo $visionProvider === 'gemini'  ? 'selected' : ''; ?>>Gemini</option>
          <option value="claude"  <?php echo $visionProvider === 'claude'  ? 'selected' : ''; ?>>Claude</option>
        </select>
      </div>
      <div>
        <label class="mb-2.5 block font-medium text-black dark:text-white" for="ai_provider_chat">
          <?php echo $lang === 'ja' ? 'チャットプロバイダー' : 'Chat Provider'; ?>
        </label>
        <select id="ai_provider_chat" name="ai_provider_chat"
          class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white">
          <option value="" <?php echo $chatProvider === '' ? 'selected' : ''; ?>><?php echo $lang === 'ja' ? 'なし' : 'None'; ?></option>
          <option value="openai"  <?php echo $chatProvider === 'openai'  ? 'selected' : ''; ?>>OpenAI</option>
          <option value="gemini"  <?php echo $chatProvider === 'gemini'  ? 'selected' : ''; ?>>Gemini</option>
          <option value="claude"  <?php echo $chatProvider === 'claude'  ? 'selected' : ''; ?>>Claude</option>
        </select>
      </div>
    </div>
  </div>

  <!-- ===== Section 2: API Keys ===== -->
  <div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'APIキー' : 'API Keys'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? '各プロバイダーのAPIキーを管理します。複数のキーを設定してラウンドロビンで使用できます。' : 'Manage API keys for each provider. Multiple keys can be set for round-robin use.'; ?></p>
    </div>
    <div class="p-6 space-y-8">

      <!-- OpenAI -->
      <div>
        <h4 class="mb-3 font-medium text-black dark:text-white flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-black text-white text-xs font-bold">AI</span>
          OpenAI
        </h4>
        <template x-for="(key, idx) in openaiKeys" :key="idx">
          <div class="mb-2 flex items-center gap-2">
            <input type="hidden" :name="'ai_openai_api_keys[' + idx + ']'" :value="key">
            <span class="flex-1 rounded border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-700 px-3 py-2 font-mono text-sm text-gray-600 dark:text-gray-400" x-text="key.length > 4 ? '•'.repeat(Math.max(0, key.length - 4)) + key.slice(-4) : key"></span>
            <button type="button" @click="removeKey('openaiKeys', idx)"
              class="inline-flex items-center justify-center rounded border border-gray-200 px-3 py-2 text-sm text-error-500 hover:border-error-500 hover:bg-error-500 hover:text-white transition dark:border-gray-800"
              aria-label="<?php echo $lang === 'ja' ? 'キーを削除' : 'Remove key'; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </template>
        <div class="flex gap-2 mt-2">
          <input type="text" x-model="newOpenai"
            class="flex-1 rounded border border-gray-200 bg-transparent py-2 px-3 font-mono text-sm outline-none transition focus:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="sk-..." autocomplete="off" @keydown.enter.prevent="addKey('openaiKeys', newOpenai); newOpenai = ''">
          <button type="button" @click="addKey('openaiKeys', newOpenai); newOpenai = ''"
            class="inline-flex items-center justify-center rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 transition whitespace-nowrap">
            + <?php echo $lang === 'ja' ? '追加' : 'Add'; ?>
          </button>
        </div>
      </div>

      <!-- Gemini -->
      <div>
        <h4 class="mb-3 font-medium text-black dark:text-white flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-blue-500 text-white text-xs font-bold">G</span>
          Gemini (Google)
        </h4>
        <template x-for="(key, idx) in geminiKeys" :key="idx">
          <div class="mb-2 flex items-center gap-2">
            <input type="hidden" :name="'ai_gemini_api_keys[' + idx + ']'" :value="key">
            <span class="flex-1 rounded border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-700 px-3 py-2 font-mono text-sm text-gray-600 dark:text-gray-400" x-text="key.length > 4 ? '•'.repeat(Math.max(0, key.length - 4)) + key.slice(-4) : key"></span>
            <button type="button" @click="removeKey('geminiKeys', idx)"
              class="inline-flex items-center justify-center rounded border border-gray-200 px-3 py-2 text-sm text-error-500 hover:border-error-500 hover:bg-error-500 hover:text-white transition dark:border-gray-800"
              aria-label="<?php echo $lang === 'ja' ? 'キーを削除' : 'Remove key'; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </template>
        <div class="flex gap-2 mt-2">
          <input type="text" x-model="newGemini"
            class="flex-1 rounded border border-gray-200 bg-transparent py-2 px-3 font-mono text-sm outline-none transition focus:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="AIza..." autocomplete="off" @keydown.enter.prevent="addKey('geminiKeys', newGemini); newGemini = ''">
          <button type="button" @click="addKey('geminiKeys', newGemini); newGemini = ''"
            class="inline-flex items-center justify-center rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 transition whitespace-nowrap">
            + <?php echo $lang === 'ja' ? '追加' : 'Add'; ?>
          </button>
        </div>
      </div>

      <!-- Anthropic -->
      <div>
        <h4 class="mb-3 font-medium text-black dark:text-white flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-orange-500 text-white text-xs font-bold">AN</span>
          Anthropic (Claude)
        </h4>
        <template x-for="(key, idx) in anthropicKeys" :key="idx">
          <div class="mb-2 flex items-center gap-2">
            <input type="hidden" :name="'ai_anthropic_api_keys[' + idx + ']'" :value="key">
            <span class="flex-1 rounded border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-700 px-3 py-2 font-mono text-sm text-gray-600 dark:text-gray-400" x-text="key.length > 4 ? '•'.repeat(Math.max(0, key.length - 4)) + key.slice(-4) : key"></span>
            <button type="button" @click="removeKey('anthropicKeys', idx)"
              class="inline-flex items-center justify-center rounded border border-gray-200 px-3 py-2 text-sm text-error-500 hover:border-error-500 hover:bg-error-500 hover:text-white transition dark:border-gray-800"
              aria-label="<?php echo $lang === 'ja' ? 'キーを削除' : 'Remove key'; ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </template>
        <div class="flex gap-2 mt-2">
          <input type="text" x-model="newAnthropic"
            class="flex-1 rounded border border-gray-200 bg-transparent py-2 px-3 font-mono text-sm outline-none transition focus:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="sk-ant-..." autocomplete="off" @keydown.enter.prevent="addKey('anthropicKeys', newAnthropic); newAnthropic = ''">
          <button type="button" @click="addKey('anthropicKeys', newAnthropic); newAnthropic = ''"
            class="inline-flex items-center justify-center rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 transition whitespace-nowrap">
            + <?php echo $lang === 'ja' ? '追加' : 'Add'; ?>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- ===== Section 3: Extraction Prompt ===== -->
  <div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '抽出プロンプト' : 'Extraction Prompt'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? '商品画像から情報を抽出する際にAIに送信するプロンプト。' : 'The prompt sent to AI when extracting information from product images.'; ?></p>
    </div>
    <div class="p-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div>
        <label class="mb-2.5 block font-medium text-black dark:text-white" for="ai_prompt_ja">
          <?php echo $lang === 'ja' ? '日本語プロンプト' : 'Japanese Prompt'; ?>
        </label>
        <textarea
          id="ai_prompt_ja"
          name="ai_prompt_ja"
          rows="6"
          class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white resize-y"
          placeholder="<?php echo $lang === 'ja' ? '日本語のプロンプトを入力してください...' : 'Enter Japanese prompt...'; ?>"
        ><?php echo htmlspecialchars($promptJa); ?></textarea>
      </div>
      <div>
        <label class="mb-2.5 block font-medium text-black dark:text-white" for="ai_prompt_en">
          <?php echo $lang === 'ja' ? '英語プロンプト' : 'English Prompt'; ?>
        </label>
        <textarea
          id="ai_prompt_en"
          name="ai_prompt_en"
          rows="6"
          class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white resize-y"
          placeholder="<?php echo $lang === 'ja' ? '英語のプロンプトを入力してください...' : 'Enter English prompt...'; ?>"
        ><?php echo htmlspecialchars($promptEn); ?></textarea>
      </div>
    </div>
  </div>

  <!-- ===== Section 4: Batch Processing ===== -->
  <div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'バッチ処理' : 'Batch Processing'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? 'AIバッチ処理のレート制限を設定します。' : 'Configure rate limits for AI batch processing.'; ?></p>
    </div>
    <div class="p-6">
      <div class="max-w-xs">
        <label class="mb-2.5 block font-medium text-black dark:text-white" for="messaging_rate_limit">
          <?php echo $lang === 'ja' ? 'レート制限（リクエスト/分）' : 'Rate Limit (requests/minute)'; ?>
        </label>
        <input
          type="number"
          id="messaging_rate_limit"
          name="messaging_rate_limit"
          min="1"
          max="1000"
          value="<?php echo (int) $rateLimit; ?>"
          class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
        >
        <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400">
          <?php echo $lang === 'ja' ? '1分あたりの最大AIリクエスト数。' : 'Maximum number of AI requests per minute.'; ?>
        </p>
      </div>
    </div>
  </div>

  <!-- ===== Save button ===== -->
  <div class="flex justify-end gap-3">
    <button type="submit"
      class="inline-flex items-center justify-center rounded bg-brand-500 px-8 py-3 font-medium text-white hover:bg-opacity-90 transition">
      <?php echo $lang === 'ja' ? '設定を保存' : 'Save Settings'; ?>
    </button>
  </div>

</form>

<?php }; ?>
