<?php
/*
 * Camera Capture component.
 *
 * Props (via local variables before include):
 *   $fileInputId  string  (required) — id of the hidden <input type="file"> to fill
 *   $buttonLabel  string  (optional) — trigger button text
 *   $buttonClass  string  (optional) — extra CSS classes on trigger button
 *   $uniqueId     string  (optional) — suffix to keep HTML ids unique
 *
 * Events dispatched (on the wrapper element, bubble to window):
 *   photo-captured  { dataUrl, blob }  — when photo is snapped or file selected
 *   photo-confirmed { dataUrl, blob }  — when user clicks Confirm; component then closes
 *
 * Usage example:
 *   <?php
 *   $fileInputId = 'item-photo';
 *   include __DIR__ . '/path/to/cameraCapture.php';
 *   ?>
 */

$fileInputId = $fileInputId ?? '';
$buttonLabel = $buttonLabel ?? __('ui.item.register.take_photo', [], null, 'Take Photo');
$buttonClass = $buttonClass ?? '';
$uniqueId    = $uniqueId    ?? uniqid('cc_');

$wrapperId = 'saso-camera-wrapper-' . $uniqueId;
$videoId   = 'saso-camera-preview-' . $uniqueId;
?>

<div id="<?php echo htmlspecialchars($wrapperId, ENT_QUOTES, 'UTF-8'); ?>"
     x-data="{
       ...sasoCamera(),
       _videoId:     <?php echo json_encode($videoId); ?>,
       _fileInputId: <?php echo json_encode($fileInputId); ?>,

       openCamera() {
         this.capturedDataUrl = null;
         this.capturedBlob    = null;
         this.active          = true;
         if (this.mode === 'camera') {
           this.$nextTick(() => this._startCam());
         }
       },

       closeCamera() {
         this._stopCam();
         this.active = false;
       },

       switchTab(m) {
         if (m === this.mode) return;
         if (this.mode === 'camera') this._stopCam();
         this.capturedDataUrl = null;
         this.capturedBlob    = null;
         this.mode = m;
         if (m === 'camera') this.$nextTick(() => this._startCam());
       },

       _startCam() {
         const video = document.getElementById(this._videoId);
         if (!video) return;
         navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
           .then((stream) => {
             this.stream = stream;
             video.srcObject = stream;
             video.play();
           })
           .catch(() => { this.mode = 'file'; });
       },

       _stopCam() {
         if (this.stream) {
           this.stream.getTracks().forEach(t => t.stop());
           this.stream = null;
         }
         const video = document.getElementById(this._videoId);
         if (video) video.srcObject = null;
       },

       capturePhoto() {
         const video = document.getElementById(this._videoId);
         if (!video) return;
         const canvas = document.createElement('canvas');
         canvas.width  = video.videoWidth  || 640;
         canvas.height = video.videoHeight || 480;
         canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
         const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
         this.capturedDataUrl = dataUrl;
         const byteString = atob(dataUrl.split(',')[1]);
         const ab = new ArrayBuffer(byteString.length);
         const ia = new Uint8Array(ab);
         for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
         this.capturedBlob = new Blob([ab], { type: 'image/jpeg' });
         this.$dispatch('photo-captured', { dataUrl, blob: this.capturedBlob });
       },

       retakePhoto() {
         this.capturedDataUrl = null;
         this.capturedBlob    = null;
       },

       confirmPhoto() {
         this.$dispatch('photo-confirmed', { dataUrl: this.capturedDataUrl, blob: this.capturedBlob });
         // Push blob into hidden file input
         if (this._fileInputId) {
           const inp = document.getElementById(this._fileInputId);
           if (inp) {
             const dt = new DataTransfer();
             dt.items.add(new File([this.capturedBlob], 'photo.jpg', { type: 'image/jpeg' }));
             inp.files = dt.files;
             inp.dispatchEvent(new Event('change'));
           }
         }
         this.closeCamera();
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
     }"
