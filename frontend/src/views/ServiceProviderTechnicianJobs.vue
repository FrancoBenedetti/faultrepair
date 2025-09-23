<template>
  <div class="technician-jobs">
    <div class="page-header">
      <div class="header-content">
        <div class="technician-info">
          <h1>{{ technician.full_name }}</h1>
          <p class="technician-email">{{ technician.email }}</p>
          <p class="technician-phone" v-if="technician.phone">{{ technician.phone }}</p>
        </div>
        <div class="back-button">
          <button @click="$router.go(-1)" class="btn-secondary">
            ← Back to Dashboard
          </button>
        </div>
      </div>
    </div>

    <div class="jobs-section">
      <div class="section-header">
        <h2>Jobs Assigned to {{ technician.full_name }}</h2>
        <div class="jobs-summary">
          <span class="summary-item">
            Total: {{ jobs.length }}
          </span>
          <span class="summary-item active">
            Active: {{ activeJobsCount }}
          </span>
          <span class="summary-item completed">
            Completed: {{ completedJobsCount }}
          </span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading technician jobs...</p>
      </div>

      <!-- No Jobs -->
      <div v-else-if="jobs.length === 0" class="no-jobs">
        <div class="no-jobs-icon">📋</div>
        <h3>No jobs assigned</h3>
        <p>{{ technician.full_name }} doesn't have any jobs assigned yet.</p>
      </div>

      <!-- Jobs List -->
      <div v-else class="jobs-list">
        <div v-for="job in jobs" :key="job.id" class="job-card">
          <div class="job-header">
            <div class="job-title">
              <h3>{{ job.item_identifier || 'No Item ID' }}</h3>
              <span :class="['status-badge', getStatusClass(job.job_status)]">
                {{ job.job_status }}
              </span>
            </div>
            <div class="job-meta">
              <span class="client">{{ job.client_name }}</span>
              <span class="location">{{ job.location_name }}</span>
              <span class="date">{{ formatDate(job.created_at) }}</span>
            </div>
          </div>

          <div class="job-content">
            <div class="fault-description">
              <h4>Fault Description</h4>
              <p>{{ job.fault_description }}</p>
            </div>

            <div v-if="job.technician_notes" class="technician-notes">
              <h4>Technician Notes</h4>
              <p>{{ job.technician_notes }}</p>
            </div>

            <div class="job-details">
              <div class="detail-item">
                <strong>Service Provider:</strong> {{ job.service_provider_name }}
              </div>
              <div class="detail-item">
                <strong>Reported by:</strong> {{ job.reporting_user || 'Unknown' }}
              </div>
              <div class="detail-item">
                <strong>Last Updated:</strong> {{ formatDate(job.updated_at) }}
              </div>
            </div>
          </div>

          <div class="job-actions">
            <button @click="showJobDetails(job)" class="btn-primary">
              View Details
            </button>
            <button v-if="canUpdateStatus(job)" @click="showStatusModal(job)" class="btn-secondary">
              Update Status
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Job Details Modal -->
    <div v-if="selectedJob" class="modal-overlay" @click="closeJobDetails">
      <div class="modal-content job-details-modal" @click.stop>
        <div class="modal-header">
          <h3>Job Details - {{ selectedJob.item_identifier || 'No Item ID' }}</h3>
          <button @click="closeJobDetails" class="close-btn">&times;</button>
        </div>

        <div class="job-details-content">
          <div class="job-info-grid">
            <div class="info-section">
              <h4>Job Information</h4>
              <div class="info-grid">
                <div class="info-item">
                  <strong>Status:</strong>
                  <span :class="['status-badge', getStatusClass(selectedJob.job_status)]">
                    {{ selectedJob.job_status }}
                  </span>
                </div>
                <div class="info-item">
                  <strong>Client:</strong> {{ selectedJob.client_name }}
                </div>
                <div class="info-item">
                  <strong>Location:</strong> {{ selectedJob.location_name }}
                </div>
                <div class="info-item">
                  <strong>Address:</strong> {{ selectedJob.location_address }}
                </div>
                <div class="info-item">
                  <strong>Reported:</strong> {{ formatDate(selectedJob.created_at) }}
                </div>
                <div class="info-item">
                  <strong>Last Updated:</strong> {{ formatDate(selectedJob.updated_at) }}
                </div>
              </div>
            </div>

            <div class="info-section">
              <h4>Assignment Details</h4>
              <div class="info-grid">
                <div class="info-item">
                  <strong>Technician:</strong> {{ technician.full_name }}
                </div>
                <div class="info-item">
                  <strong>Service Provider:</strong> {{ selectedJob.service_provider_name }}
                </div>
                <div class="info-item">
                  <strong>Reported by:</strong> {{ selectedJob.reporting_user || 'Unknown' }}
                </div>
              </div>
            </div>
          </div>

          <div class="job-description-section">
            <h4>Fault Description</h4>
            <p>{{ selectedJob.fault_description }}</p>
          </div>

          <div v-if="selectedJob.technician_notes" class="job-notes-section">
            <h4>Technician Notes</h4>
            <p>{{ selectedJob.technician_notes }}</p>
          </div>

          <!-- Attached Images -->
          <div v-if="jobImages[selectedJob.id] && jobImages[selectedJob.id].length > 0" class="job-images-section">
            <h4>Attached Images ({{ jobImages[selectedJob.id].length }})</h4>
            <div class="images-grid">
              <div v-for="image in jobImages[selectedJob.id]" :key="image.id" class="image-item">
                <div class="image-container">
                  <img
                    :src="image.url"
                    :alt="image.original_filename"
                    class="job-image"
                    @click="openImageModal(image)"
                  >
                </div>
                <div class="image-info">
                  <div class="image-name">{{ image.original_filename }}</div>
                  <div class="image-meta">{{ formatFileSize(image.file_size) }} • {{ formatDate(image.uploaded_at) }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="status-history-section">
            <h4>Status History</h4>
            <div v-if="selectedJob.status_history && selectedJob.status_history.length > 0" class="status-timeline">
              <div v-for="history in selectedJob.status_history" :key="history.changed_at" class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                  <div class="status-change">{{ history.status }}</div>
                  <div class="status-meta">
                    {{ formatDate(history.changed_at) }}
                    <span v-if="history.changed_by">by {{ history.changed_by }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="no-history">
              No status history available
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="statusUpdateJob" class="modal-overlay" @click="closeStatusModal">
      <div class="modal-content status-modal" @click.stop>
        <div class="modal-header">
          <h3>Update Job Status</h3>
          <button @click="closeStatusModal" class="close-btn">&times;</button>
        </div>

        <div class="status-form">
          <div class="current-status">
            <strong>Current Status:</strong>
            <span :class="['status-badge', getStatusClass(statusUpdateJob.job_status)]">
              {{ statusUpdateJob.job_status }}
            </span>
          </div>

          <div class="form-group">
            <label for="newStatus">New Status</label>
            <select id="newStatus" v-model="newStatus" class="status-select">
              <option v-if="canSelectStatus('Declined')" value="Declined">Declined</option>
              <option v-if="canSelectStatus('In Progress')" value="In Progress">In Progress</option>
              <option v-if="canSelectStatus('Quote Provided')" value="Quote Provided">Quote Provided</option>
              <option v-if="canSelectStatus('Repaired')" value="Repaired">Repaired</option>
              <option v-if="canSelectStatus('Payment Requested')" value="Payment Requested">Payment Requested</option>
            </select>
          </div>

          <!-- Technician Selection (only for In Progress) -->
          <div v-if="newStatus === 'In Progress'" class="form-group">
            <label for="technician" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">engineering</span>
              Assign Technician *
            </label>
            <select id="technician" v-model="selectedTechnicianId" class="status-select">
              <option value="">Select a technician...</option>
              <option v-for="technician in technicians" :key="technician.id" :value="technician.id">
                {{ technician.full_name }} ({{ technician.username }})
              </option>
            </select>
            <p v-if="!selectedTechnicianId" class="text-sm text-red-600 mt-1">
              A technician must be assigned before setting the job to "In Progress"
            </p>
          </div>

          <div class="form-group">
            <label for="notes">Notes (Optional)</label>
            <textarea id="notes" v-model="statusNotes" rows="3" placeholder="Add any notes about this status change..."></textarea>
          </div>

          <div class="form-actions">
            <button @click="closeStatusModal" class="btn-secondary">Cancel</button>
            <button @click="updateJobStatus" class="btn-primary" :disabled="updatingStatus">
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
  name: 'ServiceProviderTechnicianJobs',
  data() {
    return {
      technician: {},
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
    const technicianId = this.$route.params.technicianId
    if (technicianId) {
      this.loadTechnicianJobs(technicianId)
    } else {
      this.$router.push('/service-provider/dashboard')
    }
  },
  methods: {
    async loadTechnicianJobs(technicianId) {
      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`/backend/api/technician-jobs.php?technician_id=${technicianId}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          this.technician = data.technician
          this.jobs = data.jobs
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load technician jobs')
      } finally {
        this.loading = false
      }
    },

    async showJobDetails(job) {
      this.selectedJob = { ...job }
      // Load images for this job
      await this.loadJobImages(job.id)
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
        const token = localStorage.getItem('token')
        const requestData = {
          job_id: this.statusUpdateJob.id,
          status: this.newStatus,
          notes: this.statusNotes
        }

        // Include technician assignment if setting to "In Progress"
        if (this.newStatus === 'In Progress' && this.selectedTechnicianId) {
          requestData.technician_id = this.selectedTechnicianId
        }

        const response = await fetch('/backend/api/job-status-update.php', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
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
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/technicians.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

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
        const token = localStorage.getItem('token')
        const response = await fetch(`/backend/api/job-images.php?job_id=${jobId}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

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
    }
  }
}
</script>

<style scoped>
.technician-jobs {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  background: white;
  border-radius: 8px;
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.technician-info h1 {
  margin: 0 0 8px 0;
  color: #333;
}

.technician-email {
  color: #666;
  margin: 0 0 4px 0;
}

.technician-phone {
  color: #666;
  margin: 0;
}

.back-button {
  flex-shrink: 0;
}

.jobs-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.section-header h2 {
  margin: 0;
  color: #333;
}

.jobs-summary {
  display: flex;
  gap: 20px;
}

.summary-item {
  font-weight: 500;
  color: #666;
}

.summary-item.active {
  color: #007bff;
}

.summary-item.completed {
  color: #28a745;
}

/* Loading and No Jobs States */
.loading-state, .no-jobs {
  text-align: center;
  padding: 60px 20px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-jobs-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.no-jobs h3 {
  color: #666;
  margin-bottom: 10px;
}

/* Jobs List */
.jobs-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.job-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.job-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.job-header {
  background: #f8f9fa;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.job-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.job-title h3 {
  margin: 0;
  color: #333;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.85em;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.reported {
  background: #fff3cd;
  color: #856404;
}

.status-badge.assigned {
  background: #cce5ff;
  color: #004085;
}

.status-badge.in-progress {
  background: #d1ecf1;
  color: #0c5460;
}

.status-badge.completed {
  background: #d4edda;
  color: #155724;
}

.status-badge.on-hold {
  background: #f8d7da;
  color: #721c24;
}

.status-badge.unknown {
  background: #e2e3e5;
  color: #383d41;
}

.job-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9em;
  color: #666;
}

.client {
  font-weight: 500;
}

.job-content {
  padding: 20px;
}

.fault-description, .technician-notes {
  margin-bottom: 20px;
}

.fault-description h4, .technician-notes h4 {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 1em;
}

.fault-description p, .technician-notes p {
  margin: 0;
  line-height: 1.5;
  color: #555;
}

.job-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #f0f0f0;
}

.detail-item {
  font-size: 0.9em;
}

.detail-item strong {
  color: #333;
}

.job-actions {
  padding: 20px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
}

.job-details-modal {
  max-width: 900px;
}

.status-modal {
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.job-details-content {
  padding: 20px;
}

.job-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 30px;
}

.info-section h4 {
  margin: 0 0 15px 0;
  color: #333;
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-item strong {
  color: #333;
  min-width: 120px;
}

.job-description-section, .job-notes-section {
  margin-bottom: 30px;
}

.job-description-section h4, .job-notes-section h4 {
  margin: 0 0 10px 0;
  color: #333;
}

.job-description-section p, .job-notes-section p {
  margin: 0;
  line-height: 1.5;
  color: #555;
}

.status-history-section h4 {
  margin: 0 0 15px 0;
  color: #333;
}

.status-timeline {
  position: relative;
  padding-left: 30px;
}

.status-timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e0e0e0;
}

.timeline-item {
  position: relative;
  margin-bottom: 20px;
}

.timeline-marker {
  position: absolute;
  left: -22px;
  top: 6px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #007bff;
  border: 2px solid white;
  box-shadow: 0 0 0 2px #007bff;
}

.timeline-content {
  background: #f8f9fa;
  padding: 12px 16px;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.status-change {
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.status-meta {
  font-size: 0.85em;
  color: #666;
}

.no-history {
  text-align: center;
  color: #666;
  padding: 20px;
  font-style: italic;
}

/* Status Update Form */
.status-form {
  padding: 20px;
}

.current-status {
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.status-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  background: white;
}

.status-select:focus {
  outline: none;
  border-color: #007bff;
}

.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  resize: vertical;
  min-height: 80px;
}

.form-group textarea:focus {
  outline: none;
  border-color: #007bff;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-primary, .btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

/* Responsive Design */
@media (max-width: 768px) {
  .technician-jobs {
    padding: 10px;
  }

  .page-header, .jobs-section {
    padding: 15px;
  }

  .header-content {
    flex-direction: column;
    gap: 15px;
  }

  .section-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .job-title {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .job-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }

  .job-actions {
    flex-direction: column;
  }

  .job-info-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
