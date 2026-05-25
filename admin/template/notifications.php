<?php $this->title = 'プッシュ通知'; ?>
<?php $this->content = function ($v) {
  $lang          = $_SESSION['lang'] ?? 'ja';
  $authorized    = $v->authorized    ?? false;
  $sent          = $v->sent          ?? false;
  $loadError     = $v->loadError     ?? null;
  $sendError     = $v->sendError     ?? null;
  $fcmConfigured = $v->fcmConfigured ?? false;
  $history       = $v->history       ?? [];

  $h   = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  $ja  = fn (string $j, string $e): string => $lang === 'ja' ? $j : $e;
?>

<?php if ($loadError !== null): ?>
<div class="mb-6 rounded-sm border border-error-500 bg-error-500 bg-opacity-10 px-4 py-3 text-error-500">
  <strong><?php echo $ja('エラー: ', 'Error: '); ?></strong><?php echo $h((string) $loadError); ?>
</div>
<?php endif; ?>

<?php if (!$authorized): ?>
<div class="rounded-sm border border-error-500 bg-error-500 bg-opacity-10 p-4 text-error-500">
  <?php echo $ja('このページへのアクセス権限がありません。', 'You do not have permission to access this page.'); ?>
</div>
<?php return; ?>
<?php endif; ?>

<?php if ($sent): ?>
<div class="mb-6 rounded-sm border border-success bg-success bg-opacity-10 px-4 py-3 text-success flex items-center gap-3">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
  <span><?php echo $ja('通知を送信しました。', 'Notification sent successfully.'); ?></span>
</div>
<?php endif; ?>

<?php if ($sendError !== null): ?>
<div class="mb-6 rounded-sm border border-error-500 bg-error-500 bg-opacity-10 px-4 py-3 text-error-500">
  <strong><?php echo $ja('送信エラー: ', 'Send error: '); ?></strong><?php echo $h((string) $sendError); ?>
</div>
<?php endif; ?>

<?php if (!$fcmConfigured): ?>
<div class="mb-6 rounded-sm border border-amber-300 bg-amber-50 px-4 py-3 text-amber-700 dark:border-amber-600 dark:bg-amber-900 dark:bg-opacity-20 dark:text-amber-400">
  <div class="flex items-start gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div>
      <p class="font-medium"><?php echo $ja('Firebase FCM が未設定です', 'Firebase FCM is not configured'); ?></p>
      <p class="mt-1 text-sm">
        <?php echo $ja(
          'プッシュ通知を送信するには、ENV設定でFirebaseのサービスアカウントキーとプロジェクトIDを設定してください。',
          'To send push notifications, configure the Firebase service account key and project ID in ENV settings.'
        ); ?>
        <a href="./admin/env-settings/" class="underline ml-1"><?php echo $ja('ENV設定へ', 'Go to ENV settings'); ?> →</a>
      </p>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ===== Compose ===== -->
<section class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
  <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
    <h3 class="font-semibold text-black dark:text-white"><?php echo $ja('通知を送信', 'Send Notification'); ?></h3>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
      <?php echo $ja('Firebase Cloud Messaging (FCM) 経由でモバイルアプリにプッシュ通知を送信します。', 'Send a push notification to mobile apps via Firebase Cloud Messaging (FCM).'); ?>
    </p>
  </header>

  <form method="post" action="" x-data="notifForm()" class="p-6 space-y-5">
    <input type="hidden" name="csrftoken" value="<?php echo $h(\saso\util\CSRFtoken::current()); ?>">

    <!-- Title -->
    <div>
      <label class="mb-1.5 block text-sm font-medium text-black dark:text-white" for="notification_title">
        <?php echo $ja('タイトル', 'Title'); ?> <span class="text-error-500">*</span>
      </label>
      <input id="notification_title" name="notification_title" type="text"
             class="form-input w-full" maxlength="255" required
             placeholder="<?php echo $ja('通知のタイトル', 'Notification title'); ?>">
    </div>

    <!-- Body -->
    <div>
      <label class="mb-1.5 block text-sm font-medium text-black dark:text-white" for="notification_body">
        <?php echo $ja('本文', 'Body'); ?> <span class="text-error-500">*</span>
      </label>
      <textarea id="notification_body" name="notification_body" rows="3"
                class="form-input w-full resize-none" required
                placeholder="<?php echo $ja('通知の本文を入力してください', 'Enter notification body'); ?>"></textarea>
    </div>

    <!-- Image URL (optional) -->
    <div>
      <label class="mb-1.5 block text-sm font-medium text-black dark:text-white" for="image_url">
        <?php echo $ja('画像URL（任意）', 'Image URL (optional)'); ?>
      </label>
      <input id="image_url" name="image_url" type="url"
             class="form-input w-full"
             placeholder="https://example.com/image.png">
    </div>

    <!-- Target type -->
    <div>
      <p class="mb-2 text-sm font-medium text-black dark:text-white"><?php echo $ja('送信先', 'Target'); ?> <span class="text-error-500">*</span></p>
      <div class="flex flex-wrap gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="target_type" value="topic" x-model="targetType"
                 class="h-4 w-4 text-brand-500" checked>
          <span class="text-sm"><?php echo $ja('トピック', 'Topic'); ?></span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="target_type" value="token" x-model="targetType"
                 class="h-4 w-4 text-brand-500">
          <span class="text-sm"><?php echo $ja('デバイストークン', 'Device Token'); ?></span>
        </label>
      </div>
    </div>

    <!-- Topic shortcut buttons (shown when target_type = topic) -->
    <div x-show="targetType === 'topic'" x-cloak>
      <label class="mb-1.5 block text-sm font-medium text-black dark:text-white" for="target">
        <?php echo $ja('トピック名', 'Topic name'); ?>
      </label>
      <div class="flex gap-2 flex-wrap mb-2">
        <button type="button" @click="setTopic('all')"
                class="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700">
          <?php echo $ja('全ユーザー (all)', 'All users (all)'); ?>
        </button>
        <button type="button" @click="setTopic('flutter_app')"
                class="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700">
          Flutter App
        </button>
        <button type="button" @click="setTopic('saso_alerts')"
                class="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700">
          <?php echo $ja('在庫アラート (saso_alerts)', 'Stock alerts (saso_alerts)'); ?>
        </button>
      </div>
      <input id="target" name="target" type="text"
             class="form-input w-full" x-model="target"
             placeholder="all" required>
      <p class="mt-1 text-xs text-gray-500"><?php echo $ja('FCMトピック名。端末側でこのトピックをサブスクライブしている必要があります。', 'FCM topic name. Devices must subscribe to this topic.'); ?></p>
    </div>

    <!-- Device token (shown when target_type = token) -->
    <div x-show="targetType === 'token'" x-cloak>
      <label class="mb-1.5 block text-sm font-medium text-black dark:text-white" for="target_token">
        <?php echo $ja('デバイストークン', 'Device Token'); ?>
      </label>
      <textarea id="target_token" name="target" rows="3"
                class="form-input w-full font-mono text-xs resize-none"
                x-bind:required="targetType === 'token'"
                placeholder="<?php echo $ja('FCMデバイストークンを貼り付けてください', 'Paste FCM device token here'); ?>"></textarea>
    </div>

    <div class="flex justify-end">
      <button type="submit"
              <?php echo !$fcmConfigured ? 'disabled' : ''; ?>
              class="inline-flex items-center gap-2 rounded bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
              style="<?php echo $fcmConfigured ? 'background:#3c50e0' : ''; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <?php echo $ja('通知を送信', 'Send Notification'); ?>
      </button>
    </div>
  </form>
</section>

<!-- ===== Send history ===== -->
<section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
  <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex items-center justify-between">
    <div>
      <h3 class="font-semibold text-black dark:text-white"><?php echo $ja('送信履歴', 'Send History'); ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $ja('直近50件', 'Last 50 sends'); ?></p>
    </div>
  </header>

  <?php if (empty($history)): ?>
  <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
    <?php echo $ja('送信履歴はありません。', 'No notification history yet.'); ?>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b border-gray-200 dark:border-gray-800">
          <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap"><?php echo $ja('送信日時', 'Sent at'); ?></th>
          <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400"><?php echo $ja('タイトル', 'Title'); ?></th>
          <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400"><?php echo $ja('送信先', 'Target'); ?></th>
          <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400"><?php echo $ja('送信者', 'Sent by'); ?></th>
          <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400"><?php echo $ja('結果', 'Result'); ?></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php foreach ($history as $row): ?>
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
          <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
            <?php echo $h((string) $row['sent_at']); ?>
          </td>
          <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs">
            <div class="font-medium"><?php echo $h((string) $row['title']); ?></div>
            <div class="text-xs text-gray-500 mt-0.5 truncate"><?php echo $h((string) $row['body']); ?></div>
          </td>
          <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
            <span class="inline-block rounded px-1.5 py-0.5 text-xs font-mono bg-gray-100 dark:bg-gray-700">
              <?php echo $h((string) $row['target_type']); ?>
            </span>
            <span class="ml-1 font-mono text-xs truncate max-w-[12rem] inline-block align-bottom">
              <?php echo $h((string) $row['target']); ?>
            </span>
          </td>
          <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap text-xs">
            <?php echo $h((string) $row['sent_by']); ?>
          </td>
          <td class="px-4 py-3">
            <?php if ((int) $row['success'] === 1): ?>
            <span class="inline-flex items-center gap-1 rounded-full bg-success bg-opacity-10 px-2 py-0.5 text-xs font-medium text-success">
              <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <?php echo $ja('成功', 'OK'); ?>
            </span>
            <?php else: ?>
            <span class="inline-flex items-start gap-1 rounded-full bg-error-500 bg-opacity-10 px-2 py-0.5 text-xs font-medium text-error-500" title="<?php echo $h((string) ($row['error_message'] ?? '')); ?>">
              <svg class="h-3 w-3 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
              <?php echo $ja('失敗', 'Failed'); ?>
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<script>
function notifForm() {
  return {
    targetType: 'topic',
    target: 'all',
    setTopic(name) {
      this.targetType = 'topic';
      this.target = name;
    },
  };
}
</script>
<?php }; ?>
