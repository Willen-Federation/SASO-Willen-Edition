/*
 * SASO Scanner helpers.
 *
 * Loaded synchronously (like tailadmin.js) before Alpine.js deferred scripts.
 * Provides:
 *   - `sasoScanner()` — barcode / QR scan mode using html5-qrcode
 *   - `sasoCamera()`  — photo-capture mode using getUserMedia
 *
 * No build step. Plain ES2020 + Alpine.js v3 globals.
 */
(function () {
  'use strict';

  window.sasoBarcodeScanner = function (config) {
    const settings = Object.assign({
      readerId: 'saso-qr-reader',
      targetInput: '',
      messages: {
        libraryMissing: 'Scanner library not loaded.',
        elementMissing: 'Scanner element not found.',
        noCamera: 'No camera found.',
        cameraDenied: 'camera_denied',
      },
    }, config || {});

    return {
      active: false,
      result: null,
      error: null,
      scanner: null,
      stopping: false,
      _readerId: settings.readerId,
      _targetInput: settings.targetInput,
      _messages: settings.messages || {},

      openScanner() {
        this.result = null;
        this.error = null;
        this.active = true;
        this.stopping = false;
        this.$nextTick(() => this.startScan());
      },

      closeScanner() {
        this.stopping = true;
        return this.stopScan()
          .then(() => {
            this.active = false;
          })
          .finally(() => {
            this.stopping = false;
          });
      },

      startScan() {
        if (typeof Html5Qrcode === 'undefined') {
          this.error = this._messages.libraryMissing;
          return;
        }

        const el = document.getElementById(this._readerId);
        if (!el) {
          this.error = this._messages.elementMissing;
          return;
        }

        this.scanner = new Html5Qrcode(this._readerId);
        Html5Qrcode.getCameras()
          .then((cameras) => {
            if (!cameras || cameras.length === 0) {
              this.error = this._messages.noCamera;
              return undefined;
            }

            const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[cameras.length - 1];
            return this.scanner.start(
              cam.id,
              { fps: 10, qrbox: { width: 250, height: 250 } },
              (code) => {
                this.result = code;
                this.$dispatch('barcode-detected', { code, targetInput: this._targetInput });
                this.closeScanner();
              },
              () => {}
            );
          })
          .catch((err) => {
            const msg = (err && err.message) ? err.message : String(err);
            this.error = /denied|NotAllowed/i.test(msg) ? 'camera_denied' : msg;
          });
      },

      stopScan() {
        if (!this.scanner) return Promise.resolve();

        const scanner = this.scanner;
        this.scanner = null;

        return Promise.resolve()
          .then(() => {
            if (scanner.stop) return scanner.stop();
            return undefined;
          })
          .catch(() => {})
          .then(() => {
            if (scanner.clear) return scanner.clear();
            return undefined;
          })
          .catch(() => {});
      },
    };
  };

  document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) return;

    // -----------------------------------------------------------------------
    // sasoScanner — barcode / QR scan via camera
    // -----------------------------------------------------------------------
    Alpine.data('sasoScanner', () => ({
      active:  false,
      result:  null,
      error:   null,
      scanner: null,

      open() {
        this.result = null;
        this.error  = null;
        this.active = true;
        this.$nextTick(() => this.startScan());
      },

      close() {
        this.stopScan();
        this.active = false;
      },

      startScan() {
        if (typeof Html5Qrcode === 'undefined') {
          this.error = 'Scanner library not loaded.';
          return;
        }

        const readerId = 'saso-qr-reader';
        const el = document.getElementById(readerId);
        if (!el) {
          this.error = 'Scanner element not found.';
          return;
        }

        this.scanner = new Html5Qrcode(readerId);

        Html5Qrcode.getCameras()
          .then((cameras) => {
            if (!cameras || cameras.length === 0) {
              this.error = 'No camera found.';
              return;
            }
            // Prefer rear camera
            const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[cameras.length - 1];
            return this.scanner.start(
              cam.id,
              { fps: 10, qrbox: { width: 250, height: 250 } },
              (code) => {
                this.result = code;
                this.$dispatch('barcode-detected', { code });
                this.close();
              },
              () => { /* frame errors — ignored */ }
            );
          })
          .catch((err) => {
            const msg = (err && err.message) ? err.message : String(err);
            if (/denied|NotAllowed/i.test(msg)) {
              this.error = 'camera_denied';
            } else {
              this.error = msg;
            }
          });
      },

      stopScan() {
        if (this.scanner) {
          this.scanner.stop().catch(() => {});
          this.scanner = null;
        }
      },
    }));

    // -----------------------------------------------------------------------
    // sasoCamera — photo capture via getUserMedia or file input
    // -----------------------------------------------------------------------
    Alpine.data('sasoCamera', () => ({
      active:          false,
      stream:          null,
      capturedDataUrl: null,
      capturedBlob:    null,
      mode:            'camera', // 'camera' | 'file'

      open() {
        this.capturedDataUrl = null;
        this.capturedBlob    = null;
        this.active          = true;
        if (this.mode === 'camera') {
          this.$nextTick(() => this.startCamera());
        }
      },

      close() {
        this.stopCamera();
        this.active = false;
      },

      switchMode(m) {
        if (m === this.mode) return;
        if (this.mode === 'camera') this.stopCamera();
        this.mode            = m;
        this.capturedDataUrl = null;
        this.capturedBlob    = null;
        if (m === 'camera') {
          this.$nextTick(() => this.startCamera());
        }
      },

      startCamera() {
        const video = document.getElementById('saso-camera-preview');
        if (!video) return;
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
          .then((stream) => {
            this.stream    = stream;
            video.srcObject = stream;
            video.play();
          })
          .catch(() => {
            this.mode = 'file';
          });
      },

      stopCamera() {
        if (this.stream) {
          this.stream.getTracks().forEach(t => t.stop());
          this.stream = null;
        }
        const video = document.getElementById('saso-camera-preview');
        if (video) {
          video.srcObject = null;
        }
      },

      capture() {
        const video = document.getElementById('saso-camera-preview');
        if (!video) return;
        const canvas = document.createElement('canvas');
        canvas.width  = video.videoWidth  || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        this.capturedDataUrl = dataUrl;
        // Convert dataURL to blob
        const byteString = atob(dataUrl.split(',')[1]);
        const mime = 'image/jpeg';
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
        this.capturedBlob = new Blob([ab], { type: mime });
        this.$dispatch('photo-captured', { dataUrl: this.capturedDataUrl, blob: this.capturedBlob });
      },

      retake() {
        this.capturedDataUrl = null;
        this.capturedBlob    = null;
      },

      confirm() {
        this.$dispatch('photo-confirmed', { dataUrl: this.capturedDataUrl, blob: this.capturedBlob });
        this.close();
      },

      handleFile(event) {
        const file = event.target.files && event.target.files[0];
        if (!file) return;
        this.capturedBlob = file;
        const reader = new FileReader();
        reader.onload = (e) => {
          this.capturedDataUrl = e.target.result;
          this.$dispatch('photo-captured', { dataUrl: this.capturedDataUrl, blob: this.capturedBlob });
        };
        reader.readAsDataURL(file);
      },
    }));

    // -----------------------------------------------------------------------
    // sasoBarcodeSearch — home-page barcode lookup + quick registration
    // -----------------------------------------------------------------------
    Alpine.data('sasoBarcodeSearch', () => ({
      code: '',
      result: null,
      error: null,
      itemUrl: null,
      loading: false,
      showRegModal: false,
      reg: { barcodeId: '', itemName: '', colorName: '', sizeName: '', price: '' },
      regLoading: false,
      regError: null,
      csrfToken: '',
      labels: { notFound: '', invalid: '', regError: '', regRequired: '' },

      _buf: '', _lastTime: 0, _timer: null, SCAN_GAP_MS: 50, MIN_SCAN_CHARS: 8,

      init() {
        this.csrfToken = this.$el.dataset.csrf || '';
        this.labels = {
          notFound:    this.$el.dataset.labelNotFound    || '',
          invalid:     this.$el.dataset.labelInvalid     || '',
          regError:    this.$el.dataset.labelRegError    || '',
          regRequired: this.$el.dataset.labelRegRequired || '',
        };
      },

      onWindowKey(e) {
        const el = document.activeElement;
        const tag = el ? el.tagName : '';
        const isBarcodeInput = el && el.id === 'barcodeInput';
        const isOtherInput = !isBarcodeInput && (
          tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' ||
          (el && el.isContentEditable)
        );
        if (isOtherInput) return;

        const now = performance.now();
        const gap = now - this._lastTime;
        this._lastTime = now;

        if (e.key === 'Enter') {
          if (this._buf.length >= this.MIN_SCAN_CHARS) {
            e.preventDefault();
            this.code = this._buf;
            this._buf = '';
            if (this._timer) {
              clearTimeout(this._timer);
              this._timer = null;
            }
            this.$nextTick(() => this.search());
          } else {
            this._buf = '';
          }
          return;
        }

        if (e.key.length !== 1) return;

        if (this._buf.length > 0 && gap > this.SCAN_GAP_MS * 5) {
          this._buf = '';
        }

        this._buf += e.key;

        if (this._timer) clearTimeout(this._timer);
        if (this._buf.length >= this.MIN_SCAN_CHARS) {
          this._timer = setTimeout(() => {
            if (this._buf.length >= this.MIN_SCAN_CHARS) {
              this.code = this._buf;
              this._buf = '';
              this.search();
            }
            this._timer = null;
          }, 150);
        }
      },

      // Matches BarcodeCode::PATTERN: alphanumeric-prefix codes (e.g. PND000000001)
      // and 13-digit JAN/EAN barcodes — keeps legacy 12-digit Feature codes excluded.
      isPoolCode() {
        const v = this.code.trim().toUpperCase();
        return /^[A-Z][A-Z0-9]{0,7}\d{4,12}$/.test(v) || /^\d{13}$/.test(v);
      },

      isLegacy() {
        return /^\d{12}$/.test(this.code.trim());
      },

      async search() {
        const raw = this.code.trim();
        this.result = null;
        this.error = null;
        this.itemUrl = null;
        this.showRegModal = false;
        if (!raw) return;

        if (this.isLegacy()) {
          const item  = raw.slice(0, 8);
          const color = raw.slice(8, 10);
          const size  = raw.slice(10, 12);
          window.location.href = './item/start/item/' + item + '/color/' + color + '/size/' + size + '/action/shelf';
          return;
        }

        if (!this.isPoolCode()) {
          this.error = this.labels.invalid;
          return;
        }

        this.loading = true;
        try {
          const res  = await fetch('./api/v1/barcode/' + encodeURIComponent(raw));
          const data = await res.json();
          if (!res.ok || !data) {
            this.error = this.labels.notFound;
          } else if (data.item && data.item.id) {
            this.result  = { type: 'pnd', name: data.item.name, id: data.item.id };
            this.itemUrl = './item/start/item/' + data.item.id + '/';
          } else {
            this.reg.barcodeId = raw;
            this.reg.itemName  = '';
            this.reg.colorName = '';
            this.reg.sizeName  = '';
            this.reg.price     = '';
            this.regError      = null;
            this.showRegModal  = true;
          }
        } catch (e) {
          this.error = this.labels.notFound;
        } finally {
          this.loading = false;
        }
      },

      async submitReg() {
        if (!this.reg.itemName.trim() || !this.reg.colorName.trim() || !this.reg.sizeName.trim()) {
          this.regError = this.labels.regRequired;
          return;
        }
        this.regLoading = true;
        this.regError   = null;
        try {
          const form = new FormData();
          form.append('csrftoken',  this.csrfToken);
          form.append('barcodeId',  this.reg.barcodeId);
          form.append('itemName',   this.reg.itemName.trim());
          form.append('colorName',  this.reg.colorName.trim());
          form.append('sizeName',   this.reg.sizeName.trim());
          form.append('price',      this.reg.price);
          const res = await fetch('./item/registerFromBarcode/', {
            method:   'POST',
            body:     form,
            redirect: 'follow',
          });
          if (res.ok && res.url && res.url.includes('/item/')) {
            window.location.href = res.url;
          } else {
            this.regError = this.labels.regError;
          }
        } catch (e) {
          this.regError = this.labels.regError;
        } finally {
          this.regLoading = false;
        }
      },
    }));
  });
})();
