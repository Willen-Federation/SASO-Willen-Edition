//<?php $this->content = function($v) { ?>

export default class ItemEdit {
    constructor()
    {
        let displayCategoryChangeButton = document.querySelector('#changeCategotyOfAnItem');
        displayCategoryChangeButton?.addEventListener('click', e=>{
            let category = document.querySelector('#category');
            const isHidden = category.classList.contains('d-none');
            category.classList.toggle('d-none');
            const icon = e.currentTarget.querySelector('i');
            if (isHidden) {
                e.currentTarget.innerHTML = '<i class="bi bi-x me-1"></i>分類変更しない';
            } else {
                e.currentTarget.innerHTML = '<i class="bi bi-diagram-3 me-1"></i>分類一覧表示';
            }
        });
    }
}

//<?php } ?>
