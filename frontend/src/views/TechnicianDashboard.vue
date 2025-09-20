<template>
  <div class="technician-dashboard">
    <div class="dashboard-header">
      <div class="header-left">
        <h1>Technician Dashboard</h1>
        <div class="user-info">
          <span class="welcome-text">Welcome, {{ userName }}</span>
        </div>
      </div>
      <div class="header-right">
        <button @click="signOut" class="btn-secondary">Sign Out</button>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Statistics Section -->
      <div class="stats-section">
        <h2>My Job Statistics</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number">{{ statistics.total_jobs }}</div>
            <div class="stat-label">Total Jobs</div>
          </div>
          <div class="stat-card active">
            <div class="stat-number">{{ statistics.active_jobs }}</div>
            <div class="stat-label">Active Jobs</div>
          </div>
          <div class="stat-card completed">
            <div class="stat-number">{{ statistics.completed_jobs }}</div>
            <div class="stat-label">Completed Jobs</div>
          </div>
        </div>
      </div>

      <!-- Jobs Section -->
      <div class="jobs-section">
        <div class="section-header">
          <h2>My Assigned Jobs</h2>
          <div class="jobs-summary">
            <span class="summary-item">
              Showing {{ jobs.length }} jobs
            </span>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading your jobs...</p>
        </div>

        <!-- No Jobs -->
        <div v-else-if="jobs.length === 0" class="no-jobs">
          <div class="no-jobs-icon">📋</div>
          <h3>No jobs assigned</h3>
          <p>You don't have any jobs assigned to you yet.</p>
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
                <h4>My Notes</h4>
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
            <h4>My Notes</h4>
            <p>{{ selectedJob.technician_notes }}</p>
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
              <option value="Assigned">Assigned</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="On Hold">On Hold</option>
            </select>
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
  name: 'TechnicianDashboard',
  data() {
    return {
      jobs: [],
      statistics: {
        total_jobs: 0,
        active_jobs: 0,
        completed_jobs: 0
      },
      loading: false,
      selectedJob: null,
      statusUpdateJob: null,
      newStatus: '',
      statusNotes: '',
      updatingStatus: false,
      userName: ''
    }
  },
  mounted() {
    this.loadUserInfo()
    this.loadTechnicianJobs()
  },
  methods: {
    loadUserInfo() {
      // Get user info from localStorage or token
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1]))
          this.userName = payload.username || 'Technician'
        } catch (e) {
          this.userName = 'Technician'
        }
      }
    },

    async loadTechnicianJobs() {
      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/technician-jobs.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          this.jobs = data.jobs
          this.statistics = data.statistics
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load jobs')
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
      return ['Assigned', 'In Progress', 'On Hold'].includes(job.job_status)
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

          // Update statistics
          this.updateStatistics()

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

    updateStatistics() {
      this.statistics.total_jobs = this.jobs.length
      this.statistics.active_jobs = this.jobs.filter(job =>
        ['Reported', 'Assigned', 'In Progress'].includes(job.job_status)
      ).length
      this.statistics.completed_jobs = this.jobs.filter(job => job.job_status === 'Completed').length
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

    signOut() {
      localStorage.removeItem('token')
      this.$router.push('/')
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

<style scoped>
.technician-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.user-info {
  margin-top: 8px;
}

.welcome-text {
  color: #666;
  font-size: 0.9em;
}

.dashboard-content {
  display: grid;
  gap: 30px;
}

.stats-section, .jobs-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.stat-card {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #e0e0e0;
}

.stat-card.active {
  border-color: #007bff;
  background: #f8f9ff;
}

.stat-card.completed {
  border-color: #28a745;
  background: #f8fff9;
}

.stat-number {
  font-size: 2.5em;
  font-weight: bold;
  color: #333;
  margin-bottom: 5px;
}

.stat-label {
  color: #666;
  font-size: 0.9em;
  text-transform: uppercase;
  letter-spacing: 0.5px;
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
  font-weight: 500;
  color: #666;
}

.summary-item {
  font-size: 0.9em;
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
  .technician-dashboard {
    padding: 10px;
  }

  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
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
