//<?php $this->content = function($v) { ?>

export default class CSRF {
    constructor(csrftoken)
    {
        let input = name=>{
            let token = document.createElement('input');
            token.setAttribute('type', 'hidden');
            token.setAttribute('name', name);
            token.setAttribute('value', csrftoken);
            return token;
        };

        document.querySelectorAll('form[method="post"]').forEach(
            elm=>{
                elm.appendChild(input('csrftoken'));
                elm.appendChild(input('csrftokenConfirm'));
            }
        )
    }
}

//<?php } ?>