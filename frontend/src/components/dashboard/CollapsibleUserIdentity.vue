<template>
  <div class="collapsible-user-identity">
    <!-- Compact Identity Bar - Default State -->
    <div class="identity-bar" @click="toggleExpanded">
      <div class="identity-compact">
        <!-- User Avatar -->
        <div class="user-avatar">
          <span class="material-icon">{{ userInitial }}</span>
        </div>

        <!-- Compact User Info -->
        <div class="user-info-compact">
          <div class="user-name">{{ userDisplayName }}</div>
          <div v-if="showRole" class="user-role">{{ userRoleDisplay }}</div>
        </div>

        <!-- Profile Progress Indicator -->
        <div v-if="showProfileCompleteness && profileCompleteness > 0" class="profile-indicator">
          <div class="mini-progress-bar">
            <div class="progress-fill" :style="{ width: profileCompleteness + '%' }"></div>
          </div>
          <span class="mini-percentage">{{ profileCompleteness }}%</span>
        </div>

        <!-- Expand Toggle Icon -->
        <button class="expand-toggle" :class="{ expanded: isExpanded }">
          <span class="material-icon-sm">expand_more</span>
        </button>
      </div>
    </div>

    <!-- Expanded Details - Hidden by Default -->
    <div v-show="isExpanded" class="identity-details">
      <div class="details-grid">
        <!-- Account Information -->
        <div class="detail-section">
          <div class="section-header">
            <span class="material-icon-sm" :class="getSectionIconClass('account')">{{ getSectionIcon('account') }}</span>
            <span class="section-title">Account Information</span>
          </div>
          <div class="detail-items">
            <div class="detail-item">
              <span class="label">Name:</span>
              <span class="value">{{ userDisplayName }}</span>
            </div>
            <div v-if="userRole" class="detail-item">
              <span class="label">Role:</span>
              <span class="role-badge" :class="getRoleBadgeClass(userRole)">
                {{ getRoleDisplayName(userRole) }}
              </span>
            </div>
            <div v-if="organizationName" class="detail-item">
              <span class="label">Organization:</span>
              <span class="value">{{ organizationName }}</span>
            </div>
          </div>
        </div>

        <!-- Profile Status -->
        <div v-if="showProfileCompleteness" class="detail-section">
          <div class="section-header">
            <span class="material-icon-sm" :class="getSectionIconClass('profile')">{{ getSectionIcon('profile') }}</span>
            <span class="section-title">Profile Status</span>
          </div>
          <div class="detail-items">
            <div class="detail-item">
              <span class="label">Complete:</span>
              <div class="progress-display">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: profileCompleteness + '%' }"></div>
                </div>
                <span class="percentage">{{ profileCompleteness }}%</span>
              </div>
            </div>
            <div v-if="profileCompleteness < 100" class="detail-item">
              <button @click="$emit('edit-profile')" class="action-btn primary">
                <span class="material-icon-sm">edit</span>
                <span class="btn-text">Complete Profile</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Subscription -->
        <div class="detail-section">
          <div class="section-header">
            <span class="material-icon-sm" :class="getSectionIconClass('subscription')">{{ getSectionIcon('subscription') }}</span>
            <span class="section-title">Subscription</span>
          </div>
          <div class="detail-items">
            <div class="detail-item">
              <span class="label">Plan:</span>
              <span class="subscription-badge">Free Plan</span>
            </div>
            <div class="detail-item">
              <button @click="$emit('upgrade')" class="action-btn premium">
                <span class="material-icon-sm">upgrade</span>
                <span class="btn-text">Upgrade to Premium</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="detail-section">
          <div class="section-header">
            <span class="material-icon-sm" :class="getSectionIconClass('actions')">{{ getSectionIcon('actions') }}</span>
            <span class="section-title">Quick Actions</span>
          </div>
          <div class="quick-actions">
            <button
              v-if="canInviteUsers"
              @click="$emit('navigate', '/create-invitation')"
              class="action-btn outline"
            >
              <span class="material-icon-sm">person_add</span>
              <span class="btn-text">Invite Users</span>
            </button>
            <button @click="$emit('navigate', '/edit-profile')" class="action-btn outline">
              <span class="material-icon-sm">edit</span>
              <span class="btn-text">Edit Profile</span>
            </button>
            <button @click="handleSignOut" class="action-btn danger">
              <span class="material-icon-sm">logout</span>
              <span class="btn-text">Sign Out</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Collapse Button -->
      <div class="collapse-footer">
        <button @click="toggleExpanded" class="collapse-btn">
          <span class="material-icon-sm">expand_less</span>
          <span class="btn-text">Collapse</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CollapsibleUserIdentity',
  props: {
    // User information
    userDisplayName: {
      type: String,
      default: () => {
        // Fallback to get user data from localStorage if not provided
        try {
          const userData = localStorage.getItem('user')
          if (userData) {
            const user = JSON.parse(userData)
            if (user.first_name && user.last_name) {
              return `${user.first_name} ${user.last_name}`.trim()
            }
            return user.username || 'User'
          }
        } catch (error) {
          console.warn('Failed to get user data for CollapsibleUserIdentity:', error)
        }
        return 'User'
      },
      required: false
    },

    userRole: {
      type: Number,
      default: null
    },

    roleDisplayNames: {
      type: Object,
      default: () => ({})
    },

    // Organization
    organizationName: {
      type: String,
      default: ''
    },

    // Profile completion
    profileCompleteness: {
      type: Number,
      default: 0
    },

    showProfileCompleteness: {
      type: Boolean,
      default: true
    },

    showRole: {
      type: Boolean,
      default: true
    },

    canInviteUsers: {
      type: Boolean,
      default: false
    },

    // Behavior settings
    autoExpandOnMount: {
      type: Boolean,
      default: false
    },

    enableAnimations: {
      type: Boolean,
      default: true
    },

    enableSwipeGestures: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      isExpanded: false,
      touchStartY: 0,
      touchEndY: 0
    }
  },

  computed: {
    userInitial() {
      return this.userDisplayName ? this.userDisplayName.charAt(0).toUpperCase() : 'U'
    },

    userRoleDisplay() {
      return this.getRoleDisplayName(this.userRole)
    }
  },

  mounted() {
    if (this.autoExpandOnMount) {
      this.isExpanded = true
    }

    // Add swipe listeners for mobile interaction
    if (this.enableSwipeGestures) {
      this.addSwipeListeners()
    }

    // Close expanded view when clicking outside
    document.addEventListener('click', this.handleOutsideClick)
  },

  beforeUnmount() {
    this.removeSwipeListeners()
    document.removeEventListener('click', this.handleOutsideClick)
  },

  methods: {
    toggleExpanded() {
      this.isExpanded = !this.isExpanded
      this.$emit('toggle', this.isExpanded)
    },

    addSwipeListeners() {
      const element = this.$el.querySelector('.identity-bar')
      if (element) {
        element.addEventListener('touchstart', this.handleTouchStart, { passive: false })
        element.addEventListener('touchmove', this.handleTouchMove, { passive: false })
        element.addEventListener('touchend', this.handleTouchEnd, { passive: false })
      }
    },

    removeSwipeListeners() {
      const element = this.$el.querySelector('.identity-bar')
      if (element) {
        element.removeEventListener('touchstart', this.handleTouchStart)
        element.removeEventListener('touchmove', this.handleTouchMove)
        element.removeEventListener('touchend', this.handleTouchEnd)
      }
    },

    handleTouchStart(e) {
      this.touchStartY = e.touches[0].clientY
    },

    handleTouchMove(e) {
      if (!this.touchStartY) return

      const touchY = e.touches[0].clientY
      const diffY = this.touchStartY - touchY

      // Prevent scrolling if we're doing a significant vertical swipe
      if (Math.abs(diffY) > 20) {
        e.preventDefault()
      }
    },

    handleTouchEnd(e) {
      if (!this.touchStartY) return

      const touchEndY = e.changedTouches[0].clientY
      const diffY = this.touchStartY - touchEndY
      const minSwipeDistance = 50

      // Swipe up to expand
      if (!this.isExpanded && diffY > minSwipeDistance) {
        this.toggleExpanded()
      }
      // Swipe down to collapse
      else if (this.isExpanded && diffY < -minSwipeDistance) {
        this.toggleExpanded()
      }

      this.touchStartY = 0
    },

    handleOutsideClick(e) {
      // Only close on mobile and when clicking outside the component
      if (window.innerWidth < 768 && this.isExpanded && !this.$el.contains(e.target)) {
        // Small delay to allow button clicks to process
        setTimeout(() => {
          if (this.isExpanded) {
            this.isExpanded = false
            this.$emit('toggle', false)
          }
        }, 100)
      }
    },

    getRoleDisplayName(roleId) {
      if (this.roleDisplayNames && this.roleDisplayNames[roleId]) {
        return this.roleDisplayNames[roleId]
      }

      switch (roleId) {
        case 1: return 'Reporting Employee'
        case 2: return 'Site Budget Controller'
        case 3: return 'Service Provider Admin'
        case 4: return 'Service Provider Technician'
        default: return `Role ${roleId}`
      }
    },

    getRoleBadgeClass(role) {
      return role === 2 || role === 3 ? 'role-admin' : 'role-user'
    },

    getSectionIcon(section) {
      const icons = {
        account: 'person',
        profile: 'verified_user',
        subscription: 'workspace_premium',
        actions: 'settings'
      }
      return icons[section] || 'info'
    },

    getSectionIconClass(section) {
      const classes = {
        account: 'text-blue-600',
        profile: 'text-green-600',
        subscription: 'text-purple-600',
        actions: 'text-gray-600'
      }
      return classes[section] || 'text-gray-500'
    },

    handleSignOut() {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      this.$router.push('/')
    }
  },

  emits: ['navigate', 'edit-profile', 'upgrade', 'toggle']
}
</script>

