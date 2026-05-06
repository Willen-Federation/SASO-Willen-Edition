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
    class="btn btn-secondary inline-flex items-center gap-2 <?php echo htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8'); ?>"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812
           1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span><?php echo ui_text($buttonLabel); ?></span>
  </button>

  {{-- Camera overlay --}}
  <div
    x-show="active"
    x-cloak
    class="fixed inset-0 z-99999 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="closeCamera()"
  >
    <div class="absolute inset-0 bg-black/70" @click="closeCamera()"></div>

    <div class="relative z-10 w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
         x-trap.inert.noscroll="active">

      {{-- Header --}}
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white">
          <?php echo ui_text(__('ui.item.register.take_photo', [], null, 'Take Photo')); ?>
        </h2>
        <button
          type="button"
          @click="closeCamera()"
          class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10"
          aria-label="<?php echo ui_attr(__('ui.scanner.close', [], null, 'Close')); ?>"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 6 18 18M6 18 18 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      {{-- Mode tabs --}}
      <div class="mb-4 flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden" role="tablist">
        <button
          type="button"
          role="tab"
          :aria-selected="mode === 'camera'"
          @click="switchTab('camera')"
          :class="mode === 'camera' ? 'bg-brand-500 text-white' : 'bg-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
          class="flex-1 px-4 py-2 text-sm font-medium transition"
        >
          <?php echo ui_text(__('ui.item.register.take_photo', [], null, 'Camera')); ?>
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="mode === 'file'"
          @click="switchTab('file')"
          :class="mode === 'file' ? 'bg-brand-500 text-white' : 'bg-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
          class="flex-1 px-4 py-2 text-sm font-medium transition"
        >
          <?php echo ui_text(__('ui.item.register.image_drop', [], null, 'File')); ?>
        </button>
      </div>

      {{-- Camera tab --}}
      <div x-show="mode === 'camera'">
        <div x-show="!capturedDataUrl">
          <video
            id="<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
            class="w-full rounded-lg bg-black"
            style="max-height:280px;object-fit:cover;"
            autoplay
            playsinline
            muted
          ></video>
          <button type="button" @click="capturePhoto()"
                  class="btn btn-primary mt-3 w-full">
            <?php echo ui_text(__('ui.item.register.take_photo', [], null, 'Capture')); ?>
          </button>
        </div>
        <div x-show="capturedDataUrl">
          <img :src="capturedDataUrl" class="w-full rounded-lg" alt="" style="max-height:280px;object-fit:contain;">
          <div class="mt-3 flex gap-2">
            <button type="button" @click="retakePhoto()" class="btn btn-secondary flex-1">
              Retake
            </button>
            <button type="button" @click="confirmPhoto()" class="btn btn-primary flex-1">
              <?php echo ui_text(__('ui.button.save', [], null, 'Confirm')); ?>
            </button>
          </div>
        </div>
      </div>

      {{-- File tab --}}
      <div x-show="mode === 'file'">
        <div x-show="!capturedDataUrl">
          <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-8 text-gray-500 hover:border-brand-500 dark:border-gray-600 dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16l4-4 4 4 4-8 4 4M4 20h16"/>
            </svg>
            <span class="text-sm">
              <?php echo ui_text(__('ui.item.register.image_drop', [], null, 'Drop photo or tap to select')); ?>
            </span>
            <input type="file" accept="image/*" class="sr-only" @change="handleFile($event)">
          </label>
        </div>
        <div x-show="capturedDataUrl">
          <img :src="capturedDataUrl" class="w-full rounded-lg" alt="" style="max-height:280px;object-fit:contain;">
          <div class="mt-3 flex gap-2">
            <button type="button" @click="retakePhoto()" class="btn btn-secondary flex-1">
              Retake
            </button>
            <button type="button" @click="confirmPhoto()" class="btn btn-primary flex-1">
              <?php echo ui_text(__('ui.button.save', [], null, 'Confirm')); ?>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>
