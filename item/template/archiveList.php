<?php $this->title = 'アーカイブ一覧'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">アーカイブ一覧</li>
  </ol>
</nav>

<?php ($v->inside)('item', 'listFrame', $v->isArchive); ?>

<?php }; ?>