<style scoped>
.collapsible-user-identity {
  width: 100%;
  margin-bottom: 16px;
}

/* Compact Identity Bar */
.identity-bar {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.identity-bar:hover {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.identity-compact {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1.1rem;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-info-compact {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.95rem;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.profile-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
}

.mini-progress-bar {
  width: 40px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.mini-progress-bar .progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.mini-percentage {
  font-size: 0.75rem;
  color: #374151;
  font-weight: 600;
}

.expand-toggle {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  transition: all 0.3s ease;
}

.expand-toggle:hover {
  background: rgba(255, 255, 255, 1);
  color: #374151;
}

.expand-toggle.expanded {
  transform: rotate(180deg);
}

/* Expanded Details Section */
.identity-details {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 12px 12px;
  padding: 16px;
  animation: slideDownFade 0.3s ease-out;
  margin-top: -1px; /* Seamless border connection */
}

@keyframes slideDownFade {
  from {
    opacity: 0;
    transform: translateY(-8px);
    max-height: 0;
  }
  to {
    opacity: 1;
    transform: translateY(0);
    max-height: 1000px; /* Large enough for content */
  }
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

.detail-section {
  background: white;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.section-title {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.detail-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
}

.detail-item .label {
  font-size: 0.85rem;
  color: #6b7280;
  font-weight: 500;
}

.detail-item .value {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
  text-align: right;
  flex: 1;
  margin-left: 12px;
}

.role-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  text-align: center;
}

.role-badge.role-admin {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1e40af;
}

.role-badge.role-user {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #166534;
}

.subscription-badge {
  padding: 4px 10px;
  background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
  color: #7c3aed;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.progress-display {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  margin-left: 12px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar .progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.percentage {
  font-size: 0.85rem;
  color: #374151;
  font-weight: 600;
  min-width: 36px;
  text-align: right;
}

/* Action Buttons */
.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  width: 100%;
}

.action-btn.primary {
  background: #2563eb;
  color: white;
  border: none;
}

.action-btn.primary:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.action-btn.premium {
  background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
  color: #7c3aed;
  border: 1px solid #d8b4fe;
  font-weight: 600;
}

.action-btn.premium:hover {
  background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
  transform: translateY(-1px);
}

.action-btn.outline {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.action-btn.outline:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.action-btn.danger {
  background: #dc2626;
  color: white;
  border: none;
}

.action-btn.danger:hover {
  background: #b91c1c;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* Collapse Footer */
.collapse-footer {
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: center;
}

.collapse-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: none;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  color: #6b7280;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.collapse-btn:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

/* Tablet and up */
@media (min-width: 768px) {
  .details-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }

  .detail-section {
    padding: 20px;
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .section-title {
    font-size: 0.95rem;
  }
}

/* Touch targets for mobile */
@media (max-width: 767px) {
  .identity-bar {
    min-height: 64px;
  }

  .expand-toggle {
    min-width: 44px;
    min-height: 44px;
  }

  .action-btn,
  .collapse-btn {
    min-height: 44px;
  }
}

/* Reduced motion for accessibility */
@media (prefers-reduced-motion: reduce) {
  .identity-bar,
  .identity-details,
  .expand-toggle,
  .action-btn,
  .collapse-btn {
    transition: none;
    animation: none;
  }

  .identity-details {
    animation: none !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .identity-bar {
    border: 2px solid;
  }

  .detail-section {
    border: 2px solid;
  }

  .user-avatar {
    border: 2px solid white;
  }
}

/* Print styles */
@media print {
  .identity-details {
    display: none !important;
  }

  .expand-toggle {
    display: none !important;
  }
}
</style>
