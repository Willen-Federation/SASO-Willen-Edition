<?php $this->content = function($v) { ?>

<?php if (!$v->authorized): ?>
  <div class="ta-alert ta-alert-danger" role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div><strong>Access Denied</strong> — You must be an administrator to access this page.</div>
  </div>
<?php else: ?>

  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="./">Home</a></li>
      <li class="breadcrumb-item"><a href="./settingAdmin/start/">Settings</a></li>
      <li class="breadcrumb-item active" aria-current="page">Shelf Dimensions</li>
    </ol>
  </nav>

  <?php if ($v->message): ?>
    <div class="ta-alert ta-alert-info mb-4" x-data="{ open: true }" x-show="open" role="alert">
      <div class="flex-1"><?php echo htmlspecialchars($v->message, ENT_QUOTES, 'UTF-8'); ?></div>
      <button type="button" @click="open = false" class="ml-auto shrink-0 -mr-1 p-1 rounded hover:bg-black/10 dark:hover:bg-white/10" aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  <?php endif; ?>

  <div class="rounded-2xl border shadow-sm mb-6 overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--saso-card-bdr)">
      <h3 class="font-semibold text-base flex items-center gap-2" style="color:var(--saso-text)">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Shelf Dimension Configuration
      </h3>
      <span class="text-xs" style="color:var(--saso-text-sub)">Customize the names and descriptions of each shelf dimension</span>
    </div>
    <div class="px-6 py-5">
      <p class="text-sm mb-4" style="color:var(--saso-text-sub)">
        Define up to 10 dimensions for your shelf numbering system.
        Enable/disable dimensions as needed. The enabled dimensions will appear on the shelf number generation form.
      </p>

      <form method="post">
        <div class="overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
          <table class="ta-table" aria-label="Shelf dimension configuration">
            <thead>
              <tr>
                <th style="width: 60px;">Enable</th>
                <th style="width: 100px;">Position</th>
                <th>Name</th>
                <th>Description</th>
                <th style="width: 110px;">Type</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($v->dimensions as $dim): ?>
                <tr>
                  <td>
                    <input type="checkbox"
                           id="dimension_<?php echo (int)$dim['position']; ?>_enabled"
                           name="dimension_<?php echo (int)$dim['position']; ?>_enabled"
                           value="1"
                           <?php echo $dim['enabled'] ? 'checked' : ''; ?>
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                           aria-label="Enable dimension <?php echo (int)$dim['position']; ?>">
                  </td>
                  <td class="text-sm" style="color:var(--saso-text-sub)">
                    <?php echo (int)$dim['position']; ?>
                  </td>
                  <td>
                    <input type="text" class="form-input text-sm py-1.5"
                           name="dimension_<?php echo (int)$dim['position']; ?>_name"
                           placeholder="e.g., Section Code"
                           value="<?php echo htmlspecialchars($dim['name'], ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="100">
                  </td>
                  <td>
                    <input type="text" class="form-input text-sm py-1.5"
                           name="dimension_<?php echo (int)$dim['position']; ?>_description"
                           placeholder="Optional description"
                           value="<?php echo htmlspecialchars($dim['description'], ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="200">
                  </td>
                  <td>
                    <select class="form-input text-sm py-1.5"
                            name="dimension_<?php echo (int)$dim['position']; ?>_type">
                      <option value="numeric" <?php echo $dim['type'] === 'numeric' ? 'selected' : ''; ?>>Numeric</option>
                      <option value="letter" <?php echo $dim['type'] === 'letter' ? 'selected' : ''; ?>>Letter</option>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 rounded-xl border p-4 text-sm" style="border-color:var(--saso-card-bdr);color:var(--saso-text-sub)">
          <strong style="color:var(--saso-text)">Note:</strong>
          <ul class="mt-1 space-y-1 list-disc list-inside">
            <li><strong>Letter</strong> type: Values like A, B, C (converted to uppercase)</li>
            <li><strong>Numeric</strong> type: Values like 01, 02, 03 (zero-padded to 2 digits)</li>
            <li>At least one dimension must be enabled</li>
          </ul>
        </div>

        <div class="mt-5">
          <button type="submit" class="btn btn-primary" name="save_shelf_config" value="1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Save Configuration
          </button>
        </div>
      </form>
    </div>
  </div>

<?php endif; ?>

<?php }; ?>
