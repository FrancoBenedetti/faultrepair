<template>
  <div class="edit-job-page min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
          <!-- Breadcrumb Navigation -->
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <router-link
              :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
              class="text-blue-600 hover:text-blue-800"
              @click="returnToDashboard"
            >
              {{ from === 'service-provider' ? 'Service Provider Dashboard' : 'Client Dashboard' }}
            </router-link>
            <span class="text-gray-400">></span>
            <span class="text-gray-900 font-medium">
              Edit Job: {{ job?.item_identifier || 'Loading...' }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button
              @click="cancelEditing"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
              :disabled="saving"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="text-center">
          <div class="loading-spinner w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p class="text-gray-600">Loading job details...</p>
        </div>
      </div>

      <!-- Job Edit Form -->
      <form v-else-if="job" @submit.prevent="handleFormSubmit" class="space-y-8">
        <!-- Job Origin Area (Read-Only for Reported Jobs) -->
        <div v-if="job.job_status === 'Reported'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="job-origin-area">
            <div class="origin-header">
              <span class="material-icon user-icon">person</span>
              <div class="origin-info">
                <div class="origin-text">
                  Reported by {{ getReportedByFullName() }} on {{ formatDate(job.created_at) }}
                </div>
                <div class="origin-status">Status: {{ job.job_status }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Sections Container -->
        <div class="edit-sections space-y-8">
          <!-- Job Details Section -->
          <div v-if="job.job_status === 'Reported' || canEditJobDetails" class="job-section job-details-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="section-header mb-6">
              <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
                <span class="material-icon section-icon text-blue-600">edit_note</span>
                Job Details
              </h3>
            </div>

            <div class="section-content">
              <div class="form-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="form-group">
                  <label for="item-identifier" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">build</span>
                    Item Identifier <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="item-identifier"
                    type="text"
                    v-model="editableJob.item_identifier"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    maxlength="100"
                    placeholder="Enter item identifier..."
                    required
                  />
                </div>

                <div v-if="userRole === 2" class="form-group">
                  <label for="location-select" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">location_on</span>
                    Location <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="location-select"
                    v-model="selectedLocationId"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loadingLocations"
                  >
                    <option value="">{{ loadingLocations ? 'Loading...' : '-- Select Location --' }}</option>
                    <option v-for="location in availableLocations" :key="location.id" :value="location.id">
                      {{ location.name }}
                      <span v-if="location.address" class="location-address"> – {{ location.address }}</span>
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="contact-person" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">contact_mail</span>
                    Contact Person
                  </label>
                  <input
                    id="contact-person"
                    type="text"
                    v-model="editableJob.contact_person"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    maxlength="100"
                    placeholder="Contact person for this job..."
                  />
                </div>

                <div class="form-group lg:col-span-2">
                  <label for="fault-description" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">description</span>
                    Fault Description <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="fault-description"
                    v-model="editableJob.fault_description"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="4"
                    maxlength="1000"
                    placeholder="Describe the fault or issue in detail..."
                    required
                  ></textarea>
                </div>
              </div>

              <!-- Image Upload Area -->
              <div class="image-upload-area mt-8">
                <div class="border-t border-gray-200 pt-6">
                  <h4 class="text-lg font-semibold text-gray-900 mb-4">Images</h4>
                  <ImageUpload
                    ref="imageUpload"
                    :max-images="10"
                    :max-file-size="10 * 1024 * 1024"
                    :existing-images="existingImages"
                    @images-changed="handleImagesChanged"
                  />
                </div>
              </div>

              <!-- Section Actions -->
              <div class="section-actions flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="saveAndContinue('job-details')"
                  :disabled="saving"
                  class="btn-filled bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  Save & Continue
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="btn-outlined border-gray-400 text-gray-600 px-4 py-2.5 rounded-lg font-medium hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2"
                >
                  Save & Close
                </button>
              </div>
            </div>
          </div>

          <!-- Assignment & Status Section (Placeholder for now) -->
          <div class="job-section status-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-center text-gray-500">
              <!-- Additional sections will be added as needed -->
              <p>This section will include job status management and service provider assignment.</p>
            </div>
          </div>
        </div>
      </form>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-16">
        <div class="error-icon mx-auto w-16 h-16 flex items-center justify-center bg-red-100 rounded-full mb-4">
          <span class="material-icon text-red-600">error</span>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Error Loading Job</h3>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <button
          @click="returnToDashboard"
          class="btn-filled bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
        >
          Return to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'
import ImageUpload from '@/components/ImageUpload.vue'

export default {
  name: 'EditJob',
  components: {
    ImageUpload
  },
  props: {
    jobId: {
      type: Number,
      required: true
    },
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
      job: null,
      loading: true,
      saving: false,
      error: null,
      editableJob: {},
      existingImages: [],
      newImages: [],
      availableLocations: [],
      loadingLocations: false,
      selectedLocationId: '',
      userRole: null
    }
  },
  async mounted() {
    await this.getUserRole()
    await this.loadJob()
  },
  methods: {
    async getUserRole() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          this.userRole = payload.role_id
        }
      } catch (error) {
        this.error = 'Authentication error'
      }
    },

    async loadJob() {
      try {
        const response = await apiFetch(`/backend/api/${this.from === 'service-provider' ? 'service-provider-jobs.php' : 'client-jobs.php'}?id=${this.jobId}`)

        if (response.ok) {
          const data = await response.json()
          this.job = data.jobs && data.jobs.length > 0 ? data.jobs[0] : null
          if (!this.job) {
            throw new Error('Job not found')
          }

          // Initialize editable data
          this.editableJob = { ...this.job }

          // Load images if in Reported status
          if (this.job.job_status === 'Reported') {
            await this.loadExistingImages()
            await this.loadLocations()
          }
        } else {
          throw new Error('Failed to load job')
        }
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async loadExistingImages() {
      try {
        const response = await apiFetch(`/backend/api/job-images.php?job_id=${this.jobId}`)
        if (response.ok) {
          const data = await response.json()
          this.existingImages = data.images || []
        }
      } catch (error) {
        console.error('Failed to load existing images:', error)
      }
    },

    async loadLocations() {
      if (this.userRole !== 2) return

      this.loadingLocations = true
      try {
        const response = await apiFetch('/backend/api/client-locations.php')

        if (response.ok) {
          const data = await response.json()
          this.availableLocations = data.locations || []

          // Set current location if job has one
          if (this.job.client_location_id) {
            this.selectedLocationId = this.job.client_location_id.toString()
          }
        }
      } catch (error) {
        console.error('Error loading locations:', error)
      } finally {
        this.loadingLocations = false
      }
    },

    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getReportedByFullName() {
      if (!this.job) return 'Unknown User'

      if (this.job.reporting_user_full_name) {
        return this.job.reporting_user_full_name
      }
      if (this.job.reporting_user_first_name && this.job.reporting_user_last_name) {
        return `${this.job.reporting_user_first_name} ${this.job.reporting_user_last_name}`
      }
      if (this.job.reporting_user_name) {
        return this.job.reporting_user_name
      }
      return this.job.reporting_user || 'Unknown User'
    },

    handleImagesChanged(images) {
      this.newImages = images
    },

    async handleFormSubmit() {
      await this.saveAndClose('final')
    },

    async saveAndContinue(sectionType) {
      // Implementation to be migrated from modal
      console.log('Save and continue:', sectionType)
      // Will implement section-based save logic
    },

    async saveAndClose(sectionType) {
      this.saving = true

      try {
        // Save job details
        if (sectionType === 'job-details' || sectionType === 'final') {
          await this.saveJobDetails()
        }

        // Save images
        await this.saveImageChanges()

        // Return to dashboard with scroll position
        await this.returnToDashboard()

        // Show success notification
        this.$router.push({
          path: this.from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
          query: { success: 'Job updated successfully' }
        })

      } catch (error) {
        console.error('Save failed:', error)
        // Show error notification
        alert('Failed to save changes: ' + (error.message || error))
      } finally {
        this.saving = false
      }
    },

    async saveJobDetails() {
      const updateData = {
        job_id: this.job.id
      }

      // Only include changed fields
      const jobFields = ['item_identifier', 'contact_person', 'fault_description']
      jobFields.forEach(field => {
        if (this.editableJob[field] !== this.job[field]) {
          updateData[field] = this.editableJob[field] || null
        }
      })

      // Add location if for Role 2
      if (this.userRole === 2 && this.selectedLocationId) {
        updateData.client_location_id = this.selectedLocationId
      }

      if (Object.keys(updateData).length > 1) {
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(updateData)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to update job details')
        }
      }
    },

    async saveImageChanges() {
      if (!this.$refs.imageUpload) return

      const result = await this.$refs.imageUpload.uploadImages(this.job.id)
      if (!result || !result.success) {
        throw new Error(result?.error || 'Failed to upload images')
      }
    },

    cancelEditing() {
      if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
        this.returnToDashboard()
      }
    },

    async returnToDashboard() {
      // Store scroll position for return
      const routeQuery = {
        scroll: this.scrollPosition || '0'
      }

      // Navigate back to originating dashboard
      await this.$router.push({
        path: this.from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
        query: routeQuery
      })
    },

    computed: {
      canEditJobDetails() {
        if (!this.job) return false

        if (this.job.job_status === 'Reported') {
          return true
        }

        // Role 2 can edit all reported jobs, Role 1 can only edit their own jobs
        if (this.userRole === 2) {
          return true
        }

        return false
      }
    }
  }
}
</script>

<style scoped>
.edit-job-page {
  /* Full-screen responsive layout */
}

.form-input:focus {
  outline: none;
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

/* Loading Spinner */
.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Form Styles */
.form-label {
  color: #374151;
  font-weight: 500;
}

.field-icon,
.section-icon {
  font-size: 18px;
}

.section-icon {
  color: #3B82F6;
}

.btn-filled {
  @apply bg-blue-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors;
}

.btn-outlined {
  @apply bg-transparent text-gray-600 border border-gray-300 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors;
}

/* Responsive Design */
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

  .lg\\:col-span-2 {
    @apply col-span-1;
  }
}
</style>
