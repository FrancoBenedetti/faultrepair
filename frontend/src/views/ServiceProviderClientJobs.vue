<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Page Header -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ client.name }}</h1>
            <p class="text-gray-600 flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">location_on</span>
              {{ client.address }}
            </p>
          </div>
          <button @click="$router.go(-1)" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">arrow_back</span>
            Back to Dashboard
          </button>
        </div>
      </div>

      <!-- Jobs Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 mb-6 pb-4 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">work</span>
            Jobs for {{ client.name }}
          </h2>
          <div class="flex gap-4">
            <div class="text-center">
              <div class="text-lg font-bold text-gray-900">{{ jobs.length }}</div>
              <div class="text-xs text-gray-600">Total Jobs</div>
            </div>
            <div class="text-center">
              <div class="text-lg font-bold text-blue-600">{{ activeJobsCount }}</div>
              <div class="text-xs text-gray-600">Active</div>
            </div>
            <div class="text-center">
              <div class="text-lg font-bold text-green-600">{{ completedJobsCount }}</div>
              <div class="text-xs text-gray-600">Completed</div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-16">
          <div class="h-16 w-16 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <span class="material-icon text-gray-400 animate-spin">refresh</span>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Loading Jobs</h3>
          <p class="text-gray-600">Please wait while we fetch the job information...</p>
        </div>

        <!-- No Jobs -->
        <div v-else-if="jobs.length === 0" class="text-center py-16">
          <div class="h-16 w-16 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <span class="material-icon text-gray-400">work_off</span>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No Jobs Found</h3>
          <p class="text-gray-600">No jobs have been assigned to you for this client yet.</p>
        </div>

        <!-- Jobs List -->
        <div v-else class="space-y-6">
          <div v-for="job in jobs" :key="job.id" class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow bg-white">
            <!-- Job Header -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xl font-semibold text-gray-900">{{ job.item_identifier || 'No Item ID' }}</h3>
                    <span :class="[
                      'px-3 py-1 rounded-full text-sm font-medium',
                      job.job_status === 'Reported' ? 'bg-blue-100 text-blue-800' :
                      job.job_status === 'Assigned' ? 'bg-pink-100 text-pink-800' :
                      job.job_status === 'In Progress' ? 'bg-purple-100 text-purple-800' :
                      job.job_status === 'Completed' ? 'bg-gray-100 text-gray-800' :
                      job.job_status === 'On Hold' ? 'bg-red-100 text-red-800' :
                      'bg-gray-100 text-gray-800'
                    ]">
                      {{ job.job_status }}
                    </span>
                  </div>
                  <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span class="flex items-center gap-1">
                      <span class="material-icon-sm">location_on</span>
                      {{ job.location_name }}
                    </span>
                    <span class="flex items-center gap-1">
                      <span class="material-icon-sm">event</span>
                      {{ formatDate(job.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Job Content -->
            <div class="p-6">
              <div class="mb-4">
                <h4 class="text-lg font-medium text-gray-900 mb-2 flex items-center gap-2">
                  <span class="material-icon-sm text-blue-600">description</span>
                  Fault Description
                </h4>
                <p class="text-gray-700">{{ job.fault_description }}</p>
              </div>

              <div v-if="job.technician_notes" class="mb-4">
                <h4 class="text-lg font-medium text-gray-900 mb-2 flex items-center gap-2">
                  <span class="material-icon-sm text-green-600">engineering</span>
                  Technician Notes
                </h4>
                <p class="text-gray-700">{{ job.technician_notes }}</p>
              </div>

              <!-- Job Details Grid -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 pt-4 border-t border-gray-200">
                <div>
                  <span class="text-sm font-medium text-gray-900">Reported by:</span>
                  <div class="text-sm text-gray-600">{{ job.reporting_user || 'Unknown' }}</div>
                </div>
                <div v-if="job.assigned_technician">
                  <span class="text-sm font-medium text-gray-900">Assigned Technician:</span>
                  <div class="text-sm text-gray-600">{{ job.assigned_technician }}</div>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-900">Last Updated:</span>
                  <div class="text-sm text-gray-600">{{ formatDate(job.updated_at) }}</div>
                </div>
              </div>

              <!-- Job Actions -->
              <div class="flex justify-end gap-3">
                <button @click="showJobDetails(job)" class="btn-filled flex items-center gap-2">
                  <span class="material-icon-sm">visibility</span>
                  View Details
                </button>
                <button v-if="canUpdateStatus(job)" @click="showStatusModal(job)" class="btn-text flex items-center gap-2">
                  <span class="material-icon-sm">update</span>
                  Update Status
                </button>
                <!-- Archive/Unarchive button for service provider admins -->
                <button @click="toggleArchiveJob(job)" class="btn-text flex items-center gap-2" :class="{ 'text-orange-600': job.archived_by_service_provider }">
                  <span class="material-icon-sm">{{ job.archived_by_service_provider ? 'unarchive' : 'archive' }}</span>
                  {{ job.archived_by_service_provider ? 'Unarchive' : 'Archive' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Job Details Modal -->
      <div v-if="selectedJob" class="modal-overlay" @click="closeJobDetails">
        <div class="modal-content" @click.stop>
          <!-- Modal Header -->
          <div class="modal-header">
            <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
              <span class="material-icon text-blue-600">work</span>
              Job Details - {{ selectedJob.item_identifier || 'No Item ID' }}
            </h3>
            <button @click="closeJobDetails" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <!-- Job Status Badge -->
            <div class="mb-6">
              <span :class="[
                'px-3 py-1 rounded-full text-sm font-medium',
                selectedJob.job_status === 'Reported' ? 'bg-blue-100 text-blue-800' :
                selectedJob.job_status === 'Assigned' ? 'bg-pink-100 text-pink-800' :
                selectedJob.job_status === 'In Progress' ? 'bg-purple-100 text-purple-800' :
                selectedJob.job_status === 'Completed' ? 'bg-gray-100 text-gray-800' :
                selectedJob.job_status === 'On Hold' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
              ]">
                {{ selectedJob.job_status }}
              </span>
            </div>

            <!-- Job Information Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
              <!-- Job Information -->
              <div>
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <span class="material-icon-sm text-blue-600">info</span>
                  Job Information
                </h4>
                <div class="space-y-3">
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Location:</span>
                    <span class="text-gray-600">{{ selectedJob.location_name }}</span>
                  </div>
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Address:</span>
                    <span class="text-gray-600">{{ selectedJob.location_address }}</span>
                  </div>
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Reported:</span>
                    <span class="text-gray-600">{{ formatDate(selectedJob.created_at) }}</span>
                  </div>
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Last Updated:</span>
                    <span class="text-gray-600">{{ formatDate(selectedJob.updated_at) }}</span>
                  </div>
                </div>
              </div>

              <!-- People Involved -->
              <div>
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <span class="material-icon-sm text-green-600">group</span>
                  People Involved
                </h4>
                <div class="space-y-3">
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Client:</span>
                    <span class="text-gray-600">{{ selectedJob.client_name }}</span>
                  </div>
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Reported by:</span>
                    <span class="text-gray-600">{{ selectedJob.reporting_user || 'Unknown' }}</span>
                  </div>
                  <div v-if="selectedJob.assigned_technician" class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-900">Assigned Technician:</span>
                    <span class="text-gray-600">{{ selectedJob.assigned_technician }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fault Description -->
            <div class="mb-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <span class="material-icon-sm text-blue-600">description</span>
                Fault Description
              </h4>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700">{{ selectedJob.fault_description }}</p>
              </div>
            </div>

            <!-- Technician Notes -->
            <div v-if="selectedJob.technician_notes" class="mb-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <span class="material-icon-sm text-green-600">engineering</span>
                Technician Notes
              </h4>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700">{{ selectedJob.technician_notes }}</p>
              </div>
            </div>

            <!-- Attached Images -->
            <div v-if="jobImages[selectedJob.id] && jobImages[selectedJob.id].length > 0" class="mb-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="material-icon-sm text-orange-600">image</span>
                Attached Images ({{ jobImages[selectedJob.id].length }})
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="image in jobImages[selectedJob.id]" :key="image.id" class="relative group">
                  <div class="aspect-w-16 aspect-h-12 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                    <img
                      :src="image.url"
                      :alt="image.original_filename"
                      class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform"
                      @click="openImageModal(image)"
                    >
                  </div>
                  <div class="mt-2 text-sm text-gray-600">
                    <div class="font-medium truncate">{{ image.original_filename }}</div>
                    <div class="text-xs">{{ formatFileSize(image.file_size) }} • {{ formatDate(image.uploaded_at) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Status History -->
            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="material-icon-sm text-purple-600">history</span>
                Status History
              </h4>
              <div v-if="selectedJob.status_history && selectedJob.status_history.length > 0" class="space-y-3">
                <div v-for="history in selectedJob.status_history" :key="history.changed_at" class="flex items-start gap-4 p-3 bg-gray-50 rounded-lg">
                  <div class="w-3 h-3 bg-blue-600 rounded-full mt-2 flex-shrink-0"></div>
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ history.status }}</div>
                    <div class="text-sm text-gray-600">
                      {{ formatDate(history.changed_at) }}
                      <span v-if="history.changed_by">by {{ history.changed_by }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <span class="material-icon text-4xl text-gray-300">history</span>
                <p class="mt-2">No status history available</p>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button @click="closeJobDetails" class="btn-filled">Close</button>
          </div>
        </div>
      </div>

      <!-- Status Update Modal -->
      <div v-if="statusUpdateJob" class="modal-overlay" @click="closeStatusModal">
        <div class="modal-content" @click.stop style="max-width: 500px;">
          <!-- Modal Header -->
          <div class="modal-header">
            <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
              <span class="material-icon text-blue-600">update</span>
              Update Job Status
            </h3>
            <button @click="closeStatusModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <!-- Current Status Display -->
            <div class="p-4 mb-6 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="font-medium text-gray-900">Current Status:</span>
                <span :class="[
                  'px-3 py-1 rounded-full text-sm font-medium',
                  statusUpdateJob.job_status === 'Reported' ? 'bg-blue-100 text-blue-800' :
                  statusUpdateJob.job_status === 'Assigned' ? 'bg-pink-100 text-pink-800' :
                  statusUpdateJob.job_status === 'In Progress' ? 'bg-purple-100 text-purple-800' :
                  statusUpdateJob.job_status === 'Completed' ? 'bg-gray-100 text-gray-800' :
                  statusUpdateJob.job_status === 'On Hold' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ statusUpdateJob.job_status }}
                </span>
              </div>
            </div>

            <!-- New Status Selection -->
            <div class="mb-6">
              <label for="newStatus" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">flag</span>
                New Status
              </label>
            <select id="newStatus" v-model="newStatus" class="form-input">
              <option v-if="canSelectStatus('Declined')" value="Declined">Declined</option>
              <option v-if="canSelectStatus('In Progress')" value="In Progress">In Progress</option>
              <option v-if="canSelectStatus('Quote Provided')" value="Quote Provided">Quote Provided</option>
              <option v-if="canSelectStatus('Repaired')" value="Repaired">Repaired</option>
              <option v-if="canSelectStatus('Payment Requested')" value="Payment Requested">Payment Requested</option>
            </select>
            </div>

            <!-- Technician Selection (only for In Progress) -->
            <div v-if="newStatus === 'In Progress'" class="mb-6">
              <label for="technician" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">engineering</span>
                Assign Technician *
              </label>
              <select id="technician" v-model="selectedTechnicianId" class="form-input">
                <option value="">Select a technician...</option>
                <option v-for="technician in technicians" :key="technician.id" :value="technician.id">
                  {{ technician.full_name }} ({{ technician.username }})
                </option>
              </select>
              <p v-if="!selectedTechnicianId" class="text-sm text-red-600 mt-1">
                A technician must be assigned before setting the job to "In Progress"
              </p>
            </div>

            <!-- Notes -->
            <div class="mb-6">
              <label for="notes" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">notes</span>
                Notes (Optional)
              </label>
              <textarea id="notes" v-model="statusNotes" rows="3" class="form-input resize-none" placeholder="Add any notes about this status change..."></textarea>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button @click="closeStatusModal" class="btn-text">Cancel</button>
            <button @click="updateJobStatus" class="btn-filled" :disabled="updatingStatus">
              <span v-if="updatingStatus" class="material-icon-sm animate-spin mr-2">refresh</span>
              {{ updatingStatus ? 'Updating...' : 'Update Status' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'ServiceProviderClientJobs',
  data() {
    return {
      client: {},
      jobs: [],
      loading: false,
      selectedJob: null,
      statusUpdateJob: null,
      newStatus: '',
      statusNotes: '',
      updatingStatus: false,
      userRole: null,
      technicians: [],
      selectedTechnicianId: null,
      jobImages: {} // Store images for each job
    }
  },
  computed: {
    activeJobsCount() {
      return this.jobs.filter(job =>
        ['Reported', 'Assigned', 'In Progress'].includes(job.job_status)
      ).length
    },
    completedJobsCount() {
      return this.jobs.filter(job => job.job_status === 'Completed').length
    }
  },
  mounted() {
    this.getUserRole()
    const clientId = this.$route.params.clientId
    if (clientId) {
      this.loadClientJobs(clientId)
    } else {
      this.$router.push('/service-provider/dashboard')
    }
  },
  methods: {
    async loadClientJobs(clientId) {
      this.loading = true
      try {
        const response = await apiFetch(`/backend/api/service-provider-client-jobs.php?client_id=${clientId}`)

        if (response.ok) {
          const data = await response.json()
          this.client = data.client
          this.jobs = data.jobs
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load client jobs')
      } finally {
        this.loading = false
      }
    },

    async showJobDetails(job) {
      this.selectedJob = { ...job }
      console.log('Loading images for job:', job.id)
      // Load images for this job
      await this.loadJobImages(job.id)
      console.log('Images loaded:', this.jobImages[job.id])
    },

    closeJobDetails() {
      this.selectedJob = null
    },

    async showStatusModal(job) {
      this.statusUpdateJob = { ...job }
      this.newStatus = job.job_status
      this.statusNotes = ''
      this.selectedTechnicianId = null

      // Load technicians if user is admin (only admins can assign technicians)
      if (this.userRole === 3) {
        await this.loadTechnicians()
      }
    },

    closeStatusModal() {
      this.statusUpdateJob = null
      this.newStatus = ''
      this.statusNotes = ''
      this.selectedTechnicianId = null
    },

    canUpdateStatus(job) {
      // Cannot update status of archived jobs
      if (job.archived_by_service_provider) {
        return false
      }

      // Allow status updates for active jobs
      return ['Reported', 'Assigned', 'In Progress', 'On Hold'].includes(job.job_status)
    },

    async updateJobStatus() {
      if (!this.newStatus) {
        alert('Please select a new status')
        return
      }

      // Validate technician assignment for "In Progress" status
      if (this.newStatus === 'In Progress' && !this.selectedTechnicianId) {
        alert('Please select a technician before setting the job to "In Progress"')
        return
      }

      this.updatingStatus = true
      try {
        const requestData = {
          job_id: this.statusUpdateJob.id,
          status: this.newStatus,
          notes: this.statusNotes
        }

        // Include technician assignment if setting to "In Progress"
        if (this.newStatus === 'In Progress' && this.selectedTechnicianId) {
          requestData.technician_id = this.selectedTechnicianId
        }

        const response = await apiFetch('/backend/api/job-status-update.php', {
          method: 'PUT',
          body: JSON.stringify(requestData)
        })

        if (response.ok) {
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === this.statusUpdateJob.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = this.newStatus
            this.jobs[jobIndex].updated_at = new Date().toISOString()

            // Update assigned technician if changed
            if (this.newStatus === 'In Progress' && this.selectedTechnicianId) {
              const selectedTech = this.technicians.find(t => t.id == this.selectedTechnicianId)
              if (selectedTech) {
                this.jobs[jobIndex].assigned_technician = selectedTech.full_name
              }
            }
          }

          this.closeStatusModal()
          alert('Job status updated successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update job status')
      } finally {
        this.updatingStatus = false
      }
    },

    getStatusClass(status) {
      const statusClasses = {
        'Reported': 'reported',
        'Assigned': 'assigned',
        'In Progress': 'in-progress',
        'Completed': 'completed',
        'On Hold': 'on-hold',
        'Cancelled': 'cancelled'
      }
      return statusClasses[status] || 'unknown'
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
    },

    openImageModal(image) {
      // For now, just open the image in a new tab
      // In a real application, you might want a modal with zoom functionality
      window.open(image.url, '_blank')
    },

    handleError(error) {
      if (error.error) {
        alert(error.error)
      } else {
        alert('An error occurred')
      }
    },

    getUserRole() {
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          this.userRole = payload.role_id
        } catch (error) {
          console.error('Error parsing token:', error)
          this.userRole = null
        }
      }
    },

    async loadTechnicians() {
      try {
        const response = await apiFetch('/backend/api/technicians.php')

        if (response.ok) {
          const data = await response.json()
          this.technicians = data.technicians || []
        } else {
          console.error('Failed to load technicians')
          this.technicians = []
        }
      } catch (error) {
        console.error('Error loading technicians:', error)
        this.technicians = []
      }
    },

    async loadJobImages(jobId) {
      try {
        const response = await apiFetch(`/backend/api/job-images.php?job_id=${jobId}`)

        if (response.ok) {
          const data = await response.json()
          this.jobImages[jobId] = data.images || []
        } else {
          console.error('Failed to load job images')
          this.jobImages[jobId] = []
        }
      } catch (error) {
        console.error('Error loading job images:', error)
        this.jobImages[jobId] = []
      }
    },

    canSelectStatus(status) {
      if (!this.statusUpdateJob) return false

      const currentStatus = this.statusUpdateJob.job_status
      const isAdmin = this.userRole === 3
      const isTechnician = this.userRole === 4

      switch (status) {
        case 'Declined':
          return isAdmin && currentStatus === 'Assigned'
        case 'In Progress':
          return isAdmin && currentStatus === 'Assigned'
        case 'Quote Provided':
          return isAdmin // Can be set when quotation is provided via email
        case 'Repaired':
          return (isAdmin || isTechnician) && currentStatus === 'In Progress'
        case 'Payment Requested':
          return isAdmin && currentStatus === 'Repaired'
        default:
          return false
      }
    },

    // Archive/unarchive job functionality
    async toggleArchiveJob(job) {
      const action = job.archived_by_service_provider ? 'unarchive' : 'archive'
      const confirmMessage = `Are you sure you want to ${action} this job?`

      if (!confirm(confirmMessage)) {
        return
      }

      try {
        const response = await apiFetch('/backend/api/service-provider-client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: job.id,
            archived_by_service_provider: !job.archived_by_service_provider
          })
        })

        if (response.ok) {
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === job.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].archived_by_service_provider = !job.archived_by_service_provider
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

          alert(`Job ${action}d successfully!`)
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert(`Failed to ${action} job`)
      }
    }
  }
}
</script>
