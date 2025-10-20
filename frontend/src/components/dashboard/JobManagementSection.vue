<template>
  <div class="jobs-section card">
    <!-- Jobs Section Header -->
    <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200">
      <div class="flex-1">
        <CollapsibleSection
          title="Job Management"
          icon="build"
          :expanded="true"
        >
          <template #title-suffix>
            <div v-if="clientProfile && (clientProfile.is_enabled === false || clientProfile.is_enabled === 0 || clientProfile.is_enabled === '0')" class="admin-disabled-warning mt-3">
              <div class="disabled-banner">
                <div class="disabled-icon">⚠️</div>
                <div class="disabled-content">
                  <h4>Account Administratively Disabled</h4>
                  <p>Service request creation is restricted. {{ clientProfile.disabled_reason || 'Please contact support for assistance.' }}</p>
                </div>
              </div>
            </div>
          </template>
        </CollapsibleSection>
      </div>
      <button @click="$emit('create-job')" class="btn-filled" :disabled="!clientProfile?.is_enabled">
        <span class="material-icon-sm mr-2">add</span>
        Service Request
      </button>
    </div>

    <!-- Job Filters -->
    <div class="job-filters flex flex-wrap gap-4 mb-6 p-4 bg-neutral-50 rounded-lg">
      <div class="filter-group min-w-40">
        <label for="status-filter" class="form-label mb-1">Status:</label>
        <select id="status-filter" v-model="jobFilters.status" @change="$emit('load-jobs')" class="form-input">
          <option value="">All Statuses</option>
          <option value="Reported">Reported</option>
          <option value="Assigned">Assigned</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>
      <!-- Archive filter only for budget controllers -->
      <div v-if="isAdmin" class="filter-group min-w-40">
        <label for="archive-filter" class="form-label mb-1">Archive Status:</label>
        <select id="archive-filter" v-model="jobFilters.archive_status" @change="$emit('load-jobs')" class="form-input">
          <option value="">All Jobs</option>
          <option value="active">Active Jobs</option>
          <option value="archived">Archived Jobs</option>
        </select>
      </div>
      <!-- Location and Provider filters for budget controllers -->
      <div v-if="isAdmin" class="filter-group min-w-40">
        <label for="location-filter" class="form-label mb-1">Location:</label>
        <select id="location-filter" v-model="jobFilters.location_id" @change="$emit('load-jobs')" class="form-input">
          <option value="">All Locations</option>
          <option v-for="location in locations" :key="location.id" :value="location.id">
            {{ location.name }}
          </option>
        </select>
      </div>
      <div v-if="isAdmin" class="filter-group min-w-40">
        <label for="provider-filter" class="form-label mb-1">Provider:</label>
        <select id="provider-filter" v-model="jobFilters.provider_id" @change="$emit('load-jobs')" class="form-input">
          <option value="">All Providers</option>
          <option v-for="provider in approvedProviders" :key="provider.service_provider_id" :value="provider.service_provider_id">
            {{ provider.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Loading state -->
    <LoadingState v-if="jobs === null" message="Loading jobs..." fullWidth />

    <!-- Job cards -->
    <div v-else-if="jobs && jobs.length > 0" class="jobs-grid grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
      <Card
        v-for="job in jobs"
        :key="job.id"
        clickable
        @click="$emit('view-job-details', job)"
      >
        <template #header>
          <div class="job-status">
            <StatusBadge :status="job.job_status" />
          </div>
          <div class="job-actions flex gap-2">
            <button v-if="canEditJob(job)" @click.stop="$emit('edit-job', job)" class="btn-outlined btn-small">
              <span class="material-icon-sm">edit</span>
            </button>
            <button v-else @click.stop="$emit('view-job-details', job)" class="btn-outlined btn-small">
              <span class="material-icon-sm">visibility</span>
            </button>
            <!-- Quote Response buttons for Quote Provided jobs -->
            <button v-if="job.job_status === 'Quote Provided' && isAdmin" @click.stop="$emit('accept-quote', job)" class="btn-filled btn-small bg-green-600 hover:bg-green-700">
              <span class="material-icon-sm">check_circle</span>
              Accept Quote
            </button>
            <button v-if="job.job_status === 'Quote Provided' && isAdmin" @click.stop="$emit('reject-quote', job)" class="btn-outlined btn-small border-red-600 text-red-600 hover:bg-red-50">
              <span class="material-icon-sm">cancel</span>
              Reject Quote
            </button>
            <!-- Confirmation/Rejection buttons for completed jobs -->
            <button v-if="job.job_status === 'Completed' && isAdmin" @click.stop="$emit('confirm-job', job)" class="btn-filled btn-small bg-green-600 hover:bg-green-700">
              <span class="material-icon-sm">check_circle</span>
              Confirm
            </button>
            <button v-if="job.job_status === 'Completed' && isAdmin" @click.stop="$emit('reject-job', job)" class="btn-outlined btn-small border-red-600 text-red-600 hover:bg-red-50">
              <span class="material-icon-sm">cancel</span>
              Reject
            </button>
            <!-- Archive/Unarchive button for budget controllers -->
            <button v-if="isAdmin" @click.stop="$emit('archive-job', job)" class="btn-outlined btn-small" :class="{ 'text-orange-600 border-orange-600': job.archived_by_client }">
              <span class="material-icon-sm">{{ job.archived_by_client ? 'unarchive' : 'archive' }}</span>
            </button>
          </div>
        </template>

        <template #content>
          <h3 class="job-title text-title-medium text-on-surface mb-2">{{ job.item_identifier || 'No Item ID' }}</h3>
          <p class="job-description text-body-medium text-on-surface-variant mb-2 line-clamp-2">{{ job.fault_description }}</p>
          <p class="job-location text-body-small text-on-surface-variant mb-4">
            <span class="material-icon-sm mr-1">location_on</span>
            {{ job.location_name }}
          </p>

          <div class="job-meta grid grid-cols-2 gap-3 text-sm">
            <div class="meta-item">
              <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Reported by:</span>
              <span class="meta-value text-body-small text-on-surface font-medium">{{ job.reporting_user }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Date:</span>
              <span class="meta-value text-body-small text-on-surface font-medium">{{ formatDate(job.created_at) }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Provider:</span>
              <span class="meta-value text-body-small text-on-surface font-medium">{{ job.assigned_provider_name || 'Not assigned' }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Images:</span>
              <span class="meta-value text-body-small text-on-surface font-medium">{{ job.image_count }}</span>
            </div>
          </div>
        </template>

        <template #footer>
          <div class="job-date text-label-medium text-on-surface-variant">
            <span class="material-icon-sm mr-1">schedule</span>
            Last updated {{ formatDate(job.updated_at) }}
          </div>
        </template>
      </Card>
    </div>

    <!-- No jobs state -->
    <ErrorState
      v-else-if="jobs && jobs.length === 0"
      title="No jobs found"
      message="Report your first fault to get started."
      icon="build"
    />

    <!-- Error state -->
    <ErrorState
      v-else
      title="Failed to load jobs"
      message="Please try refreshing the page."
      icon="error"
    />
  </div>
</template>

<script>
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'JobManagementSection',
  components: {
    CollapsibleSection,
    Card,
    LoadingState,
    ErrorState,
    StatusBadge
  },
  props: {
    jobs: {
      type: Array,
      default: null
    },
    clientProfile: {
      type: Object,
      default: null
    },
    jobFilters: {
      type: Object,
      required: true
    },
    userRole: {
      type: [Number, String],
      default: null
    },
    userId: {
      type: [Number, String],
      default: null
    },
    locations: {
      type: Array,
      default: () => []
    },
    approvedProviders: {
      type: Array,
      default: () => []
    },
    isAdmin: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    canEditJob(job) {
      // Cannot edit archived jobs
      if (job.archived_by_client) {
        return false
      }

      // Reporting employees can edit their own jobs when status is 'Reported'
      if (this.userRole === 1 && job.job_status === 'Reported' && job.reporting_user_id === this.userId) {
        return true
      }

      // Budget controllers can edit when status is 'Reported', 'Declined', or 'Quote Requested'
      if (this.isAdmin && ['Reported', 'Declined', 'Quote Requested'].includes(job.job_status)) {
        return true
      }

      return false
    },
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.jobs-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 40px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
  cursor: pointer;
}

.admin-disabled-warning {
  margin-top: 15px;
}

.disabled-banner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  background: #fff3cd;
  border: 1px solid #ffeeba;
  border-radius: 8px;
  padding: 15px;
}

.disabled-icon {
  font-size: 24px;
  color: #856404;
  flex-shrink: 0;
}

.disabled-content h4 {
  margin: 0 0 8px 0;
  color: #856404;
  font-size: 16px;
  font-weight: 600;
}

.disabled-content p {
  margin: 0;
  color: #856404;
  font-size: 14px;
  line-height: 1.4;
}

.job-filters {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 150px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 500;
  color: #666;
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  background: white;
}

.jobs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.job-status {
  display: flex;
  align-items: center;
}

.job-actions {
  display: flex;
  gap: 8px;
}

.job-title {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 1.1em;
}

.job-description {
  color: #666;
  margin-bottom: 8px;
  line-height: 1.4;
}

.job-location {
  color: #666;
  margin-bottom: 12px;
  font-size: 0.9em;
}

.job-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  min-width: 120px;
}

.meta-label {
  font-size: 0.8em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.meta-value {
  font-size: 0.9em;
  color: #333;
  font-weight: 500;
}

.job-date {
  font-size: 0.85em;
  color: #666;
  margin: 0;
  text-align: left;
}

.btn-outlined {
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 0.9em;
  color: #666;
  transition: all 0.2s ease;
}

.btn-outlined:hover {
  background: #f0f0f0;
}

.btn-filled {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-filled {
  background: #007bff;
  color: white;
}

.btn-filled:hover:not(:disabled) {
  background: #0056b3;
}

.btn-filled:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.9em;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

@media (max-width: 768px) {
  .jobs-section {
    padding: 20px;
  }

  .section-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .job-filters {
    flex-direction: column;
    gap: 15px;
  }

  .jobs-grid {
    grid-template-columns: 1fr;
  }

  .job-meta {
    flex-direction: column;
    gap: 12px;
  }

  .meta-item {
    min-width: 0;
  }
}
</style>
