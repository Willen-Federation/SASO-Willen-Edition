//<?php $this->content = function($v) { ?>

export default class ArchiveAll {
    constructor()
    {
        let every = document.querySelectorAll('.archiveAllCheckbox');
        let all = document.querySelector('#checkAllArchiveAllCheckbox');
        let checkedEvery = 0;
        every.forEach(e=>{
            let id = e.parentNode.nextSibling.nextSibling.firstChild.textContent;
            e.setAttribute('name', e.getAttribute('name')+id);
            e.setAttribute('value', id);
            e.addEventListener('change', one=>{
                if(one.target.checked) {
                    checkedEvery++;
                } else {
                    checkedEvery--;
                }
                if(one.target.checked && every.length !== checkedEvery) {
                    all.checked = true;
                    all.indeterminate = true;
                } else if(one.target.checked && every.length === checkedEvery) {
                    all.checked = true;
                    all.indeterminate = false;
                } else if(!one.target.checked && checkedEvery !== 0) {
                    all.checked = true;
                    all.indeterminate = true;
                } else if(!one.target.checked && checkedEvery === 0) {
                    all.checked = false;
                    all.indeterminate = false;
                }
            });
        });
        all?.addEventListener('change', e=>{
            every.forEach(one=>{
                if(e.target.checked && !e.target.indeterminate) {
                    one.checked = true;
                    checkedEvery = every.length;
                } else if(!e.target.checked) {
                    one.checked = false;
                    checkedEvery = 0;
                }
            });
        });
    }
}

//<?php } ?>
