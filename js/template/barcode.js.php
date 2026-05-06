//<?php $this->content = function($v) { ?>

export default class Barcode {
    constructor()
    {
        let input = document.querySelector('#barcodeInput');
        input?.focus();
        let submit = document.querySelector('#barcodeSubmit');
        input?.addEventListener('input', e=>{
            let fullCode = e.target.value;
            let item = fullCode.slice(0, 8)
            let color = fullCode.slice(8, 10)
            let size = fullCode.slice(10, 12)
            submit?.setAttribute(
                'href',
                './item/start/item/'+item+'/color/'+color+'/size/'+size+'/action/shelf'
            );
            submit?.focus();
        });
        input?.addEventListener('keydown', e=>{
            if(e.key === 'Enter') {
                let href = submit?.getAttribute('href');
                if(href && href !== '') {
                    e.preventDefault();
                    location.href = href;
                }
            }
        });

        // キーワード検索ハンドラー
        let keywordInput = document.querySelector('#keywordInput');
        let keywordSubmit = document.querySelector('#keywordSubmit');
        let performKeywordSearch = ()=>{
            let keyword = keywordInput?.value?.trim();
            if(keyword) {
                location.href = './search/start/search/' + encodeURIComponent(keyword.replace(/\//g, ''));
            }
        };
        keywordSubmit?.addEventListener('click', performKeywordSearch);
        keywordInput?.addEventListener('keydown', e=>{
            if(e.key === 'Enter') {
                e.preventDefault();
                performKeywordSearch();
            }
        });

        let focused = document.querySelector('.focused');
        focused?.select();
    }
}

//<?php } ?>
