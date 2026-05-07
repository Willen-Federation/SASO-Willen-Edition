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

  async function registerPasskey() {
    if (!window.PublicKeyCredential || !navigator.credentials) {
      alert(document.documentElement.lang === 'ja' ? 'このブラウザはパスキーに対応していません。' : 'This browser does not support passkeys.');
      return;
    }
    const token = document.getElementById('register-passkey-btn')?.dataset.csrftoken || '';
    const begin = await fetch('./mypage/passkeyBegin/', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': token }
    });
    const options = await begin.json();
    if (!begin.ok) throw new Error(options.error || 'begin failed');
    const credential = await navigator.credentials.create({
      publicKey: {
        challenge: b64ToBuf(options.challenge),
        rp: { name: options.rpName, id: options.rpId },
        user: { id: b64ToBuf(options.userId), name: options.userName, displayName: options.displayName },
        pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
        authenticatorSelection: { userVerification: 'preferred' },
        timeout: 60000,
        attestation: 'none'
      }
    });
    const name = prompt(document.documentElement.lang === 'ja' ? 'このパスキーの名前' : 'Passkey name', options.displayName + ' passkey') || 'Passkey';
    const complete = await fetch('./mypage/passkeyComplete/', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
      body: JSON.stringify({
        challenge: options.challenge,
        credentialId: bufToB64(credential.rawId),
        attestationObject: bufToB64(credential.response.attestationObject),
        clientDataJSON: bufToB64(credential.response.clientDataJSON),
        transports: credential.response.getTransports ? credential.response.getTransports() : [],
        name
      })
    });
    if (!complete.ok) throw new Error('complete failed');
    location.reload();
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('#register-passkey-btn')) {
      registerPasskey().catch((error) => {
        alert((document.documentElement.lang === 'ja' ? 'パスキー登録に失敗しました: ' : 'Passkey registration failed: ') + error.message);
      });
    }
  });
})();
