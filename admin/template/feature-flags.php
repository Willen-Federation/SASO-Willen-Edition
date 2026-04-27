<?php $this->title = 'フィーチャーフラグ'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
  $flags = $v->flags ?? [];
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'フィーチャーフラグ' : 'Feature Flags'; ?></li>
  </ol>
</nav>

<div class="mb-6 alert" style="border-color:#e2e8f0;background:#f8fafc;">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
  <span class="text-sm text-body dark:text-bodydark"><?php echo $lang === 'ja' ? 'フィーチャーフラグを使用すると、機能のON/OFFをコードの変更なしに制御できます。モバイルアプリ連携や外部サービス連携の切り替えにも使用されます。' : 'Feature flags allow you to control feature availability without code changes. Also used for mobile and external service integration toggles.'; ?></span>
</div>

<div
  x-data="{
    showAdd: false,
    search: '',
    filterEnabled: 'all'
  }"
>
  <!-- Toolbar -->
  <div class="mb-4 flex flex-wrap gap-3 items-center justify-between">
    <div class="flex gap-3">
      <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 h-5 w-5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input x-model="search" type="search" class="form-input pl-11 py-2.5 w-64" placeholder="<?php echo $lang === 'ja' ? 'キーで検索...' : 'Search by key...'; ?>">
      </div>
      <div class="flex rounded border border-stroke dark:border-strokedark overflow-hidden" role="group">
        <?php
        $fFilters = [['all','すべて','All'],['enabled','有効','Enabled'],['disabled','無効','Disabled']];
        foreach($fFilters as $ff): ?>
        <button type="button" @click="filterEnabled = '<?php echo $ff[0]; ?>'"
          class="px-3 py-2 text-sm transition"
          :class="filterEnabled === '<?php echo $ff[0]; ?>' ? 'bg-primary text-white' : 'bg-white dark:bg-boxdark text-body dark:text-bodydark hover:bg-gray-2 dark:hover:bg-meta-4'"
          :aria-pressed="(filterEnabled === '<?php echo $ff[0]; ?>').toString()">
          <?php echo $lang === 'ja' ? $ff[1] : $ff[2]; ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <button @click="showAdd = true" class="btn-primary px-6">
      + <?php echo $lang === 'ja' ? '新規フラグを追加' : 'Add Flag'; ?>
    </button>
  </div>

  <!-- Flags table -->
  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="data-table" aria-label="<?php echo $lang === 'ja' ? 'フィーチャーフラグ一覧' : 'Feature Flags'; ?>">
        <thead>
          <tr>
            <th class="pl-9"><?php echo $lang === 'ja' ? 'キー' : 'Key'; ?></th>
            <th><?php echo $lang === 'ja' ? '説明' : 'Description'; ?></th>
            <th><?php echo $lang === 'ja' ? '有効/無効' : 'Enabled'; ?></th>
            <th><?php echo $lang === 'ja' ? '最終更新' : 'Updated'; ?></th>
            <th><?php echo $lang === 'ja' ? '操作' : 'Actions'; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($flags)): ?>
          <tr>
            <td colspan="5" class="py-16 text-center">
              <div class="flex flex-col items-center gap-3 text-body dark:text-bodydark">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-stroke" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                <p><?php echo $lang === 'ja' ? 'フィーチャーフラグが設定されていません' : 'No feature flags configured'; ?></p>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach($flags as $flag): ?>
          <tr
            x-show="
              (filterEnabled === 'all' ||
               (filterEnabled === 'enabled' && <?php echo $flag->isEnabled() ? 'true' : 'false'; ?>) ||
               (filterEnabled === 'disabled' && <?php echo !$flag->isEnabled() ? 'true' : 'false'; ?>)) &&
              (!search || '<?php echo addslashes($flag->getKey()->getValue()); ?>'.toLowerCase().includes(search.toLowerCase()))
            "
          >
            <td class="pl-9">
              <code class="text-sm font-mono bg-gray-2 dark:bg-meta-4 px-2 py-0.5 rounded">
                <?php echo htmlspecialchars($flag->getKey()->getValue()); ?>
              </code>
            </td>
            <td class="text-sm text-body dark:text-bodydark">
              <?php echo htmlspecialchars($flag->getDescription() ?? '—'); ?>
            </td>
            <td>
              <form method="post" action="./api/v1/feature-flags/<?php echo htmlspecialchars($flag->getKey()->getValue()); ?>" class="inline">
                <input type="hidden" name="_method" value="PATCH">
                <label class="toggle" aria-label="<?php echo $lang === 'ja' ? 'フラグを切り替え' : 'Toggle flag'; ?>">
                  <input type="checkbox" name="enabled" value="1"
                    <?php echo $flag->isEnabled() ? 'checked' : ''; ?>
                    onchange="this.form.submit()">
                  <span class="toggle-slider"></span>
                </label>
              </form>
            </td>
            <td class="text-sm text-body dark:text-bodydark">
              <?php echo $flag->getUpdatedAt() ? htmlspecialchars($flag->getUpdatedAt()->format('Y-m-d H:i')) : '—'; ?>
            </td>
            <td>
              <div class="flex gap-2">
                <a href="./admin/feature-flags/edit/<?php echo htmlspecialchars($flag->getKey()->getValue()); ?>/"
                   class="text-sm text-primary hover:underline"><?php echo $lang === 'ja' ? '編集' : 'Edit'; ?></a>
                <form method="post" action="./admin/feature-flags/delete/<?php echo htmlspecialchars($flag->getKey()->getValue()); ?>/"
                  onsubmit="return confirm('<?php echo $lang === 'ja' ? '削除してよろしいですか？' : 'Are you sure?'; ?>')">
                  <button type="submit" class="text-sm text-danger hover:underline"><?php echo $lang === 'ja' ? '削除' : 'Delete'; ?></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add modal -->
  <div
    x-show="showAdd"
    x-transition
    class="fixed inset-0 z-99999 flex items-center justify-center bg-black bg-opacity-50"
    role="dialog"
    aria-modal="true"
    aria-label="<?php echo $lang === 'ja' ? 'フラグ追加' : 'Add Flag'; ?>"
  >
    <div class="w-full max-w-md rounded-sm bg-white dark:bg-boxdark shadow-default mx-4" @click.away="showAdd = false">
      <div class="flex items-center justify-between border-b border-stroke dark:border-strokedark px-6 py-4">
        <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '新規フィーチャーフラグ' : 'New Feature Flag'; ?></h3>
        <button @click="showAdd = false" class="text-body hover:text-black dark:hover:text-white" aria-label="閉じる">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form method="post" action="./api/v1/feature-flags" class="p-6 space-y-4">
        <div>
          <label class="form-label"><?php echo $lang === 'ja' ? 'キー' : 'Key'; ?> <span class="text-danger">*</span></label>
          <input type="text" name="key" class="form-input" required aria-required="true"
            pattern="^[a-z0-9_\-\.]+$"
            placeholder="<?php echo $lang === 'ja' ? '例: feature.mobile_connect' : 'e.g. feature.mobile_connect'; ?>">
          <p class="mt-1 text-xs text-body"><?php echo $lang === 'ja' ? '小文字英数字・アンダースコア・ハイフン・ドットのみ' : 'Lowercase letters, numbers, underscores, hyphens, dots only'; ?></p>
        </div>
        <div>
          <label class="form-label"><?php echo $lang === 'ja' ? '説明' : 'Description'; ?></label>
          <textarea name="description" class="form-input" rows="2" placeholder="<?php echo $lang === 'ja' ? 'この機能フラグの用途を説明してください' : 'Describe this feature flag'; ?>"></textarea>
        </div>
        <div class="flex items-center gap-3">
          <label class="toggle" aria-label="<?php echo $lang === 'ja' ? '初期状態' : 'Initial state'; ?>">
            <input type="checkbox" name="enabled" value="1">
            <span class="toggle-slider"></span>
          </label>
          <span class="text-sm text-body dark:text-bodydark"><?php echo $lang === 'ja' ? '作成時に有効にする' : 'Enable on creation'; ?></span>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="btn-primary flex-1"><?php echo $lang === 'ja' ? '作成する' : 'Create'; ?></button>
          <button type="button" @click="showAdd = false" class="btn-secondary flex-1"><?php echo $lang === 'ja' ? 'キャンセル' : 'Cancel'; ?></button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php }; ?>
