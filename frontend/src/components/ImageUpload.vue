<template>
  <div class="image-upload border border-gray-300 rounded-xl p-6 bg-gray-50">
    <div class="upload-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
      <h4 class="text-lg font-semibold text-gray-900 mb-0">Attach Images</h4>
      <div class="upload-actions flex gap-3">
        <button type="button" @click.stop="triggerFileInput" class="btn-outlined btn-small">
          <span class="material-icon-sm mr-2">upload_file</span>
          Choose Files
        </button>
        <button type="button" @click.stop="openCamera" class="btn-filled btn-small">
          <span class="material-icon-sm mr-2">photo_camera</span>
          Take Photo
        </button>
      </div>
    </div>

    <!-- Hidden file inputs -->
    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      multiple
      @change="handleFileSelection"
      class="hidden"
    >

    <!-- Camera capture fallback for mobile -->
    <input
      ref="cameraInput"
      type="file"
      accept="image/*"
      capture="environment"
      @change="handleFileSelection"
      class="hidden"
    >

    <!-- Camera capture -->
    <div v-if="showCamera" class="modal-overlay">
      <div class="modal-content max-w-md">
        <div class="modal-header">
          <h3 class="text-xl font-semibold text-gray-900 mb-0">Take Photo</h3>
          <button type="button" @click="closeCamera" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div class="modal-body p-6">
          <video ref="cameraVideo" autoplay playsinline class="camera-video w-full rounded-lg bg-black"></video>
          <canvas ref="cameraCanvas" class="hidden"></canvas>
        </div>
        <div class="modal-footer">
          <button type="button" @click="closeCamera" class="btn-outlined">Cancel</button>
          <button type="button" @click="capturePhoto" class="btn-filled" :disabled="!cameraStream">
            <span class="material-icon-sm mr-2">camera</span>
            Capture
          </button>
        </div>
      </div>
    </div>

    <!-- Image preview grid -->
    <div v-if="images.length > 0" class="image-preview-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mb-6">
      <div
        v-for="(image, index) in images"
        :key="index"
        class="image-preview-item border border-gray-200 rounded-lg overflow-hidden bg-white"
      >
        <div class="image-container relative h-32 overflow-hidden">
          <img :src="image.preview" :alt="image.name" class="preview-image w-full h-full object-cover">
          <div class="image-overlay absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center opacity-0 hover:opacity-100">
            <button @click="removeImage(index)" class="remove-image-btn bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-700 transition-colors" title="Remove image">
              <span class="material-icon-sm">delete</span>
            </button>
          </div>
        </div>
        <div class="image-info p-3">
          <span class="image-name block text-sm font-medium text-gray-900 truncate">{{ image.name }}</span>
          <span class="image-size block text-xs text-gray-600">{{ formatFileSize(image.size) }}</span>
        </div>
      </div>
    </div>

    <!-- Upload progress -->
    <div v-if="uploading" class="upload-progress mb-4">
      <div class="progress-bar w-full h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
        <div class="progress-fill h-full bg-blue-600 transition-all duration-300 ease-out" :style="{ width: uploadProgress + '%' }"></div>
      </div>
      <p class="text-base text-gray-900">Uploading images... {{ uploadProgress }}%</p>
    </div>

    <!-- Error messages -->
    <div v-if="error" class="error-message bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      <span class="material-icon-sm mr-2">error</span>
      {{ error }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'ImageUpload',
  props: {
    jobId: {
      type: Number,
      default: null
    },
    maxImages: {
      type: Number,
      default: 10
    },
    maxFileSize: {
      type: Number,
      default: 10 * 1024 * 1024 // 10MB
    }
  },
  data() {
    return {
      images: [],
      showCamera: false,
      cameraStream: null,
      cameraSupported: false,
      uploading: false,
      uploadProgress: 0,
      error: null
    }
  },
  mounted() {
    this.checkCameraSupport()
  },
  beforeUnmount() {
    this.stopCamera()
  },
  methods: {
    checkCameraSupport() {
      // Basic check for API availability and secure context
      const hasAPI = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)
      const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1'

      this.cameraSupported = hasAPI && isSecure

      if (!hasAPI) {
        console.log('Camera not supported: MediaDevices API not available')
      } else if (!isSecure) {
        console.log('Camera not supported: Not in secure context (HTTPS required)')
      } else {
        console.log('Camera potentially supported - will request permission when used')
      }
    },

    triggerFileInput() {
      this.$refs.fileInput.click()
    },

    handleFileSelection(event) {
      const files = Array.from(event.target.files)
      this.addImages(files)
      // Clear the input so the same file can be selected again if needed
      event.target.value = ''
    },

    addImages(files) {
      const validFiles = []
      const errors = []

      files.forEach(file => {
        if (!file.type.startsWith('image/')) {
          errors.push(`${file.name} is not an image file`)
          return
        }

        if (file.size > this.maxFileSize) {
          errors.push(`${file.name} is too large (max ${this.formatFileSize(this.maxFileSize)})`)
          return
        }

        validFiles.push(file)
      })

      if (this.images.length + validFiles.length > this.maxImages) {
        errors.push(`Cannot add ${validFiles.length} images. Maximum ${this.maxImages} images allowed.`)
        return
      }

      if (errors.length > 0) {
        this.error = errors.join('. ')
        return
      }

      this.error = null

      validFiles.forEach(file => {
        const reader = new FileReader()
        reader.onload = (e) => {
          this.images.push({
            file: file,
            name: file.name,
            size: file.size,
            type: file.type,
            preview: e.target.result
          })
        }
        reader.readAsDataURL(file)
      })

      this.$emit('images-changed', this.images)
    },

    removeImage(index) {
      this.images.splice(index, 1)
      this.$emit('images-changed', this.images)
    },

    async openCamera() {
      try {
        // Clear any previous errors
        this.error = null

        // Try back camera first, then fall back to any camera
        let constraints = { video: { facingMode: 'environment' } }

        try {
          this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints)
        } catch (firstError) {
          console.log('Back camera not available, trying any camera:', firstError.message)
          // Fall back to any available camera
          constraints = { video: true }
          this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints)
        }

        // Show the modal first to ensure video element is rendered
        this.showCamera = true

        // Use nextTick to ensure DOM is updated before setting video source
        await this.$nextTick()

        // Now set the video source
        const video = this.$refs.cameraVideo
        if (video) {
          video.srcObject = this.cameraStream
          console.log('Camera opened successfully')
        } else {
          throw new Error('Video element not found')
        }
      } catch (error) {
        console.error('Error accessing camera:', error)

        // Provide more specific error messages
        let errorMessage = 'Unable to access camera.'
        if (error.name === 'NotAllowedError') {
          errorMessage += ' Camera permission was denied. Please allow camera access and try again.'
        } else if (error.name === 'NotFoundError') {
          errorMessage += ' No camera found on this device.'
        } else if (error.name === 'NotReadableError') {
          errorMessage += ' Camera is already in use by another application.'
        } else if (error.name === 'OverconstrainedError') {
          errorMessage += ' Camera constraints cannot be satisfied.'
        } else {
          errorMessage += ` ${error.message}`
        }

        this.error = errorMessage
      }
    },

    closeCamera() {
      this.stopCamera()
      this.showCamera = false
    },

    stopCamera() {
      if (this.cameraStream) {
        this.cameraStream.getTracks().forEach(track => track.stop())
        this.cameraStream = null
      }
    },

    capturePhoto() {
      if (!this.cameraStream) return

      const video = this.$refs.cameraVideo
      const canvas = this.$refs.cameraCanvas
      const context = canvas.getContext('2d')

      // Set canvas size to video size
      canvas.width = video.videoWidth
      canvas.height = video.videoHeight

      // Draw the video frame to canvas
      context.drawImage(video, 0, 0, canvas.width, canvas.height)

      // Convert to blob
      canvas.toBlob((blob) => {
        const file = new File([blob], `photo_${Date.now()}.jpg`, { type: 'image/jpeg' })
        this.addImages([file])
        this.closeCamera()
      }, 'image/jpeg', 0.8)
    },

    async uploadImages() {
      if (!this.jobId || this.images.length === 0) {
        return
      }

      this.uploading = true
      this.uploadProgress = 0
      this.error = null

      const token = localStorage.getItem('token')
      let successCount = 0

      for (let i = 0; i < this.images.length; i++) {
        const image = this.images[i]
        const formData = new FormData()
        formData.append('image', image.file)
        formData.append('job_id', this.jobId.toString())

        try {
          const response = await fetch('/backend/api/upload-job-image.php', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: formData
          })

          if (response.ok) {
            successCount++
          } else {
            const errorData = await response.json()
            throw new Error(errorData.error || 'Upload failed')
          }
        } catch (error) {
          console.error('Upload error:', error)
          this.error = `Failed to upload ${image.name}: ${error.message}`
          break
        }

        this.uploadProgress = Math.round(((i + 1) / this.images.length) * 100)
      }

      this.uploading = false

      if (successCount === this.images.length) {
        this.images = []
        this.$emit('upload-complete', successCount)
      }
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }
  }
}
</script>
