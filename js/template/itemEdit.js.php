//<?php $this->content = function($v) { ?>

export default class ItemEdit {
    constructor()
    {
        let displayCategoryChangeButton = document.querySelector('#changeCategotyOfAnItem');
        displayCategoryChangeButton?.addEventListener('click', e=>{
            let category = document.querySelector('#category');
            if(category.getAttribute('class') === 'hidden') {
                category.setAttribute('class', '');
                e.target.textContent = "分類変更しない";
            } else {
                category.setAttribute('class', 'hidden');
                e.target.textContent = "分類一覧表示";
            }
        });
    }
}

//<?php } ?>
