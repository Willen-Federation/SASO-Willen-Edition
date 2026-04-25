//<?php $this->content = function($v) { ?>

export default class Feature {
    constructor()
    {
        let sums = document.querySelectorAll('td.featureSum');
        sums.forEach(sum=>{
            let featureCode = sum.getAttribute('id').replace(/sumof/, '');
            let shipment = document.querySelector('input#shipmentof'+featureCode);
            shipment?.setAttribute('max', parseInt(sum.textContent));
        });
        let labelSheetsAmount = document.querySelector('#labelSheetsAmount')?.textContent;
        let labelSheetsAmountMax = document.querySelector('#labelSheetsAmountMax')?.textContent;
        document.querySelectorAll('.labelSheetsInput').forEach(input=>{
            let self = input.value===''?0:input.value;
            input.setAttribute('max', parseInt(labelSheetsAmountMax) - parseInt(labelSheetsAmount) + parseInt(self));
        });
    }
}

//<?php } ?>
