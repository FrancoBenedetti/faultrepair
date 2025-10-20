<template>
  <CollapsibleSection
    title="Business Profile"
    :expanded="expanded"
    :completeness="profileCompleteness"
    @toggle="$emit('toggle')"
  >
    <template #header-actions>
      <button @click.stop="$emit('edit-profile')" class="btn-filled flex items-center gap-2">
        <span class="material-icon-sm">edit</span>
        Edit Profile
      </button>
    </template>

    <div class="section-content transition-all duration-300 ease-in-out">
      <!-- Loading state -->
      <LoadingState v-if="!profile" message="Loading profile..." />

      <!-- Profile content -->
      <div v-else-if="profile" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Basic Information Card -->
        <Card class="profile-card" title="Basic Information">
          <div class="profile-field-grid space-y-3">
            <div class="profile-field">
              <label class="profile-field-label">Business Name:</label>
              <span class="profile-field-value">{{ profile.name || 'Not specified' }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Address:</label>
              <span class="profile-field-value">{{ profile.address || 'Not specified' }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Website:</label>
              <span class="profile-field-value">
                <a v-if="profile.website"
                   :href="profile.website"
                   target="_blank"
                   class="text-blue-600 hover:text-blue-800 underline break-all">
                  {{ profile.website }}
                </a>
                <span v-else class="text-gray-500">Not specified</span>
              </span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Description:</label>
              <span class="profile-field-value">{{ profile.description || 'Not specified' }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">VAT Number:</label>
              <span class="profile-field-value">{{ profile.vat_number || 'Not specified' }}</span>
            </div>
          </div>
        </Card>

        <!-- Manager Contact Card -->
        <Card class="profile-card" title="Manager Contact">
          <div class="profile-field-grid space-y-3">
            <div class="profile-field">
              <label class="profile-field-label">Manager Name:</label>
              <span class="profile-field-value">{{ profile.manager_name || 'Not specified' }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Manager Email:</label>
              <span class="profile-field-value">
                <a v-if="profile.manager_email"
                   :href="`mailto:${profile.manager_email}`"
                   class="text-blue-600 hover:text-blue-800 underline break-all">
                  {{ profile.manager_email }}
                </a>
                <span v-else class="text-gray-500">Not specified</span>
              </span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Manager Phone:</label>
              <span class="profile-field-value">
                <a v-if="profile.manager_phone"
                   :href="`tel:${profile.manager_phone}`"
                   class="text-blue-600 hover:text-blue-800 underline">
                  {{ profile.manager_phone }}
                </a>
                <span v-else class="text-gray-500">Not specified</span>
              </span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Registration:</label>
              <span class="profile-field-value">{{ profile.business_registration_number || 'Not specified' }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Member Since:</label>
              <span class="profile-field-value">{{ formatDate(profile.created_at) }}</span>
            </div>
          </div>
        </Card>

        <!-- Status & Account Card -->
        <Card class="profile-card" title="Account Status">
          <div class="profile-field-grid space-y-3">
            <div class="profile-field">
              <label class="profile-field-label">Status:</label>
              <StatusBadge :class="profile.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ profile.is_active ? 'Active' : 'Inactive' }}
              </StatusBadge>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Jobs Completed:</label>
              <span class="profile-field-value font-semibold">{{ profile.jobs_completed || 0 }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Active Jobs:</label>
              <span class="profile-field-value">{{ profile.active_jobs || 0 }}</span>
            </div>
            <div class="profile-field">
              <label class="profile-field-label">Client Rating:</label>
              <span class="profile-field-value">
                <star-rating
                  :rating="profile.average_rating || 0"
                  :read-only="true"
                  :show-rating="true"
                />
              </span>
            </div>
          </div>
        </Card>
      </div>

      <!-- No profile state -->
      <div v-else class="text-center py-16 bg-gray-50 rounded-lg border border-gray-200">
        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
          <span class="material-icon text-gray-400">business</span>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Profile Not Set Up</h3>
        <p class="text-gray-600 mb-6">Complete your business profile to help clients understand your services better.</p>
        <button @click="$emit('edit-profile')" class="btn-filled">
          <span class="material-icon-sm mr-2">edit</span>
          Set Up Profile
        </button>
      </div>
    </div>
  </CollapsibleSection>
</template>

<script>
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'BusinessProfileSectionSP',
  components: {
    CollapsibleSection,
    Card,
    LoadingState,
    StatusBadge
  },
  props: {
    expanded: {
      type: Boolean,
      default: false
    },
    profile: {
      type: Object,
      default: null
    },
    profileCompleteness: {
      type: Number,
      default: 0
    }
  },
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.profile-field-grid {
  display: grid;
  gap: 0.75rem;
}

.profile-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.profile-field-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.profile-field-value {
  font-size: 0.875rem;
  color: #374151;
  line-height: 1.4;
}

.profile-field-value {
  word-break: break-word;
}

/* Star rating component placeholder styles */
.star-rating {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.star-rating .stars {
  display: flex;
  gap: 0.125rem;
}

.star-rating .star {
  font-size: 1rem;
  color: #fbbf24;
}

.star-rating .rating-text {
  font-size: 0.875rem;
  color: #6b7280;
  margin-left: 0.5rem;
}
</style>
