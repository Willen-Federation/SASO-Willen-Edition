<?php $this->title = 'アーカイブ一覧'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">アーカイブ一覧</li>
</ol>

<?php ($v->inside)('item', 'listFrame', $v->isArchive); ?>

<?php }; ?>