//<?php $this->content = function($v) { ?>

export default class Shelf {
    constructor()
    {
        let mins = [...Array(5)].map((_, i) => i + 1).map(i=>document.querySelector('#dimension'+i+'min'));
        let maxs = [...Array(5)].map((_, i) => i + 1).map(i=>document.querySelector('#dimension'+i+'max'));
        let pageNumber = document.querySelector('#pageNumber');
        let submitMultiButton = document.querySelector('#submitMultiButton');
        let jump = e=>{
            let minsText = mins.reduce((carry, item, number)=>carry+'/min'+(number+1)+'/'+item.value??'', '');
            let maxsText = maxs.reduce((carry, item, number)=>carry+'/max'+(number+1)+'/'+item.value??'', '');
            location.href = './shelf/multi/page/'+pageNumber.value+minsText+maxsText;
        };
        mins.concat(maxs).concat([pageNumber]).forEach(elm=>{
            elm?.addEventListener('keypress', e=>{
                if(e.key === 'Enter') {
                    jump(e);
                }
            });
        });
        submitMultiButton?.addEventListener('click', jump);

        let single = document.querySelector('#singleShelfNumber');
        let submitSingleButton = document.querySelector('#submitSingleButton');
        let singleJump = e=>{
            if(single.validity.valid){
                location.href = './shelf/single/number/'+single.value;
            }
        }
        single?.addEventListener('keypress', e=>{
            if(e.key === 'Enter') {
                singleJump(e);
            }
        })
        submitSingleButton?.addEventListener('click', singleJump);
    }
}

//<?php } ?>
