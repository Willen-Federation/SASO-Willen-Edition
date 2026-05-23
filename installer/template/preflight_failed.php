<?php
/**
 * Standalone error page rendered when {@see \saso\installer\Preflight} fails.
 *
 * No stepper / "next" button — the wizard cannot proceed until the underlying
 * filesystem problem is fixed at the OS level. We surface the *exact* chmod /
 * chown commands the operator needs so this is a one-paste fix rather than a
 * guessing exercise.
 */
$this->title = 'インストール前提条件エラー';
$this->content = function ($v) {
    $preflight = $v->preflight;
    $failures  = $preflight !== null ? $preflight->failures() : [];
    $all       = $preflight !== null ? $preflight->checks() : [];
    $h         = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<div class="mx-auto w-full max-w-3xl">
  <div class="mb-6 rounded-2xl border shadow-sm overflow-hidden"
       style="background:var(--saso-card,#fff);border-color:var(--saso-card-bdr,#e5e7eb)">

    <div class="border-b border-rose-200 bg-rose-50 px-6 py-4">
      <h2 class="text-lg font-semibold text-rose-700">インストールを続行できません</h2>
      <p class="mt-1 text-sm text-rose-700">
        以下の前提条件が満たされていないため、<code>.env</code> ファイルへ安全に書き込めません。
        PHP が動作しているユーザーから書き込み可能な状態になるよう修正してから、本ページを再読み込みしてください。
      </p>
    </div>

    <div class="p-6 space-y-6">
      <section>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
          失敗したチェック
        </h3>
        <ul class="space-y-3">
          <?php foreach ($failures as $check): ?>
            <li class="rounded-md border border-rose-200 bg-rose-50 p-3">
              <div class="font-medium text-rose-700"><?php echo $h($check->label); ?></div>
              <div class="mt-1 text-xs text-rose-700">
                <?php echo $h($check->detail); ?>
              </div>
              <?php if (!empty($check->remedy)): ?>
                <pre class="mt-2 overflow-x-auto rounded bg-gray-900 px-3 py-2 text-xs text-gray-100"><code><?php echo $h($check->remedy); ?></code></pre>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <section>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
          全チェック一覧
        </h3>
        <ul class="divide-y" style="border-color:var(--saso-card-bdr,#e5e7eb)">
          <?php foreach ($all as $check): ?>
            <li class="flex items-start gap-3 py-2">
              <?php if ($check->ok): ?>
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
              <?php else: ?>
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">!</span>
              <?php endif; ?>
              <span class="text-sm">
                <span class="font-medium block"><?php echo $h($check->label); ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  <?php echo $h($check->detail); ?>
                </span>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <p class="text-xs text-gray-500">
        詳細な手順は <code>docs/runbooks/installer-security-step.md</code> を参照してください。
      </p>
    </div>
  </div>
</div>
<?php
};
?>
