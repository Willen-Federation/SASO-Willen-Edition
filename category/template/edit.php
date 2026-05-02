<?php $this->title = '分類管理'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">分類管理</li>
</ol>
</nav>

<div id="appendingParentInputs"></div>
<button id="appendingParent">+</button>
<div id="categoriesRoot">
</div>

<?php }; ?>
