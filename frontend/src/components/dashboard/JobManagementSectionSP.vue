<template>
  <div class="section-content">
      <!-- Job Filters -->
      <div class="job-filters flex flex-wrap gap-4 mb-6 p-4 bg-neutral-50 rounded-lg">
        <div class="filter-group min-w-40">
          <label for="status-filter" class="form-label mb-1">Status:</label>
          <select id="status-filter" v-model="jobFilters.status" @change="$emit('update-job-filters', jobFilters)" class="form-input">
            <option value="">All Statuses</option>
            <option value="Reported">Reported</option>
            <option value="Assigned">Assigned</option>
            <option value="Quote Requested">Quote Requested</option>
            <option value="Quote Provided">Quote Provided</option>
            <option value="Declined">Declined</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Incomplete">Incomplete</option>
            <option value="Cannot repair">Cannot repair</option>
          </select>
        </div>

        <!-- Client filter for admins -->
        <div v-if="userRole === 3" class="filter-group min-w-40">
          <label for="client-filter" class="form-label mb-1">Client:</label>
          <select id="client-filter" v-model="jobFilters.client_id" @change="$emit('update-job-filters', jobFilters)" class="form-input">
            <option value="">All Clients</option>
            <option v-for="client in approvedClients" :key="client.id" :value="client.id">
              {{ client.name }}
            </option>
          </select>
        </div>

        <!-- Technician filter for admins -->
        <div v-if="userRole === 3" class="filter-group min-w-40">
          <label for="technician-filter" class="form-label mb-1">Technician:</label>
          <select id="technician-filter" v-model="jobFilters.technician_id" @change="$emit('update-job-filters', jobFilters)" class="form-input">
            <option value="">All Technicians</option>
            <option v-for="technician in technicians" :key="technician.id" :value="technician.id">
              {{ technician.full_name }}
            </option>
          </select>
        </div>
      </div>

      <div class="jobs-grid grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Loading state -->
        <LoadingState v-if="jobs === null" class="col-span-full" message="Loading jobs..." />

        <!-- Job cards -->
        <Card v-else-if="jobs && jobs.length > 0" v-for="job in jobs" :key="job.id" class="job-card overflow-hidden transition-all duration-200 hover:shadow-elevation-3">
          <template #header>
            <div class="job-status">
              <StatusBadge :status="job.job_status" />
            </div>
            <div class="job-actions flex gap-2">
              <button v-if="userRole === 3 || (userRole === 4 && (job.assigned_technician_user_id == currentUserId || job.job_status !== 'In Progress'))"
                      @click="$emit('view-job-details', job)" class="btn-outlined btn-small">
                <span class="material-icon-sm">visibility</span>
              </button>
              <button v-if="userRole === 3 || (userRole === 4 && job.assigned_technician_user_id == currentUserId)"
                      @click="$emit('edit-job', job)" class="btn-outlined btn-small">
                <span class="material-icon-sm">edit</span>
              </button>
            </div>
          </template>

          <template #content>
            <div class="job-content p-4">
              <h3 class="job-title font-bold text-lg mb-2">{{ job.item_identifier || 'No Item ID' }}</h3>
              <p class="job-description mb-2 text-gray-700">{{ job.fault_description }}</p>
              <p class="job-location mb-2">
                <span class="mr-1">📍</span>
                <span class="text-blue-600">{{ job.location_name }}</span>
              </p>

              <div class="job-meta grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span class="font-medium">Client:</span>
                  <span>{{ job.client_name }}</span>
                </div>
                <div>
                  <span class="font-medium">Date:</span>
                  <span>{{ formatDate(job.created_at) }}</span>
                </div>
                <div>
                  <span class="font-medium">Technician:</span>
                  <span>{{ job.assigned_technician || 'Not assigned' }}</span>
                </div>
                <div>
                  <span class="font-medium">{{ job.job_status === 'Quote Requested' ? 'Quote Due:' : 'Images:' }}</span>
                  <span v-if="job.job_status === 'Quote Requested'" :class="getQuoteUrgencyClass(job.due_date)" class="font-medium">
                    {{ formatQuoteDueDate(job.due_date) }}
                  </span>
                  <span v-else>{{ job.image_count }}</span>
                </div>
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

        <!-- No jobs state -->
        <div v-else-if="jobs && jobs.length === 0" class="no-jobs col-span-full text-center py-16">
          <div class="no-jobs-icon material-icon-xl text-neutral-400 mb-4">build</div>
          <h3 class="text-title-large text-on-surface mb-2">No jobs found</h3>
          <p class="text-body-large text-on-surface-variant">No jobs match your current filters.</p>
        </div>

        <!-- Error state -->
        <ErrorState v-else class="col-span-full" message="Failed to load jobs" />
      </div>
  </div>
</template>

<script>
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'JobManagementSectionSP',
  components: {
    CollapsibleSection,
    Card,
    LoadingState,
    ErrorState,
    StatusBadge
  },
  props: {
    expanded: {
      type: Boolean,
      default: true
    },
    jobs: {
      type: Array,
      default: () => null
    },
    jobFilters: {
      type: Object,
      required: true
    },
    approvedClients: {
      type: Array,
      default: () => []
    },
    technicians: {
      type: Array,
      default: () => []
    },
    userRole: {
      type: Number,
      required: true
    }
  },
  data() {
    return {}
  },

  computed: {
    currentUserId() {
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.user_id
        } catch (error) {
          console.error('Error parsing token:', error)
          return null
        }
      }
      return null
    }
  },
  methods: {
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
    }
  }
}
</script>
