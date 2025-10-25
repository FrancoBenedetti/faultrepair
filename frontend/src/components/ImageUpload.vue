<template>
  <div class="image-upload border border-gray-300 rounded-lg p-4 bg-gray-50">
    <div class="upload-header flex items-center justify-between mb-4">
      <h4 class="text-base font-semibold text-gray-900">Images</h4>
      <div class="upload-actions flex gap-2">
        <button
          type="button"
          @click.stop="triggerFileInput"
          class="btn-secondary btn-sm flex items-center gap-1"
          title="Upload files">
          <span class="material-icon-sm">upload_file</span>
          Upload
        </button>
        <button
          type="button"
          @click.stop="openCamera"
          class="btn-secondary btn-sm flex items-center gap-1"
          title="Take photo">
          <span class="material-icon-sm">camera_alt</span>
          Camera
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
        <div class="image-container relative h-32 overflow-hidden group">
          <img :src="image.preview" :alt="image.name" class="preview-image w-full h-full object-cover" @click="viewFullSize(image, index)">

          <!-- View button (top-left) - always visible on hover -->
          <button @click.stop="viewFullSize(image, index)" class="view-btn absolute top-2 left-2 bg-black bg-opacity-50 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-opacity-75" title="View image">
            <span class="material-icon-xs">zoom_in</span>
          </button>

          <!-- Delete button (top-right) - always visible on hover -->
          <button
            v-if="!image.existing"
            @click.stop="removeImage(index)"
            class="delete-btn absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-700"
            title="Remove image">
            <span class="material-icon-xs">close</span>
          </button>

          <button
            v-else
            @click.stop="deleteExistingImage(index)"
            class="delete-btn absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-700"
            title="Delete image permanently">
            <span class="material-icon-xs">delete</span>
          </button>

          <!-- Image name overlay on bottom -->
          <div class="image-name-overlay absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs px-2 py-1 truncate">
            {{ image.name }}
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
      <div class="progress-bar w-full h-3 bg-gray-200 rounded-full overflow-hidden mb-3 border border-gray-300">
        <div class="progress-fill h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500 ease-out rounded-full" :style="{ width: uploadProgress + '%' }"></div>
      </div>
      <p class="text-base text-gray-800 font-medium flex items-center gap-2">
        <span class="material-icon-sm animate-spin text-blue-600">hourglass_empty</span>
        Uploading images... {{ uploadProgress }}%
      </p>
    </div>

    <!-- Error messages -->
    <div v-if="error" class="error-message bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      <span class="material-icon-sm mr-2">error</span>
      {{ error }}
    </div>

    <!-- Full Size Image Modal -->
    <div v-if="selectedImage !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75" @click="closeImageModal">
      <div class="relative max-w-4xl max-h-screen p-4">
        <img
          :src="selectedImage.preview"
          :alt="selectedImage.name"
          class="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
          @click.stop
        />
        <div class="absolute top-4 right-4 flex gap-2">
          <button
            @click.stop="closeImageModal"
            class="bg-black bg-opacity-50 text-white rounded-full w-10 h-10 flex items-center justify-center hover:bg-opacity-75 transition-all"
            title="Close"
          >
            <span class="material-icon-sm">close</span>
          </button>
        </div>
        <div class="absolute bottom-4 left-4 right-4 bg-black bg-opacity-75 text-white p-3 rounded-lg">
          <div class="font-semibold">{{ selectedImage.name }}</div>
          <div class="text-sm opacity-80">{{ formatFileSize(selectedImage.size) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ImageUpload',
  props: {
    maxImages: {
      type: Number,
      default: 10
    },
    maxFileSize: {
      type: Number,
      default: 10 * 1024 * 1024 // 10MB
    },
    existingImages: {
      type: Array,
      default: () => []
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
      error: null,
      selectedImage: null
    }
  },
  mounted() {
    this.checkCameraSupport()
    this.initializeExistingImages(this.existingImages)
  },
  watch: {
    existingImages: {
      handler(newImages) {
        this.initializeExistingImages(newImages)
      },
      immediate: false
    }
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

    async deleteExistingImage(index) {
      const image = this.images[index]

      if (!image || !image.existing || !image.id) {
        console.error('ImageUpload: Cannot delete - invalid existing image data')
        return
      }

      if (!confirm(`Are you sure you want to permanently delete "${image.name}"?`)) {
        return
      }

      const token = localStorage.getItem('token')
      if (!token) {
        this.error = 'Authentication required'
        return
      }

      try {
        const url = `/backend/api/job-images.php?image_id=${image.id}&token=${encodeURIComponent(token)}`
        const response = await fetch(url, {
          method: 'DELETE'
        })

        if (response.ok) {
          console.log('ImageUpload: Successfully deleted existing image:', image.id)
          // Remove from local array
          this.images.splice(index, 1)
          this.$emit('images-changed', this.images)

          // Emit event that image was deleted
          this.$emit('image-deleted', image.id)
        } else {
          const errorData = await response.json()
          this.error = `Failed to delete image: ${errorData.error || 'Unknown error'}`
          console.error('ImageUpload: Delete failed:', errorData)
        }
      } catch (error) {
        console.error('ImageUpload: Delete error:', error)
        this.error = `Failed to delete image: ${error.message}`
      }
    },

    viewFullSize(image) {
      this.selectedImage = image
    },

    closeImageModal() {
      this.selectedImage = null
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

    async uploadImages(jobId = null) {
      const uploadJobId = jobId
      console.log('ImageUpload: uploadImages called with jobId:', uploadJobId, 'images:', this.images.length)

      if (!uploadJobId || this.images.length === 0) {
        console.log('ImageUpload: Skipping upload - no images to upload')
        return { success: true, uploadedCount: 0, message: 'No new images to upload' }
      }

      this.uploading = true
      this.uploadProgress = 0
      this.error = null

      const token = localStorage.getItem('token')
      if (!token) {
        this.error = 'No authentication token found'
        this.uploading = false
        return { success: false, error: 'Authentication required' }
      }

      console.log('ImageUpload: Using token:', token ? 'present' : 'missing')

      let successCount = 0
      let errors = []

      // Only upload images that have actual files (not existing images)
      const imagesToUpload = this.images.filter(img => !img.existing)

      if (imagesToUpload.length === 0) {
        console.log('ImageUpload: No new images to upload - all are existing')
        this.uploading = false
        return { success: true, uploadedCount: 0, message: 'No new images to upload' }
      }

      console.log('ImageUpload: Will upload', imagesToUpload.length, 'new images out of', this.images.length, 'total')

      for (let i = 0; i < imagesToUpload.length; i++) {
        const image = imagesToUpload[i]
        const formData = new FormData()
        formData.append('image', image.file)
        formData.append('job_id', uploadJobId.toString())

        console.log('ImageUpload: Uploading image', i + 1, 'of', imagesToUpload.length, 'for job', uploadJobId, 'file:', image.name)

        try {
          const url = `/backend/api/upload-job-image.php?token=${encodeURIComponent(token)}`
          console.log('ImageUpload: Making request to:', url)

          const response = await fetch(url, {
            method: 'POST',
            body: formData
          })

          console.log('ImageUpload: Response status:', response.status, response.ok)

          if (response.ok) {
            const responseData = await response.json()
            console.log('ImageUpload: Upload successful:', responseData)
            successCount++
          } else {
            let errorData
            try {
              errorData = await response.json()
            } catch (parseError) {
              errorData = { error: 'Unknown server error' }
            }
            console.error('ImageUpload: Upload failed with response:', errorData)
            const errorMsg = errorData.error || 'Upload failed'
            errors.push(`Failed to upload ${image.name}: ${errorMsg}`)
          }
        } catch (error) {
          console.error('ImageUpload: Upload error:', error)
          errors.push(`Failed to upload ${image.name}: ${error.message}`)
        }

        this.uploadProgress = Math.round(((i + 1) / imagesToUpload.length) * 100)
      }

      this.uploading = false
      console.log('ImageUpload: Upload process complete. Success count:', successCount, 'errors:', errors.length)

      if (errors.length > 0) {
        this.error = errors.join('. ')
      }

      return {
        success: successCount > 0,
        uploadedCount: successCount,
        totalAttempted: imagesToUpload.length,
        message: successCount === imagesToUpload.length
          ? `Successfully uploaded ${successCount} images`
          : `Uploaded ${successCount} of ${imagesToUpload.length} images`,
        errors: errors
      }
    },

    initializeExistingImages(images = this.existingImages) {
      if (!images || images.length === 0) return

      // Clear current images and add existing ones
      this.images = []

      images.forEach(existingImage => {
        // Use the URL provided by API or generate our own
        const preview = existingImage.url ||
          `/backend/api/serve-image.php?filename=${existingImage.filename}&token=${encodeURIComponent(localStorage.getItem('token'))}`

        // Add existing image in component format
        this.images.push({
          file: null, // No file object for existing images
          name: existingImage.original_filename,
          size: existingImage.file_size || 0,
          type: existingImage.mime_type || '',
          preview: preview,
          existing: true, // Mark as existing image
          id: existingImage.id, // Keep ID for potential deletion
          filename: existingImage.filename // Keep filename for reference
        })
      })
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

<style scoped>
/* Material Icons CSS Classes */
.material-icon-lg {
  font-family: 'Material Symbols Outlined', sans-serif;
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
}

.material-icon-sm {
  font-family: 'Material Symbols Outlined', sans-serif;
  font-weight: normal;
  font-style: normal;
  font-size: 18px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
}

.material-icon-xs {
  font-family: 'Material Symbols Outlined', sans-serif;
  font-weight: normal;
  font-style: normal;
  font-size: 14px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
}
</style>
