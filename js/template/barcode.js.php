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
        let focused = document.querySelector('.focused');
        focused?.select();
    }
}

//<?php } ?>
