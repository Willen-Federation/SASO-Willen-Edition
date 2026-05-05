<?php $this->title = 'ラベル寸法管理'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">ラベル寸法管理</li>
</ol>

<div class="row">
  <div class="col-md-6">

    <div class="card mb-3">
      <div class="card-header">
        <h3 class="card-title">ラベル寸法一覧</h3>
      </div>
      <div class="card-body">
        <div id="labelSizeList">
          <ul class="list-unstyled mb-3">
            <?php ($v->inside)('label', 'list'); ?>
            <li>
              <label class="form-check">
                <input type="radio" class="form-check-input" name="labelName" id="newLabelSize" value="(new)">
                <span class="form-check-label">新規作成</span>
              </label>
            </li>
          </ul>
          <p>
            <button type="button" class="btn btn-link text-primary p-0" id="labelSizeDeleteDisplay">ラベル寸法削除ボタン表示</button>
          </p>
          <p id="labelSizeDeleteButton" class="d-none">
            <button type="button" id="labelSizeDelete" class="btn btn-outline-danger btn-sm">
              <i class="ti ti-trash me-1"></i>削除
            </button>
          </p>
        </div>
      </div>
    </div>

    <div id="newLabelSizeForm" class="d-none">
      <div class="card mb-3">
        <div class="card-header">
          <h3 class="card-title">ラベル寸法登録</h3>
        </div>
        <div class="card-body">
          <p class="text-secondary">下図の通り寸法を単位「mm」で入力して下さい。<br>小数以下第１位まで。用紙サイズはA4。</p>

          <form method="post" action="./label/add/">
            <div class="mb-3">
              <label for="newLabelName" class="form-label">ラベル名 <span class="text-danger">*</span></label>
              <input id="newLabelName" type="text" name="labelName" class="form-control"
                     pattern="^[0-9a-zA-Z_\-]+$" maxlength="50" required>
              <div class="form-hint">半角英数、ハイフン、アンダーバー。メーカと品番等で重複しない名前をつけて下さい。</div>
            </div>

            <?php
              $rows = [
                ['name' => 'width',          'label' => '幅',     'cls' => 'text-primary'],
                ['name' => 'height',         'label' => '高さ',   'cls' => 'text-danger'],
                ['name' => 'marginLeft',     'label' => '左余白', 'cls' => 'text-success'],
                ['name' => 'marginTop',      'label' => '上余白', 'cls' => 'text-success'],
                ['name' => 'intervalColumn', 'label' => '横間隔', 'cls' => 'text-success'],
                ['name' => 'intervalRow',    'label' => '縦間隔', 'cls' => 'text-success'],
              ];
              foreach ($rows as $r): ?>
              <div class="mb-3">
                <label for="<?php echo $r['name']; ?>" class="form-label <?php echo $r['cls']; ?>"><?php echo $r['label']; ?></label>
                <div class="input-group" style="max-width: 12em;">
                  <input id="<?php echo $r['name']; ?>" type="number" name="<?php echo $r['name']; ?>"
                         class="form-control" step="0.1" min="0" max="999.9" required>
                  <span class="input-group-text">mm</span>
                </div>
              </div>
            <?php endforeach; ?>

            <button id="newLabelSizeSubmit" type="submit" class="btn btn-primary">
              <i class="ti ti-check me-1"></i>登録
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
  <div class="col-md-6">
    <?php ($v->inside)('label', 'svg'); ?>
  </div>
</div>

<?php }; ?>
