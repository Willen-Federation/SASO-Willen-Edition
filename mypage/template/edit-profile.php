<?php
/** @var \saso\mypage\EditProfileView $v */
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$t = static fn(string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
?>
<?php $this->title = $t('プロフィール編集', 'Edit Profile'); ?>
<?php $this->content = function ($v) { ?>
<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$t = static fn(string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
?>

<div class="flex justify-center px-4 py-6">
  <div class="w-full max-w-lg">

    <?php if (!$v->member): ?>
      <div class="ta-alert ta-alert-danger" role="alert">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-.75-5.25a.75.75 0 0 0 1.5 0V10a.75.75 0 0 0-1.5 0v2.75zm0-5.5a.75.75 0 0 0 1.5 0v-.25a.75.75 0 0 0-1.5 0v.25z" clip-rule="evenodd"/>
        </svg>
        <span><?php echo ui_text($t('メンバー情報が見つかりません。', 'Member data not found.')); ?></span>
      </div>
    <?php else: ?>

    <div class="rounded-2xl border"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr);box-shadow:0 2px 12px rgba(0,0,0,0.08)">
      <div class="border-b px-6 py-4" style="border-color:var(--saso-card-bdr)">
        <h2 class="text-base font-semibold" style="color:var(--saso-text)">
          <?php echo ui_text($t('プロフィール編集', 'Edit Profile')); ?>
        </h2>
      </div>

      <div class="px-6 py-6">
        <form method="POST" action="/mypage/editProfile/" novalidate>

          <div class="mb-4">
            <label for="display_name" class="form-label">
              <?php echo ui_text($t('表示名', 'Display Name')); ?>
            </label>
            <input type="text"
                   class="form-input"
                   id="display_name"
                   name="display_name"
                   maxlength="100"
                   value="<?php echo htmlspecialchars($v->member->displayName ?: '', ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo htmlspecialchars($v->member->name, ENT_QUOTES, 'UTF-8'); ?>"
                   aria-describedby="display_name_hint">
            <p id="display_name_hint" class="form-help">
              <?php echo ui_text($t('アプリ内で表示される名前です。', 'Your name displayed in the application.')); ?>
            </p>
          </div>

          <div class="mb-4">
            <label for="bio" class="form-label">
              <?php echo ui_text($t('自己紹介', 'Bio')); ?>
            </label>
            <textarea class="form-textarea"
                      id="bio"
                      name="bio"
                      rows="4"
                      maxlength="500"
                      aria-describedby="bio_hint"><?php echo htmlspecialchars($v->member->bio ?: '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <p id="bio_hint" class="form-help">
              <?php echo ui_text($t('自己紹介文（最大500文字）', 'About yourself (max 500 characters)')); ?>
            </p>
          </div>

          <div class="mb-6">
            <label for="avatar_url" class="form-label">
              <?php echo ui_text($t('アバター URL', 'Avatar URL')); ?>
            </label>
            <input type="url"
                   class="form-input"
                   id="avatar_url"
                   name="avatar_url"
                   maxlength="500"
                   value="<?php echo htmlspecialchars($v->member->avatarUrl ?: '', ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="https://example.com/avatar.jpg"
                   aria-describedby="avatar_url_hint">
            <p id="avatar_url_hint" class="form-help">
              <?php echo ui_text($t('アバター画像のURL（JPG, PNG, WebP）', 'URL to your avatar image (JPG, PNG, WebP)')); ?>
            </p>
          </div>

          <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">
              <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17 21v-8H7v8M7 3v5h8"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span><?php echo ui_text($t('保存', 'Save')); ?></span>
            </button>
            <a href="./mypage/start/" class="btn btn-secondary">
              <?php echo ui_text($t('キャンセル', 'Cancel')); ?>
            </a>
          </div>
        </form>
      </div>
    </div>

    <?php endif; ?>
  </div>
</div>
<?php }; ?>
