(function () {
  'use strict';

  const b64ToBuf = (value) => {
    const pad = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + pad).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from(raw, c => c.charCodeAt(0)).buffer;
  };
  const bufToB64 = (buffer) => btoa(String.fromCharCode(...new Uint8Array(buffer)))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');

  async function loginPasskey() {
    if (!window.PublicKeyCredential || !navigator.credentials) {
      alert(document.documentElement.lang === 'ja' ? 'このブラウザはパスキーに対応していません。' : 'This browser does not support passkeys.');
      return;
    }
    const begin = await fetch('/auth/passkeyBegin/', { method: 'POST', credentials: 'same-origin' });
    const options = await begin.json();
    if (!begin.ok || !options.allowCredentials || options.allowCredentials.length === 0) {
      throw new Error(options.error || 'No passkeys are registered');
    }
    const assertion = await navigator.credentials.get({
      publicKey: {
        challenge: b64ToBuf(options.challenge),
        rpId: options.rpId,
        allowCredentials: options.allowCredentials.map(c => ({ type: 'public-key', id: b64ToBuf(c.id) })),
        userVerification: 'preferred',
        timeout: 60000
      }
    });
    const complete = await fetch('/auth/passkeyComplete/', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        challenge: options.challenge,
        credentialId: bufToB64(assertion.rawId),
        authenticatorData: bufToB64(assertion.response.authenticatorData),
        clientDataJSON: bufToB64(assertion.response.clientDataJSON),
        signature: bufToB64(assertion.response.signature)
      })
    });
    if (!complete.ok) throw new Error('Passkey verification failed');
    location.href = '/';
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('#passkey-login-btn')) {
      loginPasskey().catch((error) => {
        alert((document.documentElement.lang === 'ja' ? 'パスキーログインに失敗しました: ' : 'Passkey login failed: ') + error.message);
      });
    }
  });
})();
