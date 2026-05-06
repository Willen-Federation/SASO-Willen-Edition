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

        document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(
            elm=>{
                if (!elm.querySelector('input[name="csrftoken"]')) {
                    elm.appendChild(input('csrftoken'));
                }
                if (!elm.querySelector('input[name="csrftokenConfirm"]')) {
                    elm.appendChild(input('csrftokenConfirm'));
                }
            }
        )
    }
}

//<?php } ?>
