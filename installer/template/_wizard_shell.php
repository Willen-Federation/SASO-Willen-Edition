<?php
/*
 * Shared chrome for every wizard step. Each step template includes this
 * partial via `require __DIR__ . '/_wizard_shell.php'` before emitting
 * its own body, and exposes:
 *   - $currentStep: string (one of WizardState::STEP_* values)
 *   - $stepTitle:   string (heading shown above the form)
 *   - $stepLead:    string (lead paragraph below the heading)
 *   - $stepBody:    \Closure (renders the form / content)
 *   - $flash?:      array{type: 'success'|'error'|'info', message: string}
 */
use saso\installer\WizardState;

$steps        = WizardState::steps();
$currentIndex = 0;
foreach ($steps as $i => $s) {
    if (($s['key'] ?? '') === $currentStep) {
        $currentIndex = $i;
        break;
    }
}
?>
<div class="mx-auto w-full max-w-3xl">
  <div class="mb-6 rounded-2xl border shadow-sm overflow-hidden"
       style="background:var(--saso-card,#fff);border-color:var(--saso-card-bdr,#e5e7eb)">

    <!-- Stepper -->
    <nav aria-label="インストールステップ" class="border-b px-6 py-4"
         style="border-color:var(--saso-card-bdr,#e5e7eb);background:rgba(60,80,224,0.04)">
      <ol class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
        <?php foreach ($steps as $i => $s):
          $isDone    = $i < $currentIndex;
          $isCurrent = $i === $currentIndex;
          $color     = $isCurrent ? '#3c50e0' : ($isDone ? '#10b981' : '#9ca3af');
          $bg        = $isCurrent ? 'rgba(60,80,224,0.12)' : ($isDone ? 'rgba(16,185,129,0.12)' : 'rgba(156,163,175,0.10)');
        ?>
        <li class="flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded-full font-semibold"
                style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>">
            <?php if ($isDone): ?>
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            <?php else: ?>
              <?php echo $i + 1; ?>
            <?php endif; ?>
          </span>
          <span class="<?php echo $isCurrent ? 'font-semibold' : ''; ?>" style="color:<?php echo $color; ?>">
            <?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?>
          </span>
          <?php if ($i + 1 < count($steps)): ?>
            <span class="text-gray-300 mx-1" aria-hidden="true">›</span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </nav>

    <div class="p-6 space-y-6">
      <header>
        <h2 class="text-xl font-semibold" style="color:var(--saso-text,#111827)">
          <?php echo htmlspecialchars($stepTitle ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>
        <?php if (!empty($stepLead)): ?>
          <p class="mt-1 text-sm" style="color:var(--saso-text-sub,#6b7280)">
            <?php echo $stepLead; ?>
          </p>
        <?php endif; ?>
      </header>

      <?php if (!empty($flash)):
        $variant = $flash['type'] ?? 'info';
        $cls     = match ($variant) {
          'success' => 'border-green-200 bg-green-50 text-green-700',
          'error'   => 'border-rose-200 bg-rose-50 text-rose-700',
          default   => 'border-amber-200 bg-amber-50 text-amber-700',
        };
      ?>
        <div class="rounded-md border px-4 py-3 text-sm <?php echo $cls; ?>" role="alert">
          <?php echo $flash['message']; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($stepBody) && $stepBody instanceof \Closure) { $stepBody(); } ?>
    </div>
  </div>

  <p class="text-center text-xs" style="color:var(--saso-text-sub,#6b7280)">
    SASO Web Installer · このウィザードは <code>.env</code> と <code>system_setting</code> テーブルへ保存します。
  </p>
</div>
