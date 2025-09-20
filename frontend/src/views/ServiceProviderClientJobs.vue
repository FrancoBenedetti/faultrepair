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
                <option value="Assigned">Assigned</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="On Hold">On Hold</option>
              </select>
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
      updatingStatus: false
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
        const token = localStorage.getItem('token')
        const response = await fetch(`/backend/api/service-provider-client-jobs.php?client_id=${clientId}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

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

    showJobDetails(job) {
      this.selectedJob = { ...job }
    },

    closeJobDetails() {
      this.selectedJob = null
    },

    showStatusModal(job) {
      this.statusUpdateJob = { ...job }
      this.newStatus = job.job_status
      this.statusNotes = ''
    },

    closeStatusModal() {
      this.statusUpdateJob = null
      this.newStatus = ''
      this.statusNotes = ''
    },

    canUpdateStatus(job) {
      // Allow status updates for active jobs
      return ['Reported', 'Assigned', 'In Progress', 'On Hold'].includes(job.job_status)
    },

    async updateJobStatus() {
      if (!this.newStatus) {
        alert('Please select a new status')
        return
      }

      this.updatingStatus = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/job-status-update.php', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            job_id: this.statusUpdateJob.id,
            status: this.newStatus,
            notes: this.statusNotes
          })
        })

        if (response.ok) {
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === this.statusUpdateJob.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = this.newStatus
            this.jobs[jobIndex].updated_at = new Date().toISOString()
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

    handleError(error) {
      if (error.error) {
        alert(error.error)
      } else {
        alert('An error occurred')
      }
    }
  }
}
</script>
