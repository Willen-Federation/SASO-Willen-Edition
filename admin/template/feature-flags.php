<?php $this->title = 'フィーチャーフラグ'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
  $flags = $v->flags ?? [];
  $csrf = \saso\util\CSRFtoken::current();
?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
  <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'フィーチャーフラグ' : 'Feature Flags'; ?></li>
</ol>

<div class="alert alert-info mb-3" role="note">
  <i class="ti ti-info-circle me-2"></i>
  <?php echo $lang === 'ja'
    ? 'フィーチャーフラグを使用すると、機能のON/OFFをコードの変更なしに制御できます。モバイルアプリ連携や外部サービス連携の切り替えにも使用されます。'
    : 'Feature flags allow you to control feature availability without code changes. Also used for mobile and external service integration toggles.'; ?>
</div>

<div x-data="{ search: '', filterEnabled: 'all', showAdd: false }">
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label for="ff-search" class="form-label"><?php echo $lang === 'ja' ? 'キーで検索' : 'Search by key'; ?></label>
          <div class="input-group">
            <span class="input-group-text"><i class="ti ti-search"></i></span>
            <input id="ff-search" type="search" x-model="search" class="form-control" placeholder="<?php echo $lang === 'ja' ? 'キーで検索...' : 'Search by key...'; ?>">
          </div>
        </div>
        <div class="col-md-auto">
          <label class="form-label"><?php echo $lang === 'ja' ? 'フィルタ' : 'Filter'; ?></label>
          <div class="btn-group" role="group">
            <?php foreach ([['all','すべて','All'],['enabled','有効','Enabled'],['disabled','無効','Disabled']] as $ff): ?>
              <button type="button" @click="filterEnabled = '<?php echo $ff[0]; ?>'"
                      class="btn"
                      :class="filterEnabled === '<?php echo $ff[0]; ?>' ? 'btn-primary' : 'btn-outline-secondary'"
                      :aria-pressed="(filterEnabled === '<?php echo $ff[0]; ?>').toString()">
                <?php echo $lang === 'ja' ? $ff[1] : $ff[2]; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col text-end">
          <button @click="showAdd = true" type="button" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i><?php echo $lang === 'ja' ? '新規フラグを追加' : 'Add Flag'; ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-striped table-vcenter card-table" aria-label="<?php echo $lang === 'ja' ? 'フィーチャーフラグ一覧' : 'Feature Flags'; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $lang === 'ja' ? 'キー' : 'Key'; ?></th>
            <th scope="col"><?php echo $lang === 'ja' ? '説明' : 'Description'; ?></th>
            <th scope="col" class="text-center"><?php echo $lang === 'ja' ? '有効/無効' : 'Enabled'; ?></th>
            <th scope="col"><?php echo $lang === 'ja' ? '最終更新' : 'Updated'; ?></th>
            <th scope="col" class="text-end"><?php echo $lang === 'ja' ? '操作' : 'Actions'; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($flags)): ?>
            <tr><td colspan="5" class="text-center text-secondary py-5">
              <i class="ti ti-flag-off" style="font-size: 2rem;"></i>
              <p class="mt-2 mb-0"><?php echo $lang === 'ja' ? 'フィーチャーフラグが設定されていません' : 'No feature flags configured'; ?></p>
            </td></tr>
          <?php else: foreach($flags as $flag): ?>
            <tr x-show="
                  (filterEnabled === 'all' ||
                   (filterEnabled === 'enabled' && <?php echo $flag->enabled ? 'true' : 'false'; ?>) ||
                   (filterEnabled === 'disabled' && <?php echo !$flag->enabled ? 'true' : 'false'; ?>)) &&
                  (!search || <?php echo htmlspecialchars(json_encode((string)$flag->key->value), ENT_QUOTES, 'UTF-8'); ?>.toLowerCase().includes(search.toLowerCase()))">
              <td><code class="font-monospace"><?php echo htmlspecialchars($flag->key->value, ENT_QUOTES, 'UTF-8'); ?></code></td>
              <td class="text-secondary"><?php echo htmlspecialchars($flag->description ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="text-center">
                <form method="post" action="./admin/feature-flags/?toggle=<?php echo htmlspecialchars($flag->key->value, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                  <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <label class="form-check form-switch m-0 d-inline-block" aria-label="<?php echo $lang === 'ja' ? 'フラグを切り替え' : 'Toggle flag'; ?>">
                    <input type="checkbox" class="form-check-input" name="enabled" value="1"
                           <?php echo $flag->enabled ? 'checked' : ''; ?>
                           onchange="this.form.submit()">
                  </label>
                </form>
              </td>
              <td class="text-secondary"><?php echo htmlspecialchars($flag->updatedAt->format('Y-m-d H:i')); ?></td>
              <td class="text-end">
                <form method="post" action="./admin/feature-flags/?delete=<?php echo htmlspecialchars($flag->key->value, ENT_QUOTES, 'UTF-8'); ?>"
                      class="d-inline m-0"
                      onsubmit="return confirm('<?php echo $lang === 'ja' ? '削除してよろしいですか？' : 'Are you sure?'; ?>')">
                  <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash me-1"></i><?php echo $lang === 'ja' ? '削除' : 'Delete'; ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal modal-blur fade" :class="showAdd ? 'show d-block' : ''" x-show="showAdd" tabindex="-1" role="dialog" aria-modal="true" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered" role="document" @click.away="showAdd = false">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo $lang === 'ja' ? '新規フィーチャーフラグ' : 'New Feature Flag'; ?></h5>
          <button type="button" class="btn-close" @click="showAdd = false" aria-label="Close"></button>
        </div>
        <form method="post" action="./admin/feature-flags/">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="modal-body">
            <div class="mb-3">
              <label for="ff-key" class="form-label"><?php echo $lang === 'ja' ? 'キー' : 'Key'; ?> <span class="text-danger">*</span></label>
              <input id="ff-key" type="text" name="key" class="form-control" required
                     pattern="^[a-z0-9_.]+$"
                     placeholder="<?php echo $lang === 'ja' ? '例: feature.mobile_connect' : 'e.g. feature.mobile_connect'; ?>">
              <div class="form-hint"><?php echo $lang === 'ja' ? '小文字英数字・アンダースコア・ドットのみ（ハイフン不可）' : 'Lowercase letters, numbers, underscores, dots only'; ?></div>
            </div>
            <div class="mb-3">
              <label for="ff-desc" class="form-label"><?php echo $lang === 'ja' ? '説明' : 'Description'; ?></label>
              <textarea id="ff-desc" name="description" class="form-control" rows="2" placeholder="<?php echo $lang === 'ja' ? 'この機能フラグの用途を説明してください' : 'Describe this feature flag'; ?>"></textarea>
            </div>
            <label class="form-check form-switch">
              <input type="checkbox" name="enabled" value="1" class="form-check-input">
              <span class="form-check-label"><?php echo $lang === 'ja' ? '作成時に有効にする' : 'Enable on creation'; ?></span>
            </label>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-link link-secondary" @click="showAdd = false"><?php echo $lang === 'ja' ? 'キャンセル' : 'Cancel'; ?></button>
            <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i><?php echo $lang === 'ja' ? '作成する' : 'Create'; ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
