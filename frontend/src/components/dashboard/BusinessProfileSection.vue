<template>
  <!-- Loading state -->
  <LoadingState v-if="!clientProfile" message="Loading profile..." />

  <!-- Profile content - conditionally shown based on expanded prop -->
  <div v-else-if="clientProfile && expanded" class="profile-content">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Basic Information Card -->
      <Card>
        <template #header>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="material-icon-sm text-white">business_center</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
          </div>
        </template>

        <template #content>
          <div class="profile-fields">
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Business Name:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ clientProfile.name || 'Not specified' }}</span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Address:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ clientProfile.address || 'Not specified' }}</span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Website:</span>
              <span class="field-value text-sm text-right break-all">
                <a v-if="clientProfile.website"
                   :href="clientProfile.website"
                   target="_blank"
                   class="text-blue-600 hover:text-blue-800 underline">
                  {{ clientProfile.website }}
                </a>
                <span v-else class="text-gray-900">Not specified</span>
              </span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Description:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ clientProfile.description || 'Not specified' }}</span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Manager Contact Card -->
      <Card>
        <template #header>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="material-icon-sm text-white">contact_phone</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Manager Contact</h3>
          </div>
        </template>

        <template #content>
          <div class="profile-fields">
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Manager Name:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ clientProfile.manager_name || 'Not specified' }}</span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Manager Email:</span>
              <span class="field-value text-sm text-right break-all">
                <a v-if="clientProfile.manager_email"
                   :href="`mailto:${clientProfile.manager_email}`"
                   class="text-blue-600 hover:text-blue-800 underline">
                  {{ clientProfile.manager_email }}
                </a>
                <span v-else class="text-gray-900">Not specified</span>
              </span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Manager Phone:</span>
              <span class="field-value text-sm text-right">
                <a v-if="clientProfile.manager_phone"
                   :href="`tel:${clientProfile.manager_phone}`"
                   class="text-blue-600 hover:text-blue-800 underline">
                  {{ clientProfile.manager_phone }}
                </a>
                <span v-else class="text-gray-900">Not specified</span>
              </span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Business Details Card -->
      <Card>
        <template #header>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="material-icon-sm text-white">assignment</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Business Details</h3>
          </div>
        </template>

        <template #content>
          <div class="profile-fields">
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">VAT Number:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ clientProfile.vat_number || 'Not specified' }}</span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Registration:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ clientProfile.business_registration_number || 'Not specified' }}</span>
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Status:</span>
              <StatusBadge :status="clientProfile.is_active ? 'Active' : 'Inactive'" />
            </div>
            <div class="field-item flex justify-between items-start">
              <span class="field-label text-sm font-medium text-gray-500">Member Since:</span>
              <span class="field-value text-sm text-gray-900 text-right">{{ formatDate(clientProfile.created_at) }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>

  <!-- No profile state - only show when expanded -->
  <ErrorState
    v-else-if="!clientProfile && expanded"
    title="Profile Not Set Up"
    message="Complete your business profile to help service providers understand your organization better."
    icon="business"
  >
    <button @click="$emit('edit-profile')" class="btn-filled">
      <span class="material-icon-sm mr-2">edit</span>
      Set Up Profile
    </button>
  </ErrorState>

  <!-- Empty space when collapsed and has profile - maintains layout consistency -->
  <div v-else-if="clientProfile && !expanded" class="hidden"></div>
</template>

<script>
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'BusinessProfileSection',
  components: {
    Card,
    LoadingState,
    ErrorState,
    StatusBadge
  },
  props: {
    expanded: {
      type: Boolean,
      default: false
    },
    clientProfile: {
      type: Object,
      default: null
    },
    clientProfileCompleteness: {
      type: Number,
      default: 0
    },
    isAdmin: {
      type: Boolean,
      default: false
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
.profile-fields {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.field-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.field-item:last-child {
  border-bottom: none;
}

.field-label {
  flex-shrink: 0;
  margin-right: 12px;
}

.field-value {
  text-align: right;
  word-break: break-word;
}

@media (max-width: 768px) {
  .profile-content .grid {
    grid-template-columns: 1fr;
  }

  .field-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .field-value {
    text-align: left;
  }
}
</style>
