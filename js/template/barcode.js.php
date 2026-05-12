//<?php $this->content = function($v) { ?>

export default class Barcode {
    constructor()
    {
        document.querySelector('#barcodeInput')?.focus();
        document.querySelector('.focused')?.select();
    }
}

//<?php } ?>
