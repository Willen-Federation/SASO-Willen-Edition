//<?php $this->content = function($v) { ?>

export default class Label {
    constructor(csrftoken)
    {
        document.getElementById('labelSizeDeleteDisplay')?.addEventListener(
            'click',
            e=>{
                let button = document.getElementById('labelSizeDeleteButton');
                if(button.classList.contains('hidden')) {
                    button.classList.remove('hidden');
                } else {
                    button.classList.add('hidden');
                }
            }
        );
        document.getElementById('labelSizeDelete')?.addEventListener(
            'click',
            e=>{
                fetch('./label/delete/', {
                    method: 'POST',
                    body: JSON.stringify({
                        labelName: document.querySelector('input[name="labelName"]:checked')?.value,
                        csrftoken: csrftoken
                    }),
                })
                .then(res=>res.text())
                .then(data=>{
                    location.reload();
                });
            }
        );
        const sizeElements = [
            'marginTop',
            'marginLeft',
            'width',
            'height',
            'intervalColumn',
            'intervalRow',
        ];
        let displaySizeToSvg = (shortenName)=>{
            let labelName = document.querySelector('input[name="labelName"]:checked').value;
            fetch('./label/size.json', {
                method: 'POST',
                body: JSON.stringify({
                    labelName: labelName,
                    csrftoken: csrftoken
                }),
            })
            .then(res=>res.json())
            .then(data=>{
                sizeElements.forEach(name=>{
                    document.getElementById(name).textContent = data[name]+'mm';
                });
                shortenName?.(labelName);
            });
        };
        document.getElementById('labelSizeList')?.addEventListener(
            'change',
            e=>{
                if(document.querySelector('input[name="labelName"]:checked').value === '(new)') {
                    document.getElementById('newLabelSizeForm').classList.remove('hidden');
                    sizeElements.forEach(name=>{
                        document.querySelector('input[name="'+name+'"]')?.dispatchEvent(new Event('input'));
                        document.querySelector('input[name="'+name+'"]')?.addEventListener(
                            'input',
                            e=>{
                                if(document.getElementById(name)){
                                    document.getElementById(name).textContent = e.target.value+'mm';
                                }
                            }
                        );
                    });
                } else {
                    document.getElementById('newLabelSizeForm').classList.add('hidden');
                    displaySizeToSvg();
                }
            }
        );
        document.getElementById('newLabelSize')?.addEventListener(
            'click',
            e=>{
                document.getElementById('labelSizeList')?.dispatchEvent(new Event('change'));
            }
        );
        document.getElementById('newLabelName')?.addEventListener(
            'change',
            e=>{
                fetch('./label/size.json', {
                    method: 'POST',
                    body: JSON.stringify({
                        labelName: e.target.value,
                        csrftoken: csrftoken
                    }),
                })
                .then(res=>res.json())
                .then(data=>{
                    document.getElementById('newLabelSizeSubmit').disabled = data.length !== 0;
                });
            }
        );
        document.getElementById('labelPrint')?.addEventListener(
            'change',
            e=>{
                let shortenName = (labelName)=>{
                    document.querySelectorAll('.fullCode')?.forEach(elm=>{
                        fetch('./label/shortName.json', {
                            method: 'POST',
                            body: JSON.stringify({
                                labelName: labelName,
                                fullCode: elm.textContent,
                                csrftoken: csrftoken
                            }),
                        })
                        .then(res=>res.json())
                        .then(data=>{
                            document.querySelector('#longName'+elm.textContent).textContent = data['shortName'];
                        });
                    });
                };
                displaySizeToSvg(shortenName);
            }
        );
        document.querySelector('#deleteAllItemLabels')?.addEventListener('click', e=>{
            if(confirm('商品ラベルを消去します')) {
                e.target.parentNode.submit();
            }
        });
    }
}

//<?php } ?>
