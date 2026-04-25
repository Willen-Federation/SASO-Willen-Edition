//<?php $this->content = function($v) { ?>

export default class Item {
    constructor()
    {
        let inventoryButtonDisplayButton = document.querySelector('#inventoryButtonDisplayButton');
        inventoryButtonDisplayButton?.addEventListener('input', e=>{
            let inventoryButtons = document.querySelectorAll('.inventoryButton');
            inventoryButtons.forEach(elm=>{elm.disabled = !e.target.checked});
        });
        document.querySelectorAll('.categoryPath').forEach(elm=>{
            fetch('./category/path.json/id/'+elm.textContent, {
                method: 'GET',
            })
            .then(res=>res.json())
            .then(data=>{
                elm.textContent = data.path;
            });
        });
    }
}

//<?php } ?>
