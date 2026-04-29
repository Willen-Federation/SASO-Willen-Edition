<?php $this->title = 'アーカイブ一覧'; ?>
<?php $this->content = function($v) { ?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-black dark:text-white">アーカイブ一覧</h2>
    <nav>
        <ol class="flex items-center gap-2">
            <li><a class="font-medium hover:text-primary" href="./">ホーム</a></li>
            <li class="font-medium text-primary">アーカイブ一覧</li>
        </ol>
    </nav>
</div>

<?php ($v->inside)('item', 'listFrame', $v->isArchive); ?>

<?php }; ?>