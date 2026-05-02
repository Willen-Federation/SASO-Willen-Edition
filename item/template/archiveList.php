<?php $this->title = 'アーカイブ一覧'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">アーカイブ一覧</li>
</ol>
</nav>

<?php ($v->inside)('item', 'listFrame', $v->isArchive); ?>

<?php }; ?>