>

  {{-- Trigger button --}}
  <button
    type="button"
    @click="openCamera()"
    class="btn btn-secondary d-inline-flex align-items-center gap-2 <?php echo htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8'); ?>"
  >
    <i class="bi bi-camera" aria-hidden="true"></i>
    <span><?php echo ui_text($buttonLabel); ?></span>
  </button>

  <div
    x-show="active"
    x-cloak
    class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
    style="z-index:9999;"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="closeCamera()"
  >
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background:rgba(0,0,0,.7);" @click="closeCamera()"></div>

    <div class="position-relative bg-white rounded shadow-lg p-4" style="width:100%;max-width:24rem;z-index:1;"
         x-trap.inert.noscroll="active">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h6 fw-semibold mb-0">
          <?php echo ui_text(__('ui.item.register.take_photo', [], null, 'Take Photo')); ?>
        </h2>
        <button
          type="button"
          @click="closeCamera()"
          class="btn-close"
          aria-label="<?php echo ui_attr(__('ui.scanner.close', [], null, 'Close')); ?>"
        ></button>
      </div>

      <div class="btn-group w-100 mb-3" role="tablist">
        <button
          type="button"
          role="tab"
          :aria-selected="mode === 'camera'"
          @click="switchTab('camera')"
          :class="mode === 'camera' ? 'btn-primary' : 'btn-outline-secondary'"
          class="btn btn-sm flex-grow-1"
        >
          <?php echo ui_text(__('ui.item.register.take_photo', [], null, 'Camera')); ?>
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="mode === 'file'"
          @click="switchTab('file')"
          :class="mode === 'file' ? 'btn-primary' : 'btn-outline-secondary'"
          class="btn btn-sm flex-grow-1"
        >
          <?php echo ui_text(__('ui.item.register.image_drop', [], null, 'File')); ?>
        </button>
      </div>

      <div x-show="mode === 'camera'">
        <div x-show="!capturedDataUrl">
          <video
            id="<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
            class="w-100 rounded bg-black"
            style="max-height:280px;object-fit:cover;"
            autoplay
            playsinline
            muted
          ></video>
          <button type="button" @click="capturePhoto()"
                  class="btn btn-primary mt-3 w-100">
            <?php echo ui_text(__('ui.item.register.take_photo', [], null, 'Capture')); ?>
          </button>
        </div>
        <div x-show="capturedDataUrl">
          <img :src="capturedDataUrl" class="w-100 rounded" alt="" style="max-height:280px;object-fit:contain;">
          <div class="mt-3 d-flex gap-2">
            <button type="button" @click="retakePhoto()" class="btn btn-secondary flex-grow-1">Retake</button>
            <button type="button" @click="confirmPhoto()" class="btn btn-primary flex-grow-1">
              <?php echo ui_text(__('ui.button.save', [], null, 'Confirm')); ?>
            </button>
          </div>
        </div>
      </div>

      <div x-show="mode === 'file'">
        <div x-show="!capturedDataUrl">
          <label class="d-flex flex-column align-items-center justify-content-center gap-2 rounded border border-2 p-5 text-muted"
                 style="border-style:dashed;cursor:pointer;">
            <i class="bi bi-image fs-2" aria-hidden="true"></i>
            <span class="small">
              <?php echo ui_text(__('ui.item.register.image_drop', [], null, 'Drop photo or tap to select')); ?>
            </span>
            <input type="file" accept="image/*" class="visually-hidden" @change="handleFile($event)">
          </label>
        </div>
        <div x-show="capturedDataUrl">
          <img :src="capturedDataUrl" class="w-100 rounded" alt="" style="max-height:280px;object-fit:contain;">
          <div class="mt-3 d-flex gap-2">
            <button type="button" @click="retakePhoto()" class="btn btn-secondary flex-grow-1">Retake</button>
            <button type="button" @click="confirmPhoto()" class="btn btn-primary flex-grow-1">
              <?php echo ui_text(__('ui.button.save', [], null, 'Confirm')); ?>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>
