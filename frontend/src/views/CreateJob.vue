<template>
  <div class="create-job-page min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
          <!-- Breadcrumb Navigation -->
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <router-link
              :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
              class="text-blue-600 hover:text-blue-800"
              @click="cancelCreating"
            >
              {{ from === 'service-provider' ? 'Service Provider Dashboard' : 'Client Dashboard' }}
            </router-link>
            <span class="text-gray-400">></span>
            <span class="text-gray-900 font-medium">Create New Service Request</span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button
              @click="cancelCreating"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
              :disabled="creatingJob"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="!locations || loading" class="flex items-center justify-center py-16">
        <div class="text-center">
          <div class="loading-spinner w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p class="text-gray-600">Loading form data...</p>
        </div>
      </div>

      <!-- Job Creation Form -->
      <div v-else class="space-y-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center space-x-3 mb-4">
            <span class="material-icon text-blue-600">add_box</span>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">New Service Request</h1>
              <p class="text-gray-600">Snap a QR code, take a picture and detail the issue to keep track of your service requests.</p>
            </div>
          </div>
        </div>

        <!-- Main Form -->
        <form @submit.prevent="handleSubmit" class="space-y-8">

          <!-- Item and Location Section -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-3">
              <span class="material-icon text-blue-600">build</span>
              Item Details
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Item Identifier -->
              <div class="space-y-2">
                <label for="item-identifier" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">label</span>
                  Item Identifier *
                </label>
                <div class="flex gap-2">
                  <input
                    type="text"
                    id="item-identifier"
                    v-model="newJob.item_identifier"
                    required
                    class="flex-1 form-input"
                    placeholder="e.g., Computer-001, Printer-ABC"
                    maxlength="100"
                  />
                  <QrScanner
                    :client-id="getClientId()"
                    @qr-detected="handleQrDetected"
                    class="flex-shrink-0"
                  />
                </div>
                <p class="text-xs text-gray-500">Unique identifier for the item (scan QR code or enter manually)</p>
              </div>

              <!-- Location -->
              <div class="space-y-2">
                <label for="location" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">location_on</span>
                  Location
                  <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="location" v-model="newJob.client_location_id" class="form-input">
                  <option value="0">Default Location (Client Premises)</option>
                  <option v-for="location in locations" :key="location.id" :value="location.id">
                    {{ location.name }}
                    <span v-if="location.address" class="text-gray-500"> – {{ location.address }}</span>
                  </option>
                </select>
                <p class="text-xs text-gray-500">Can be auto-filled from QR code. If no custom locations defined, this service request will be associated with your client name.</p>
              </div>
            </div>
          </div>

          <!-- Description Section -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-3">
              <span class="material-icon text-green-600">description</span>
              Service Description
            </h2>

            <div class="space-y-6">
              <!-- Fault Description -->
              <div class="space-y-2">
                <label for="fault-description" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">report_problem</span>
                  Service Description *
                </label>
                <textarea
                  id="fault-description"
                  v-model="newJob.fault_description"
                  required
                  rows="6"
                  class="form-input resize-none"
                  placeholder="Describe the service request in detail. What is the problem? What are the symptoms? When did it start?"
                  maxlength="1000"
                ></textarea>
                <p class="text-xs text-gray-500">{{ newJob.fault_description.length }}/1000 characters</p>
              </div>

              <!-- Contact Person -->
              <div class="space-y-2">
                <label for="contact-person" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">contact_mail</span>
                  Contact Person
                  <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input
                  type="text"
                  id="contact-person"
                  v-model="newJob.contact_person"
                  class="form-input"
                  placeholder="Person to contact about this service request"
                  maxlength="100"
                />
                <p class="text-xs text-gray-500">Who should the technician contact?</p>
              </div>
            </div>
          </div>

          <!-- Image Upload Section -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-3">
              <span class="material-icon text-purple-600">photo_camera</span>
              Supporting Images
              <span class="text-xs text-gray-500">(Optional)</span>
            </h2>

            <ImageUpload
              ref="imageUpload"
              :max-images="10"
              :max-file-size="10 * 1024 * 1024"
              @images-changed="handleImagesChanged"
            />

            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-start gap-3">
                <span class="material-icon text-blue-600 mt-0.5">info</span>
                <div>
                  <p class="text-sm text-blue-800 font-medium mb-1">Help technicians help you</p>
                  <p class="text-sm text-blue-700">Upload clear images of the issue, error messages, or affected equipment. This helps service providers prepare better and provides faster service.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col sm:flex-row lg:justify-end gap-3">
              <router-link
                :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
                class="btn btn-secondary order-2 sm:order-1"
              >
                Cancel
              </router-link>
              <button
                type="submit"
                class="btn btn-primary order-1 sm:order-2 flex items-center gap-2"
                :disabled="creatingJob"
              >
                <span v-if="creatingJob" class="material-icon-sm animate-spin">refresh</span>
                <span v-else class="material-icon-sm">send</span>
                {{ creatingJob ? 'Creating Service Request...' : 'Create Service Request' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import ImageUpload from '@/components/ImageUpload.vue'
import QrScanner from '@/components/QrScanner.vue'
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'CreateJob',
  components: {
    ImageUpload,
    QrScanner
  },
  props: {
    from: {
      type: String,
      default: 'client'
    },
    scrollPosition: {
      type: [String, Number],
      default: 0
    }
  },
  data() {
    return {
      loading: true,
      creatingJob: false,
      locations: [],
      selectedImages: [],
      newJob: {
        item_identifier: '',
        client_location_id: '0',
        fault_description: '',
        contact_person: ''
      }
    }
  },
  async mounted() {
    await this.loadLocations()
  },
  methods: {
    async loadLocations() {
      try {
        this.loading = true
        // Use client API endpoint for locations regardless of 'from' prop
        // Service providers creating jobs should have their clients' settings
        const apiEndpoint = this.from === 'service-provider' ? '/client-locations.php' : '/client-locations.php'

        const response = await apiFetch(`/backend/api${apiEndpoint}`)

        if (response.ok) {
          const data = await response.json()
          this.locations = data.locations || []
        } else {
          console.error('Failed to load locations')
          this.locations = []
        }
      } catch (error) {
        console.error('Error loading locations:', error)
        this.locations = []
      } finally {
        this.loading = false
      }
    },

    handleQrDetected(qrData) {
      console.log('QR data detected:', qrData)

      try {
        // First, try to validate client ID if present
        if (qrData.clientId && qrData.clientId !== this.getClientId()) {
          alert(`QR code is for a different client (ID: ${qrData.clientId}). This QR code is not valid for your account.`)
          return
        }

        // Check if structured data was successfully parsed
        if (qrData.itemIdentifier && qrData.itemIdentifier.trim()) {
          this.newJob.item_identifier = qrData.itemIdentifier.trim()
        } else {
          // No structured item data - check if we have any location data
          if (qrData.locationName && qrData.locationName.trim()) {
            this.newJob.item_identifier = qrData.locationName.trim()
          } else {
            // No structured data at all - check if qrData is available as string
            const rawQrString = arguments[0]
            if (rawQrString && typeof rawQrString === 'string' && rawQrString.trim()) {
              this.newJob.item_identifier = rawQrString.trim()
            } else {
              return
            }
          }
        }

        // Handle location matching
        if (qrData.locationName && qrData.locationName.trim() && this.locations && this.locations.length > 0) {
          const matchingLocation = this.locations.find(location =>
            location.name.toLowerCase() === qrData.locationName.toLowerCase()
          )
          if (matchingLocation) {
            this.newJob.client_location_id = matchingLocation.id.toString()
          }
        }

        alert('QR code scanned successfully! Item identifier and location have been filled in.')
      } catch (error) {
        console.error('Error processing QR data:', error)
        alert('Error reading QR code. Please enter the item identifier manually.')
      }
    },

    handleImagesChanged(images) {
      this.selectedImages = images
    },

    async handleSubmit() {
      this.creatingJob = true

      try {
        // First create the job
        const jobData = {
          client_location_id: this.newJob.client_location_id === '0' ? null : this.newJob.client_location_id,
          item_identifier: this.newJob.item_identifier || null,
          fault_description: this.newJob.fault_description,
          contact_person: this.newJob.contact_person || null
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'POST',
          body: JSON.stringify(jobData)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to create job')
        }

        const result = await response.json()
        const jobId = result.job_id

        // Handle image uploads if any
        if (this.selectedImages.length > 0) {
          await this.uploadImages(jobId)
        }

        // Success notification and navigation
        alert('Service request created successfully!')

        // Navigate back to dashboard
        await this.returnToDashboard()

      } catch (error) {
        alert('Failed to create service request: ' + (error.message || error))
      } finally {
        this.creatingJob = false
      }
    },

    async uploadImages(jobId) {
      if (!this.$refs.imageUpload) return

      try {
        let successCount = 0
        const token = localStorage.getItem('token')

        for (let i = 0; i < this.selectedImages.length; i++) {
          const image = this.selectedImages[i]
          const formData = new FormData()
          formData.append('image', image.file)
          formData.append('job_id', jobId.toString())

          const response = await fetch(`/backend/api/upload-job-image.php?token=${encodeURIComponent(token)}`, {
            method: 'POST',
            body: formData
          })

          if (response.ok) {
            successCount++
          } else {
            console.error('Image upload failed:', response.status)
          }
        }

        if (successCount !== this.selectedImages.length) {
          alert(`Job created successfully! However, only ${successCount} of ${this.selectedImages.length} images were uploaded.`)
        }
      } catch (error) {
        console.error('Image upload error:', error)
        // Don't fail the job creation for image upload errors
        alert('Job created successfully, but image upload failed. You can try uploading images again by editing the job.')
      }
    },

    cancelCreating() {
      if (confirm('Are you sure you want to cancel creating this service request? Any entered information will be lost.')) {
        this.returnToDashboard()
      }
    },

    async returnToDashboard() {
      const routeQuery = {
        scroll: this.scrollPosition || '0'
      }

      // Navigate back to originating dashboard
      await this.$router.push({
        path: this.from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
        query: routeQuery
      })
    },

    getClientId() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.entity_id
        }
      } catch (error) {
        console.error('Failed to get client ID from token:', error)
      }
      return null
    }
  }
}
</script>

<style scoped>
.create-job-page {
  /* Full-screen responsive layout */
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
  background: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
  display: block;
}

.btn {
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
  border-color: #2563eb;
}

.btn-secondary {
  background: white;
  color: #6b7280;
  border-color: #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Material Icons */
.material-icon {
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
  font-size: 18px;
}

@media (max-width: 768px) {
  .max-w-4xl {
    @apply max-w-full;
  }

  .max-w-7xl {
    @apply max-w-full;
  }

  .px-6 {
    @apply px-4;
  }
}
</style>
