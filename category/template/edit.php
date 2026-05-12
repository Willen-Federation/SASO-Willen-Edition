<?php $this->title = '分類管理'; ?>
<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
?>

<div class="card">
  <div class="card-header">
    <h2 class="font-semibold text-black dark:text-white">
      <?php echo $lang === 'ja' ? '分類管理' : 'Category Management'; ?>
    </h2>
  </div>
  <div class="card-body">
    <div id="appendingParentInputs" class="mb-4"></div>
    <button id="appendingParent" class="btn btn-sm btn-primary mb-4"
            aria-label="<?php echo ui_attr($lang === 'ja' ? '分類を追加' : 'Add category'); ?>">
      + <?php echo $lang === 'ja' ? '分類を追加' : 'Add category'; ?>
    </button>
    <div id="categoriesRoot"></div>
  </div>
</div>

<?php }; ?>
