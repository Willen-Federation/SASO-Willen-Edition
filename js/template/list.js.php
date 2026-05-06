//<?php $this->content = function($v) { ?>

export default class List {
    constructor()
    {
        let search = document.querySelector('#search');
        let current = document.querySelector('#current');
        let searchButton = document.querySelector('#searchButton');
        let jump = e=>{
            location.href = './'+current.textContent+'/search/'+encodeURIComponent(search.value.replace(/\//g, ''))
        };
        search?.addEventListener('keydown', e=>{
            if(e.key === 'Enter') {
                e.preventDefault();
                jump(e);
            }
        });
        searchButton?.addEventListener('click', jump);
    }
}

//<?php } ?>
