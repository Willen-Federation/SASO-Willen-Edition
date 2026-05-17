(function () {
  'use strict';
  // Passkey login disabled pending real WebAuthn assertion verification.
  // See GitHub issue #203 for the security background and the requirements
  // for safely re-enabling this flow.
  document.addEventListener('click', (event) => {
    if (event.target.closest('#passkey-login-btn')) {
      event.preventDefault();
    }
  });
})();
