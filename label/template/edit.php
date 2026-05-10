<?php $this->title = 'ラベル寸法管理'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">ラベル寸法管理</li>
  </ol>
</nav>

<h2>ラベル寸法一覧</h2>
<div id="labelSizeList">
<ul>
<?php ($v->inside)('label', 'list'); ?>
<li>
<input type="radio" name="labelName" id="newLabelSize" value="(new)">
<label for="newLabelSize">新規作成</label>
</li>
</ul>
<p><a class="blue" id="labelSizeDeleteDisplay">ラベル寸法削除ボタン表示</a></p>
<p id="labelSizeDeleteButton" class="hidden"><button type="button" id="labelSizeDelete">削除</button></p>
</div>

<div id="newLabelSizeForm" class="hidden">
<h2>ラベル寸法登録</h2>
<p>下図の通り寸法を単位「mm」で入力して下さい。</p>
<p>小数以下第１位まで。用紙サイズはA4。</p>

<form method="post" action="./label/add/">
<p>ラベル名（半角英数、ハイフン、アンダーバー）：
<input id="newLabelName" type="text" name="labelName" pattern="^[0-9a-zA-Z_\-]+$" maxlength="50" required>
※メーカと品番等で重複しない名前をつけて下さい。</p>
<p class="blue">幅：<input type="number" name="width" size="5" step="0.1" min="0" max="999.9" required></p>
<p class="red">高さ：<input type="number" name="height" size="5" step="0.1" min="0" max="999.9" required></p>
<p class="green">左余白：<input type="number" name="marginLeft" size="5" step="0.1" min="0" max="999.9" required></p>
<p class="green">上余白：<input type="number" name="marginTop" size="5" step="0.1" min="0" max="999.9" required></p>
<p class="green">横間隔：<input type="number" name="intervalColumn" size="5" step="0.1" min="0" max="999.9" required></p>
<p class="green">縦間隔：<input type="number" name="intervalRow" size="5" step="0.1" min="0" max="999.9" required></p>
<p><input id="newLabelSizeSubmit" type="submit" value="登録"></>
</form>
</div>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
