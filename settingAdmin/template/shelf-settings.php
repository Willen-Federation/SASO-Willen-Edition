<?php $this->content = function($v) { ?>

<?php if (!$v->authorized): ?>
  <div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Access Denied</strong> — You must be an administrator to access this page.
  </div>
<?php else: ?>

  <ol class="breadcrumb mb-3" aria-label="breadcrumbs">
    <li class="breadcrumb-item"><a href="./">Home</a></li>
    <li class="breadcrumb-item"><a href="./settingAdmin/start/">Settings</a></li>
    <li class="breadcrumb-item active" aria-current="page">Shelf Dimensions</li>
  </ol>

  <?php if ($v->message): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($v->message, ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="bi bi-gear me-1"></i>Shelf Dimension Configuration</h3>
      <div class="card-options text-secondary small">Customize the names and descriptions of each shelf dimension</div>
    </div>
    <div class="card-body">
      <p class="text-secondary mb-4">
        Define up to 10 dimensions for your shelf numbering system.
        Enable/disable dimensions as needed. The enabled dimensions will appear on the shelf number generation form.
      </p>

      <form method="post" class="needs-validation">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">Enable</th>
                <th style="width: 100px;">Position</th>
                <th>Name</th>
                <th>Description</th>
                <th style="width: 100px;">Type</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($v->dimensions as $dim): ?>
                <tr>
                  <td>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox"
                             id="dimension_<?php echo $dim['position']; ?>_enabled"
                             name="dimension_<?php echo $dim['position']; ?>_enabled"
                             value="1"
                             <?php echo $dim['enabled'] ? 'checked' : ''; ?>>
                    </div>
                  </td>
                  <td class="text-secondary">
                    <?php echo (int)$dim['position']; ?>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm"
                           name="dimension_<?php echo $dim['position']; ?>_name"
                           placeholder="e.g., Section Code"
                           value="<?php echo htmlspecialchars($dim['name'], ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="100">
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm"
                           name="dimension_<?php echo $dim['position']; ?>_description"
                           placeholder="Optional description"
                           value="<?php echo htmlspecialchars($dim['description'], ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="200">
                  </td>
                  <td>
                    <select class="form-select form-select-sm"
                            name="dimension_<?php echo $dim['position']; ?>_type">
                      <option value="numeric" <?php echo $dim['type'] === 'numeric' ? 'selected' : ''; ?>>Numeric</option>
                      <option value="letter" <?php echo $dim['type'] === 'letter' ? 'selected' : ''; ?>>Letter</option>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="alert alert-light mt-3" role="note">
          <strong>Note:</strong>
          <ul class="mb-0 ps-3">
            <li><strong>Letter</strong> type: Values like A, B, C (converted to uppercase)</li>
            <li><strong>Numeric</strong> type: Values like 01, 02, 03 (zero-padded to 2 digits)</li>
            <li>At least one dimension must be enabled</li>
          </ul>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary" name="save_shelf_config" value="1">
            <i class="bi bi-check-circle me-1"></i>Save Configuration
          </button>
        </div>
      </form>
    </div>
  </div>

<?php endif; ?>

<?php }; ?>
