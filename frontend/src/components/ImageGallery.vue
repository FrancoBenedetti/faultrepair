<template>
  <div class="w-full">
    <!-- No Images State -->
    <div v-if="images.length === 0" class="text-center py-16">
      <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
        <span class="material-icon text-gray-400">photo_camera</span>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">No Images Attached</h3>
      <p class="text-gray-600">No images have been attached to this job yet.</p>
    </div>

    <!-- Image Gallery -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
      <div
        v-for="image in images"
        :key="image.id"
        class="group relative bg-white border border-gray-200 rounded-lg overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
        @click="openLightbox(image)"
      >
        <div class="aspect-square overflow-hidden">
          <img
            :src="image.url"
            :alt="image.original_filename"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
          >
        </div>
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-3">
          <div class="text-white text-xs">
            <div class="font-medium truncate">{{ image.original_filename }}</div>
            <div class="opacity-90">{{ formatDate(image.uploaded_at) }}</div>
            <div class="opacity-75">by {{ image.uploaded_by }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <div v-if="lightboxImage" class="fixed inset-0 z-50 flex items-center justify-center" @click="closeLightbox">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/90"></div>

      <!-- Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-start p-6 border-b border-gray-200">
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ lightboxImage.original_filename }}</h3>
            <p class="text-sm text-gray-600 mt-1">
              Uploaded {{ formatDate(lightboxImage.uploaded_at) }} by {{ lightboxImage.uploaded_by }}
              ({{ formatFileSize(lightboxImage.file_size) }})
            </p>
          </div>
          <div class="flex items-center gap-2 ml-4">
            <button @click="downloadImage(lightboxImage)" class="btn-outlined btn-small flex items-center gap-1">
              <span class="material-icon-sm">download</span>
              Download
            </button>
            <button v-if="canDelete" @click="deleteImage(lightboxImage)" class="btn-filled btn-small flex items-center gap-1 bg-red-600 hover:bg-red-700">
              <span class="material-icon-sm">delete</span>
              Delete
            </button>
            <button @click="closeLightbox" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
          </div>
        </div>

        <!-- Image Body -->
        <div class="p-6 flex justify-center items-center min-h-0 flex-1">
          <img
            :src="lightboxImage.url"
            :alt="lightboxImage.original_filename"
            class="max-w-full max-h-[60vh] object-contain rounded-lg"
          >
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center p-6 border-t border-gray-200 bg-gray-50">
          <button
            @click="previousImage"
            class="btn-outlined flex items-center gap-2"
            :disabled="currentImageIndex === 0"
          >
            <span class="material-icon-sm">chevron_left</span>
            Previous
          </button>
          <span class="text-sm text-gray-600 font-medium">
            {{ currentImageIndex + 1 }} of {{ images.length }}
          </span>
          <button
            @click="nextImage"
            class="btn-outlined flex items-center gap-2"
            :disabled="currentImageIndex === images.length - 1"
          >
            Next
            <span class="material-icon-sm">chevron_right</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="cancelDelete"></div>

      <!-- Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Delete Image</h3>
        </div>

        <!-- Body -->
        <div class="p-6">
          <p class="text-gray-700">Are you sure you want to delete this image? This action cannot be undone.</p>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
          <button @click="cancelDelete" class="btn-outlined">Cancel</button>
          <button
            @click="confirmDelete"
            class="btn-filled bg-red-600 hover:bg-red-700 flex items-center gap-2"
            :disabled="deleting"
          >
            <span v-if="deleting" class="material-icon-sm animate-spin">refresh</span>
            <span v-else class="material-icon-sm">delete</span>
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ImageGallery',
  props: {
    images: {
      type: Array,
      default: () => []
    },
    jobId: {
      type: Number,
      required: true
    },
    canDelete: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      lightboxImage: null,
      currentImageIndex: 0,
      showDeleteConfirm: false,
      imageToDelete: null,
      deleting: false
    }
  },
  methods: {
    openLightbox(image) {
      this.lightboxImage = image
      this.currentImageIndex = this.images.findIndex(img => img.id === image.id)
    },

    closeLightbox() {
      this.lightboxImage = null
      this.currentImageIndex = 0
    },

    previousImage() {
      if (this.currentImageIndex > 0) {
        this.currentImageIndex--
        this.lightboxImage = this.images[this.currentImageIndex]
      }
    },

    nextImage() {
      if (this.currentImageIndex < this.images.length - 1) {
        this.currentImageIndex++
        this.lightboxImage = this.images[this.currentImageIndex]
      }
    },

    downloadImage(image) {
      const link = document.createElement('a')
      link.href = image.url
      link.download = image.original_filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    },

    deleteImage(image) {
      this.imageToDelete = image
      this.showDeleteConfirm = true
    },

    cancelDelete() {
      this.showDeleteConfirm = false
      this.imageToDelete = null
    },

    async confirmDelete() {
      if (!this.imageToDelete) return

      this.deleting = true
      const token = localStorage.getItem('token')

      try {
        const response = await fetch(`/backend/api/job-images.php?image_id=${this.imageToDelete.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        if (response.ok) {
          // Remove image from local array
          const index = this.images.findIndex(img => img.id === this.imageToDelete.id)
          if (index !== -1) {
            this.images.splice(index, 1)
          }

          // Close lightbox if the deleted image was being viewed
          if (this.lightboxImage && this.lightboxImage.id === this.imageToDelete.id) {
            this.closeLightbox()
          }

          this.$emit('image-deleted', this.imageToDelete.id)
        } else {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to delete image')
        }
      } catch (error) {
        console.error('Delete error:', error)
        alert('Failed to delete image: ' + error.message)
      } finally {
        this.deleting = false
        this.showDeleteConfirm = false
        this.imageToDelete = null
      }
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }
  },

  mounted() {
    // Add keyboard navigation for lightbox
    document.addEventListener('keydown', this.handleKeydown)
  },

  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeydown)
  },

  methods: {
    ...this.methods,
    handleKeydown(event) {
      if (!this.lightboxImage) return

      switch (event.key) {
        case 'ArrowLeft':
          event.preventDefault()
          this.previousImage()
          break
        case 'ArrowRight':
          event.preventDefault()
          this.nextImage()
          break
        case 'Escape':
          event.preventDefault()
          this.closeLightbox()
          break
      }
    }
  }
}
</script>
