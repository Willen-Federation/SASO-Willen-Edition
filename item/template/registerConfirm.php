<?php $this->title = '商品登録確認'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">商品登録</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table" aria-label="商品登録確認">
      <thead><tr>
        <th scope="col">商品番号</th><th scope="col">商品名</th><th scope="col">分類</th><th scope="col">価格</th>
        <th scope="col">プラ</th><th scope="col">役割名</th><th scope="col">紙</th><th scope="col">役割名</th>
        <th scope="col">登録日</th><th scope="col">更新日</th><th scope="col">色</th><th scope="col">サイズ</th>
      </tr></thead>
      <tbody><tr>
        <td>-</td>
        <td><?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="categoryPath categoryPathChangable"><?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo number_format($v->itemVar->price??0); ?></td>
        <td><?php if($v->item->pla){ ?><img src="./img/pla.gif" alt="プラ" width="25" height="25"><?php } ?></td>
        <td><?php echo htmlspecialchars($v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php if($v->item->paper){ ?><img src="./img/kami.gif" alt="紙" width="25" height="25"><?php } ?></td>
        <td><?php echo htmlspecialchars($v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>-</td><td>-</td>
        <td><?php echo htmlspecialchars($v->serializedColors, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($v->serializedSizes, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr></tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="./item/add/">
      <input type="hidden" name="itemNameConfirm" value="<?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="categoryIdConfirm" value="<?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="priceConfirm" value="<?php echo htmlspecialchars((string)$v->itemVar->price, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="colorNameConfirm" value="<?php echo htmlspecialchars($v->validFeaturesAmount?$v->inputColors:'', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="sizeNameConfirm" value="<?php echo htmlspecialchars($v->validFeaturesAmount?$v->inputSizes:'', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="plaConfirm" value="<?php echo $v->item->pla?'1':''; ?>">
      <input type="hidden" name="plaNoteConfirm" value="<?php echo htmlspecialchars($v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="paperConfirm" value="<?php echo $v->item->paper?'1':''; ?>">
      <input type="hidden" name="paperNoteConfirm" value="<?php echo htmlspecialchars($v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?>">

      <button type="submit" class="btn btn-primary mb-3"><i class="bi bi-plus me-1"></i>登録</button>

      <?php if(!$v->validFeaturesAmount) { ?>
        <div class="alert alert-warning" role="note">
          色の数とサイズの数をかけて100を超えてはいけません。<br>
          色数 × サイズ数 ≦ 100
        </div>
      <?php } ?>

      <hr class="my-3">
      <p class="text-secondary">入力をやり直す場合は以下を編集して再度「登録」を押してください。</p>

      <div class="mb-3">
        <label for="confirmReedit-itemName" class="form-label">商品名 <span class="text-danger">*</span></label>
        <input type="text" id="confirmReedit-itemName" name="itemName" class="form-control"
               size="50" maxlength="50" required value="<?php echo htmlspecialchars($v->item->name, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-hint">50字以内</div>
      </div>

      <div class="mb-3">
        <label class="form-label">分類</label>
        <div id="category" class="border rounded p-3 bg-light">
          <div id="appendingParentInputs"></div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="appendingParent">+</button>
          <div id="categoriesRoot"></div>
          <p class="mt-2 mb-0">選択中の分類：<span class="categoryPath categoryPathChangable"><?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?></span>
            <button type="button" class="btn btn-sm btn-outline-secondary d-none ms-2" id="deselectCategory">選択解除</button>
          </p>
        </div>
      </div>
      <input type="hidden" name="categoryId" id="categoryId" value="<?php echo htmlspecialchars((string)$v->itemVar->categoryId, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="mb-3">
        <label for="confirmReedit-price" class="form-label">価格</label>
        <div class="input-group" style="max-width: 22em;">
          <input type="text" id="confirmReedit-price" name="price" class="form-control"
                 pattern="^[0-9,]+$" maxlength="11"
                 value="<?php echo htmlspecialchars((string)$v->itemVar->price, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="input-group-text">円</span>
        </div>
        <div class="form-hint">9桁までの数</div>
      </div>

      <div class="mb-3">
        <label for="confirmReedit-color" class="form-label">色 <span class="text-danger">*</span></label>
        <input type="text" id="confirmReedit-color" name="colorName" class="form-control"
               required value="<?php echo htmlspecialchars($v->inputColors, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-hint">複数入力する場合は半角カンマ ( , ) で区切って下さい。</div>
      </div>

      <div class="mb-3">
        <label for="confirmReedit-size" class="form-label">サイズ <span class="text-danger">*</span></label>
        <input type="text" id="confirmReedit-size" name="sizeName" class="form-control"
               required value="<?php echo htmlspecialchars($v->inputSizes, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-hint">複数入力する場合は半角カンマ ( , ) で区切って下さい。</div>
      </div>

      <fieldset class="mb-3">
        <legend class="form-label">梱包</legend>
        <div class="row g-2 align-items-center mb-2">
          <div class="col-auto">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="pla" value="1" <?php if($v->item->pla){echo 'checked';} ?>>
              <span class="form-check-label">プラ</span>
            </label>
          </div>
          <div class="col">
            <input type="text" name="plaNote" class="form-control" maxlength="50" placeholder="メモ" value="<?php echo htmlspecialchars($v->item->plaNote, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>
        <div class="row g-2 align-items-center">
          <div class="col-auto">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="paper" value="1" <?php if($v->item->paper){echo 'checked';} ?>>
              <span class="form-check-label">紙</span>
            </label>
          </div>
          <div class="col">
            <input type="text" name="paperNote" class="form-control" maxlength="50" placeholder="メモ" value="<?php echo htmlspecialchars($v->item->paperNote, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>
      </fieldset>

      <button type="submit" class="btn btn-primary"><i class="bi bi-plus me-1"></i>登録</button>
    </form>
  </div>
</div>

<?php }; ?>
