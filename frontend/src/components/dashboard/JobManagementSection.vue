<template>
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
        <option value="0">Default</option>
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
        :class="{ 'xs-provider-job': isXSProviderJob(job) }"
      >
        <template #header>
          <div class="job-status">
            <StatusBadge :status="job.job_status" />
            <!-- XS Provider Indicator -->
            <div v-if="isXSProviderJob(job)" class="xs-indicator">
              <span class="material-icon-sm text-orange-600">settings</span>
              <span class="xs-label text-xs font-medium text-orange-700 ml-1">External Provider</span>
            </div>
          </div>
          <div class="job-actions flex gap-2">
            <!-- For admin users (budget controllers), always show edit button -->
            <button v-if="isAdmin" @click.stop="$emit('edit-job', job)" class="btn-outlined btn-small">
              <span class="material-icon-sm">{{ canEditJob(job) ? 'edit' : 'visibility' }}</span>
            </button>
            <!-- For regular users, show edit if they can edit, otherwise view -->
            <template v-else>
              <button v-if="canEditJob(job)" @click.stop="$emit('edit-job', job)" class="btn-outlined btn-small">
                <span class="material-icon-sm">edit</span>
              </button>
              <button v-else @click.stop="$emit('view-job-details', job)" class="btn-outlined btn-small">
                <span class="material-icon-sm">visibility</span>
              </button>
            </template>

            <!-- Archive/Unarchive button for budget controllers -->
            <button v-if="isAdmin" @click.stop="$emit('toggle-archive-job', job)" class="btn-outlined btn-small" :class="{ 'text-orange-600 border-orange-600': job.archived_by_client }">
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
              <span class="meta-value text-body-small text-on-surface font-medium">
                {{ job.assigned_provider_name || 'Not assigned' }}
                <span v-if="isXSProviderJob(job)" class="text-orange-600 font-bold text-xs ml-1">(External)</span>
              </span>
            </div>
            <div class="meta-item">
              <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">
                {{ job.job_status === 'Quote Requested' ? 'Quote Due:' : (job.job_status === 'Assigned' && job.current_quotation_id ? 'Quoted:' : 'Images:') }}
              </span>
              <span v-if="job.job_status === 'Quote Requested'" :class="getQuoteUrgencyClass(job.due_date)" class="meta-value text-body-small text-on-surface font-medium">
                {{ formatQuoteDueDate(job.due_date) }}
              </span>
              <span v-else-if="job.job_status === 'Assigned' && job.current_quotation_id" class="meta-value text-body-small text-on-surface font-medium">
                R{{ formatQuoteAmount(job.quotation_amount) }}
              </span>
              <span v-else class="meta-value text-body-small text-on-surface font-medium">{{ job.image_count }}</span>
            </div>
          </div>

          <!-- Floating Action Panel for Completed Jobs - Client Admin View -->
          <div v-if="job?.job_status === 'Completed' && isAdmin"
               class="completion-action-panel mt-4 p-3 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200">
            <p class="text-sm font-medium text-green-800 mb-3 flex items-center gap-2">
              <span class="material-icon text-green-600">check_circle</span>
              Service provider has marked this job as complete
            </p>
            <div class="flex gap-3">
              <button
                @click.stop="$emit('confirm-job', job)"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2"
              >
                <span class="material-icon-sm">check_circle</span>
                Confirm completion
              </button>
              <button
                @click.stop="$emit('reject-job', job)"
                class="flex-1 bg-white hover:bg-gray-50 text-red-600 font-medium py-2 px-4 rounded-lg border border-red-200 transition-colors flex items-center justify-center gap-2"
              >
                <span class="material-icon-sm">cancel</span>
                Reject / Return
              </button>
            </div>
          </div>

          <!-- Floating Action Panel for Quote Provided Jobs - Client Admin View -->
          <div v-if="job?.job_status === 'Quote Provided' && isAdmin"
               class="quote-action-panel mt-4 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
            <div class="flex justify-center">
              <button
                @click.stop="$emit('view-quotation', job)"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2 text-sm"
              >
                <span class="material-icon-sm">visibility</span>
                View Quotation
              </button>
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
</template>

<script>
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'JobManagementSection',
  components: {
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
    },
    expanded: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    isXSProviderJob(job) {
      // Check if this job is assigned to an external service provider (XS)
      return job && job.assigned_provider_type === 'XS';
    },

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
      // Also XS provider jobs in ANY status
      if (this.isAdmin && (['Reported', 'Declined', 'Quote Requested'].includes(job.job_status) || this.isXSProviderJob(job))) {
        return true
      }

      return false
    },
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },
    getQuoteUrgencyClass(dueDate) {
      if (!dueDate) return 'text-gray-600';
      const daysRemaining = this.calculateDaysRemaining(dueDate);
      if (daysRemaining <= 1) return 'text-red-600 font-bold';
      if (daysRemaining <= 3) return 'text-yellow-600';
      return 'text-gray-600';
    },
    calculateDaysRemaining(dueDate) {
      if (!dueDate) return 999;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const due = new Date(dueDate);
      due.setHours(0, 0, 0, 0);
      const diffTime = due.getTime() - today.getTime();
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    },
    formatQuoteDueDate(dueDate) {
      if (!dueDate) return 'No deadline';
      const days = this.calculateDaysRemaining(dueDate);
      if (days < 0) return `Overdue by ${Math.abs(days)} days`;
      if (days === 0) return 'Due today';
      if (days === 1) return '1 day left';
      return `${days} days left`;
    },
    formatQuoteAmount(amount) {
      if (amount === null || amount === undefined) return '0';
      return new Intl.NumberFormat('en-ZA', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(amount);
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

/* XS Provider Job Card Styling - Subtle orange accent */
.xs-provider-job {
  position: relative;
  border-left: 3px solid #ff8c69;
  background: linear-gradient(135deg, rgba(255, 107, 53, 0.015), rgba(255, 107, 53, 0.025));
}

.xs-provider-job:hover {
  background: linear-gradient(135deg, rgba(255, 107, 53, 0.02), rgba(255, 107, 53, 0.035));
}

.xs-provider-job::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 20px 20px 0;
  border-color: transparent #ff6b35 transparent transparent;
}

.xs-provider-job::after {
  content: '';
  position: absolute;
  top: 2px;
  right: 4px;
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.xs-indicator {
  display: flex;
  align-items: center;
  margin-left: 8px;
  color: #ff6b35;
  font-size: 11px;
  font-weight: 500;
  opacity: 0.9;
}

.xs-label {
  line-height: 1.2;
  font-size: 11px !important;
}
</style>
