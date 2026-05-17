(function () {
  'use strict';
  // Passkey registration disabled pending real WebAuthn attestation
  // verification. See GitHub issue #203 for the security background.
  document.addEventListener('click', (event) => {
    if (event.target.closest('#register-passkey-btn')) {
      event.preventDefault();
    }
  });
})();
