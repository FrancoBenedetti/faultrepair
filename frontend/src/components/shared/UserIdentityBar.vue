<template>
  <div class="user-identity-bar">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
      <div class="user-info flex items-center gap-4">
        <div class="user-avatar flex-shrink-0">
          <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
            <span class="material-icon text-white">{{ getCurrentUserName().charAt(0).toUpperCase() }}</span>
          </div>
        </div>
        <div class="identity-details">
          <div class="signed-in-user flex items-center gap-2 mb-1">
            <span class="material-icon-sm text-blue-600">person</span>
            <span class="text-sm font-medium text-gray-700">Signed in as:</span>
            <span class="text-sm font-semibold text-gray-900">{{ getCurrentUserName() }}</span>
            <span class="user-role-badge" :class="getRoleBadgeClass(userRole)">
              {{ roleDisplayNames && roleDisplayNames[userRole] ? roleDisplayNames[userRole] : getFallbackRoleName(userRole) }}
            </span>
          </div>
          <div class="organization-info flex items-center gap-2">
            <span class="material-icon-sm text-indigo-600">business</span>
            <span class="text-sm font-medium text-gray-700">Organization:</span>
            <span class="text-sm font-semibold text-gray-900">{{ organizationName }}</span>
          </div>
        </div>
      </div>
      <div class="subscription-info flex items-center gap-4">
        <div v-if="subscription" class="subscription-badge flex items-center gap-2 px-3 py-1 bg-white border border-gray-300 rounded-full">
          <span class="material-icon-sm text-green-600">workspace_premium</span>
          <span class="text-xs font-medium text-gray-700">{{ getSubscriptionDisplayName() }}</span>
        </div>
        <div v-if="currentUsage && limits" class="text-xs text-gray-500">
          {{ currentUsage.jobs }}/{{ limits.jobs_per_year }} jobs used
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { loadRoleSettings } from '@/utils/api.js'

export default {
  name: 'UserIdentityBar',
  props: {
    userRole: {
      type: Number,
      required: true
    },
    userName: {
      type: String,
      default: null
    },
    organizationName: {
      type: String,
      required: true
    },
    subscription: {
      type: Object,
      default: null
    },
    currentUsage: {
      type: Object,
      default: null
    },
    limits: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      roleDisplayNames: {} // Add this for role settings
    }
  },
  async mounted() {
    try {
      const settings = await loadRoleSettings()
      this.roleDisplayNames = settings || {}
    } catch (error) {
      console.warn('Failed to load role settings:', error)
      this.roleDisplayNames = {}
    }
  },
  methods: {
    getCurrentUserName() {
      // Use provided userName prop first, fallback to localStorage
      if (this.userName) {
        return this.userName
      }

      // Fallback to localStorage method
      try {
        const userData = localStorage.getItem('user')
        if (userData) {
          const user = JSON.parse(userData)
          if (user.first_name && user.last_name) {
            return `${user.first_name} ${user.last_name}`
          }
          return user.username || 'User'
        }
        return 'User'
      } catch (error) {
        console.error('Error getting current user name:', error)
        return 'User'
      }
    },

    getFallbackRoleName(roleId) {
      switch (roleId) {
        case 1:
          return 'Reporting Employee'
        case 2:
          return 'Site Budget Controller'
        case 3:
          return 'Service Provider Admin'
        case 4:
          return 'Service Provider Technician'
        default:
          return `Role ${roleId}`
      }
    },

    getRoleBadgeClass(role) {
      if (role === 3) return 'role-admin'
      if (role === 4) return 'role-technician'
      return role === 2 ? 'role-admin' : 'role-user'
    },

    getSubscriptionDisplayName() {
      if (!this.subscription) return 'Free Plan'

      const tierMap = {
        'free': 'Free Plan',
        'basic': 'Basic Plan',
        'advanced': 'Advanced Plan',
        'premium': 'Premium Plan'
      }

      return tierMap[this.subscription.subscription_tier] || this.subscription.subscription_tier
    }
  }
}
</script>

<style scoped>
.user-identity-bar {
  background: linear-gradient(135deg,
    rgba(79, 70, 229, 0.1) 0%,
    rgba(59, 130, 246, 0.1) 100%
  );
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.user-role-badge {
  padding: 2px 8px;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}

.role-admin {
  background: rgba(34, 197, 94, 0.15);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.role-technician {
  background: rgba(245, 101, 101, 0.15);
  color: #dc2626;
  border: 1px solid rgba(245, 101, 101, 0.3);
}

.role-user {
  background: rgba(59, 130, 246, 0.15);
  color: #2563eb;
  border: 1px solid rgba(59, 130, 246, 0.3);
}
</style>
