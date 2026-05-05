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
        search?.addEventListener('keypress', e=>{
            if(e.key === 'Enter') {
                jump(e);
            }
        });
        searchButton?.addEventListener('click', jump);
    }
}

//<?php } ?>
