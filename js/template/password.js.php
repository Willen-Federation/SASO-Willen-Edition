//<?php $this->content = function($v) { ?>

export default class Password {
    constructor()
    {
        let errorEvent = another=>e=>{
            let equals = document.getElementById(another+'Password').value === e.target.value;
            document.getElementById('changePasswordSubmit').disabled = !equals;
            if(!equals) {
                document.getElementById('confirmPasswordError').setAttribute('class', 'text-danger');
            } else {
                document.getElementById('confirmPasswordError').setAttribute('class', 'hidden');
            }
        }
        document.getElementById('newPassword')?.addEventListener(
            'input',
            errorEvent('confirm')
        );
        document.getElementById('confirmPassword')?.addEventListener(
            'input',
            errorEvent('new')
        );
    }
}

//<?php } ?>
