//<?php $this->content = function($v) { ?>

export default class Password {
    constructor()
    {
        let errorEvent = another=>e=>{
            let equals = document.getElementById(another+'Password').value === e.target.value;
            document.getElementById('changePasswordSubmit').disabled = !equals;
            const err = document.getElementById('confirmPasswordError');
            if (err) {
                err.className = equals ? 'invalid-feedback d-none' : 'invalid-feedback d-block';
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
