<?php $this->title = '商品登録確認'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">商品登録</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table">
      <thead><tr>
        <th>商品番号</th><th>商品名</th><th>分類</th><th>価格</th>
        <th>プラ</th><th>役割名</th><th>紙</th><th>役割名</th>
        <th>登録日</th><th>更新日</th><th>色</th><th>サイズ</th>
      </tr></thead>
      <tbody><tr>
        <td>-</td>
        <td><?php echo $v->item->name; ?></td>
        <td class="categoryPath categoryPathChangable"><?php echo $v->itemVar->categoryId; ?></td>
        <td><?php echo number_format($v->itemVar->price??0); ?></td>
        <td><?php if($v->item->pla){ ?><img src="./img/pla.gif" alt="プラ" width="25" height="25"><?php } ?></td>
        <td><?php echo $v->item->plaNote; ?></td>
        <td><?php if($v->item->paper){ ?><img src="./img/kami.gif" alt="紙" width="25" height="25"><?php } ?></td>
        <td><?php echo $v->item->paperNote; ?></td>
        <td>-</td><td>-</td>
        <td><?php echo $v->serializedColors; ?></td>
        <td><?php echo $v->serializedSizes; ?></td>
      </tr></tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="./item/add/">
      <input type="hidden" name="itemNameConfirm" value="<?php echo $v->item->name; ?>">
      <input type="hidden" name="categoryIdConfirm" value="<?php echo $v->itemVar->categoryId; ?>">
      <input type="hidden" name="priceConfirm" value="<?php echo $v->itemVar->price; ?>">
      <input type="hidden" name="colorNameConfirm" value="<?php echo $v->validFeaturesAmount?$v->inputColors:''; ?>">
      <input type="hidden" name="sizeNameConfirm" value="<?php echo $v->validFeaturesAmount?$v->inputSizes:''; ?>">
      <input type="hidden" name="plaConfirm" value="<?php echo $v->item->pla?'1':''; ?>">
      <input type="hidden" name="plaNoteConfirm" value="<?php echo $v->item->plaNote; ?>">
      <input type="hidden" name="paperConfirm" value="<?php echo $v->item->paper?'1':''; ?>">
      <input type="hidden" name="paperNoteConfirm" value="<?php echo $v->item->paperNote; ?>">

      <button type="submit" class="btn btn-primary mb-3"><i class="ti ti-plus me-1"></i>登録</button>

      <?php if(!$v->validFeaturesAmount) { ?>
        <div class="alert alert-warning" role="alert">
          色の数とサイズの数をかけて100を超えてはいけません。<br>
          色数 × サイズ数 ≦ 100
        </div>
      <?php } ?>

      <hr class="my-3">
      <p class="text-secondary">入力をやり直す場合は以下を編集して再度「登録」を押してください。</p>

      <div class="mb-3">
        <label for="confirmReedit-itemName" class="form-label">商品名 <span class="text-danger">*</span></label>
        <input type="text" id="confirmReedit-itemName" name="itemName" class="form-control"
               size="50" maxlength="50" required value="<?php echo $v->item->name; ?>">
        <div class="form-hint">50字以内</div>
      </div>

      <div class="mb-3">
        <label class="form-label">分類</label>
        <div id="category" class="border rounded p-3 bg-light">
          <div id="appendingParentInputs"></div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="appendingParent">+</button>
          <div id="categoriesRoot"></div>
          <p class="mt-2 mb-0">選択中の分類：<span class="categoryPath categoryPathChangable"><?php echo $v->itemVar->categoryId; ?></span>
            <button type="button" class="btn btn-sm btn-outline-secondary d-none ms-2" id="deselectCategory">選択解除</button>
          </p>
        </div>
      </div>
      <input type="hidden" name="categoryId" id="categoryId" value="<?php echo $v->itemVar->categoryId; ?>">

      <div class="mb-3">
        <label for="confirmReedit-price" class="form-label">価格</label>
        <div class="input-group" style="max-width: 22em;">
          <input type="text" id="confirmReedit-price" name="price" class="form-control"
                 pattern="^[0-9,]+$" maxlength="11"
                 value="<?php echo $v->itemVar->price; ?>">
          <span class="input-group-text">円</span>
        </div>
        <div class="form-hint">9桁までの数</div>
      </div>

      <div class="mb-3">
        <label for="confirmReedit-color" class="form-label">色 <span class="text-danger">*</span></label>
        <input type="text" id="confirmReedit-color" name="colorName" class="form-control"
               required value="<?php echo $v->inputColors; ?>">
        <div class="form-hint">複数入力する場合は半角カンマ ( , ) で区切って下さい。</div>
      </div>

      <div class="mb-3">
        <label for="confirmReedit-size" class="form-label">サイズ <span class="text-danger">*</span></label>
        <input type="text" id="confirmReedit-size" name="sizeName" class="form-control"
               required value="<?php echo $v->inputSizes; ?>">
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
            <input type="text" name="plaNote" class="form-control" maxlength="50" placeholder="メモ" value="<?php echo $v->item->plaNote; ?>">
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
            <input type="text" name="paperNote" class="form-control" maxlength="50" placeholder="メモ" value="<?php echo $v->item->paperNote; ?>">
          </div>
        </div>
      </fieldset>

      <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i>登録</button>
    </form>
  </div>
</div>

<?php }; ?>
