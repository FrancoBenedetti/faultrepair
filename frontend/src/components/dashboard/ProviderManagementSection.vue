<template>
  <!-- Action buttons -->
  <div class="section-header-actions mb-6" v-if="isAdmin">
    <button @click="$emit('add-xs-provider')" class="btn-filled mr-2">
      <span class="material-icon-sm mr-2">add</span>
      Add External Provider
    </button>
    <button @click="$emit('browse-providers')" class="btn-outlined">
      <span class="material-icon-sm mr-2">search</span>
      Browse Platform Providers
    </button>
  </div>

  <!-- Loading state -->
  <LoadingState v-if="!approvedProviders" message="Loading providers..." fullWidth />

  <!-- Provider cards -->
  <div v-else-if="approvedProviders && approvedProviders.length > 0" class="providers-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Existing provider cards -->
    <Card
      v-for="provider in approvedProviders"
      :key="provider.id"
      clickable
      @click="$emit('view-provider-jobs', provider)"
    >
      <template #header>
        <div class="provider-logo w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
          <span class="material-icon text-on-primary">{{ provider.name.charAt(0).toUpperCase() }}</span>
        </div>
        <div class="provider-actions flex gap-2">
          <button @click.stop="$emit('view-provider-jobs', provider)" class="btn-outlined btn-small">
            <span class="material-icon-sm">visibility</span>
          </button>
        </div>
      </template>

      <template #content>
        <div class="provider-header-row flex items-center justify-between mb-2">
          <h3 class="provider-name text-title-medium text-on-surface">{{ provider.name }}</h3>
          <span
            :class="getProviderTypeClass(provider.provider_type)"
            class="provider-type-badge text-xs font-medium px-2 py-1 rounded-full uppercase tracking-wide"
          >
            {{ getProviderTypeLabel(provider.provider_type) }}
          </span>
        </div>
        <p class="provider-address text-body-medium text-on-surface-variant mb-4">{{ provider.address }}</p>

        <div class="provider-stats grid grid-cols-3 gap-3">
          <div class="stat-item text-center">
            <span class="stat-number text-xl font-bold text-on-surface">{{ provider.total_jobs }}</span>
            <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Total</span>
          </div>
          <div class="stat-item text-center">
            <span class="stat-number text-xl font-bold text-on-surface">{{ provider.active_jobs }}</span>
            <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Active</span>
          </div>
          <div class="stat-item text-center">
            <span class="stat-number text-xl font-bold text-on-surface">{{ provider.completed_jobs }}</span>
            <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Completed</span>
          </div>
        </div>
      </template>

      <template #footer>
        <div class="approval-date text-label-medium text-on-surface-variant">
          <span class="material-icon-sm mr-1">check_circle</span>
          Approved {{ formatDate(provider.approved_at) }}
        </div>
      </template>
    </Card>
  </div>

  <!-- No providers state -->
  <ErrorState
    v-else-if="approvedProviders && approvedProviders.length === 0"
    title="No approved providers"
    message="Browse and approve service providers to get started."
    icon="handshake"
  >
    <button v-if="isAdmin" @click="$emit('browse-providers')" class="btn-outlined ml-6 mt-4">
      <span class="material-icon-sm mr-2">search</span>
      Browse Providers
    </button>
  </ErrorState>

  <!-- Error state -->
  <ErrorState
    v-else
    title="Failed to load providers"
    message="Please try refreshing the page."
    icon="error"
  />
</template>

<script>
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'

export default {
  name: 'ProviderManagementSection',
  components: {
    Card,
    LoadingState,
    ErrorState
  },
  props: {
    expanded: {
      type: Boolean,
      default: false
    },
    approvedProviders: {
      type: Array,
      default: null
    },
    isAdmin: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showAddXSProviderModal: false
    }
  },
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },

    getProviderTypeClass(providerType) {
      switch (providerType) {
        case 'S':
          return 'bg-blue-100 text-blue-800 border-blue-200'
        case 'XS':
          return 'bg-green-100 text-green-800 border-green-200'
        default:
          return 'bg-gray-100 text-gray-800 border-gray-200'
      }
    },

    getProviderTypeLabel(providerType) {
      switch (providerType) {
        case 'S':
          return 'Platform'
        case 'XS':
          return 'External'
        default:
          return 'Unknown'
      }
    }
  }
}
</script>

<style scoped>
.providers-grid {
  margin-top: 15px;
}

.provider-logo {
  flex-shrink: 0;
}

.provider-name {
  margin: 0 0 8px 0;
}

.provider-address {
  margin: 0 0 16px 0;
}

.provider-stats {
  display: flex;
  gap: 15px;
  margin-top: 12px;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 1.5em;
  font-weight: bold;
  color: #333;
  margin-bottom: 2px;
}

.stat-label {
  font-size: 0.8em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.approval-date {
  margin: 0;
  text-align: left;
}

.btn-filled {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  background: #007bff;
  color: white;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-filled:hover {
  background: #0056b3;
}

.btn-outlined {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 14px;
  color: #666;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-outlined:hover {
  background: #f0f0f0;
  color: #333;
}

.btn-small {
  padding: 6px;
  font-size: 0.9em;
}

@media (max-width: 768px) {
  .providers-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }

  .provider-logo {
    width: 48px;
    height: 48px;
  }

  .provider-stats {
    flex-direction: column;
    gap: 12px;
  }

  .provider-stats.grid-cols-3 {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
