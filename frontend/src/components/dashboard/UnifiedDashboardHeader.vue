<template>
  <header class="unified-dashboard-header">
    <!-- Mobile Header -->
    <div class="mobile-header" v-if="isMobile">
      <button @click="toggleMobileMenu" class="hamburger-btn">
        <span class="material-icon-sm">menu</span>
      </button>

      <div class="mobile-title">
        <div class="title-with-icon">
          <span class="dashboard-icon" :class="getDashboardIconClass()">
            <span class="material-icon-sm">{{ getDashboardIcon() }}</span>
          </span>
          <h1 class="org-name">{{ orgName }}</h1>
        </div>
        <div class="mobile-tagline">{{ mobileDashboardLabel }}</div>
      </div>

      <div class="mobile-actions">
        <!-- Primary CTA for mobile -->
        <button
          @click="handlePrimaryAction"
          class="btn-primary-mobile"
          :class="{ disabled: isPrimaryDisabled }"
        >
          <span class="material-icon-sm">{{ getPrimaryActionIcon() }}</span>
          <span class="btn-text">{{ primaryActionText }}</span>
        </button>

        <!-- User menu dropdown -->
        <div class="user-menu-dropdown" v-if="userMenuOpen">
          <button @click="toggleUserMenu" class="user-avatar-btn">
            <div class="user-avatar">
              <span class="material-icon">{{ getCurrentUserName().charAt(0).toUpperCase() }}</span>
            </div>
            <span class="material-icon-sm">expand_more</span>
          </button>

          <div v-if="userMenuExpanded" class="user-menu-content">
            <div class="user-info-item">
              <span class="label">Signed in as:</span>
              <span class="value">{{ getCurrentUserName() }}</span>
            </div>
            <div class="user-info-item" v-if="userRole">
              <span class="label">Role:</span>
              <span class="role-badge" :class="getRoleBadgeClass(userRole)">
                {{ roleDisplayNames && roleDisplayNames[userRole] ? roleDisplayNames[userRole] : getFallbackRoleName(userRole) }}
              </span>
            </div>
            <div class="user-info-item">
              <span class="label">Organization:</span>
              <span class="value">{{ orgName }}</span>
            </div>
            <div class="user-info-item" v-if="showProfileCompleteness">
              <span class="label">Profile:</span>
              <div class="profile-completeness-mini">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: profileCompleteness + '%' }"></div>
                </div>
                <span class="percentage">{{ profileCompleteness }}%</span>
              </div>
            </div>
            <div class="user-menu-actions">
              <button @click="handleUpgradeClick" v-if="showUpgradeButton" class="menu-action-btn upgrade-btn">
                <span class="material-icon-sm">upgrade</span>
                Upgrade to Premium
              </button>
              <button @click.stop="$emit('navigate', '/create-invitation')" v-if="showInviteUsers" class="menu-action-btn">
                <span class="material-icon-sm">person_add</span>
                Invite Users
              </button>
              <button @click="signOut" class="menu-action-btn sign-out-btn">
                <span class="material-icon-sm">logout</span>
                Sign Out
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Hamburger Menu Overlay -->
      <div v-if="mobileMenuOpen" class="mobile-menu-overlay" @click="closeMobileMenu">
        <div class="mobile-menu-content" @click.stop>
          <div class="menu-header">
            <h3>Menu</h3>
            <button @click="closeMobileMenu" class="close-btn">×</button>
          </div>
          <div class="menu-body">
            <!-- Role and Profile Info -->
            <div class="menu-section">
              <div class="menu-item">
                <span class="material-icon-sm text-blue-600">person</span>
                <div>
                  <div class="item-title">{{ getCurrentUserName() }}</div>
                  <div class="item-subtitle">{{ roleDisplayNames && roleDisplayNames[userRole] ? roleDisplayNames[userRole] : getFallbackRoleName(userRole) }}</div>
                </div>
              </div>
              <div class="menu-item" v-if="showProfileCompleteness">
                <span class="material-icon-sm text-green-600">verified_user</span>
                <div>
                  <div class="item-title">Profile Complete</div>
                  <div class="item-subtitle">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: profileCompleteness + '%' }"></div>
                    </div>
                    <span class="percentage">{{ profileCompleteness }}%</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Primary Actions -->
            <div class="menu-section">
              <h4 class="menu-section-title">Actions</h4>
              <button @click="handlePrimaryAction" class="menu-primary-btn" :class="{ disabled: isPrimaryDisabled }">
                <span class="material-icon-sm">{{ getPrimaryActionIcon() }}</span>
                {{ primaryActionText }}
              </button>
            </div>

            <!-- Secondary Actions -->
            <div class="menu-section">
              <h4 class="menu-section-title">More Options</h4>
              <button @click="handleUpgradeClick" v-if="showUpgradeButton" class="menu-secondary-btn">
                <span class="material-icon-sm">upgrade</span>
                Upgrade to Premium
              </button>
              <button @click.stop="$emit('navigate', '/create-invitation')" v-if="showInviteUsers" class="menu-secondary-btn">
                <span class="material-icon-sm">person_add</span>
                Invite Users
              </button>
              <button @click="signOut" class="menu-secondary-btn">
                <span class="material-icon-sm">logout</span>
                Sign Out
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop Header -->
    <div class="desktop-header" v-else>
      <div class="header-left">
        <h1 class="org-name">{{ orgName }}</h1>
        <div v-if="showProfileCompleteness" class="profile-completeness-desktop">
          <div class="progress-container">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: profileCompleteness + '%' }"></div>
            </div>
            <span class="percentage">{{ profileCompleteness }}% Complete</span>
          </div>
        </div>
      </div>

      <div class="header-center">
        <!-- Empty for now, could add breadcrumbs or page title -->
      </div>

      <div class="header-right">
        <!-- Primary CTA -->
        <button
          @click="handlePrimaryAction"
          class="btn-primary"
          :class="{ disabled: isPrimaryDisabled }"
          :title="primaryActionTitle"
        >
          <span class="material-icon-sm">{{ getPrimaryActionIcon() }}</span>
          {{ primaryActionText }}
        </button>

        <!-- Secondary Actions Dropdown -->
        <div class="dropdown-container">
          <button @click="toggleActionsDropdown" class="dropdown-trigger">
            <span class="material-icon-sm">more_vert</span>
          </button>

          <div v-if="actionsDropdownOpen" class="dropdown-menu">
            <button @click="handleUpgradeClick" v-if="showUpgradeButton" class="dropdown-item">
              <span class="material-icon-sm">upgrade</span>
              Upgrade to Premium
            </button>
            <button @click.stop="$emit('navigate', '/create-invitation')" v-if="showInviteUsers" class="dropdown-item">
              <span class="material-icon-sm">person_add</span>
              Invite Users
            </button>
            <button @click="signOut" class="dropdown-item">
              <span class="material-icon-sm">logout</span>
              Sign Out
            </button>
          </div>
        </div>

        <!-- User Info -->
        <div class="user-info-section">
          <div class="user-avatar" @click="toggleUserTooltip" title="Click for details">
            <span class="material-icon">{{ getCurrentUserName().charAt(0).toUpperCase() }}</span>
          </div>

          <div class="user-details">
            <div class="user-name">{{ getCurrentUserName() }}</div>
            <div class="user-role" v-if="userRole">
              {{ roleDisplayNames && roleDisplayNames[userRole] ? roleDisplayNames[userRole] : getFallbackRoleName(userRole) }}
            </div>
          </div>

          <!-- User Tooltip (shown on click/hover) -->
          <div v-if="userTooltipOpen" class="user-tooltip">
            <div class="tooltip-content">
              <div class="tooltip-item">
                <span class="label">Organization:</span>
                <span class="value">{{ orgName }}</span>
              </div>
              <div class="tooltip-item" v-if="userRole">
                <span class="label">Role:</span>
                <span class="role-badge" :class="getRoleBadgeClass(userRole)">
                  {{ roleDisplayNames && roleDisplayNames[userRole] ? roleDisplayNames[userRole] : getFallbackRoleName(userRole) }}
                </span>
              </div>
              <div class="tooltip-item" v-if="showProfileCompleteness">
                <span class="label">Profile:</span>
                <div class="profile-completeness-mini">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: profileCompleteness + '%' }"></div>
                  </div>
                  <span class="percentage">{{ profileCompleteness }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
export default {
  name: 'UnifiedDashboardHeader',
  props: {
    // Organization name to display in header
    orgName: {
      type: String,
      default: 'Organization'
    },

    // User information
    userRole: {
      type: Number,
      default: null
    },

    roleDisplayNames: {
      type: Object,
      default: () => ({})
    },

    // Profile information
    profileCompleteness: {
      type: Number,
      default: 0
    },

    showProfileCompleteness: {
      type: Boolean,
      default: true
    },

    // Dashboard type determines primary action
    dashboardType: {
      type: String,
      default: 'client', // 'client' or 'service-provider'
      validator: value => ['client', 'service-provider'].includes(value)
    },

    // Permissions and flags
    isAdmin: {
      type: Boolean,
      default: false
    },

    showUpgradeButton: {
      type: Boolean,
      default: false
    },

    showInviteUsers: {
      type: Boolean,
      default: false
    },

    // Disable primary action (e.g., if profile not enabled)
    isPrimaryDisabled: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      isMobile: false,
      mobileMenuOpen: false,
      actionsDropdownOpen: false,
      userTooltipOpen: false,
      userMenuExpanded: false,
    }
  },

  mounted() {
    this.checkMobile()
    window.addEventListener('resize', this.checkMobile)
  },

  beforeUnmount() {
    window.removeEventListener('resize', this.checkMobile)
  },

  computed: {
    primaryActionText() {
      return this.dashboardType === 'client' ? 'Service Request' : 'Create Invitation'
    },

    primaryActionTitle() {
      return this.dashboardType === 'client' ? 'Create a new service request' : 'Create an invitation for new users'
    },

    mobileDashboardLabel() {
      return this.dashboardType === 'client' ? 'Client Dashboard' : 'Service Dashboard'
    }
  },

  methods: {
    checkMobile() {
      this.isMobile = window.innerWidth < 768
    },

    // Mobile Menu Functions
    toggleMobileMenu() {
      this.mobileMenuOpen = !this.mobileMenuOpen
    },

    closeMobileMenu() {
      this.mobileMenuOpen = false
    },

    // User Menu Functions
    toggleUserMenu() {
      this.userMenuExpanded = !this.userMenuExpanded
    },

    toggleUserTooltip() {
      if (this.isMobile) {
        this.toggleUserMenu()
      } else {
        this.userTooltipOpen = !this.userTooltipOpen
      }
    },

    // Desktop Dropdown Functions
    toggleActionsDropdown() {
      this.actionsDropdownOpen = !this.actionsDropdownOpen
    },

    // Action Handlers
    handlePrimaryAction() {
      if (this.isPrimaryDisabled) return

      if (this.dashboardType === 'client') {
        this.$emit('navigate', '/client/create-job')
      } else {
        this.$emit('navigate', '/create-invitation')
      }
    },

    handleUpgradeClick() {
      alert('Upgrade to Premium feature coming soon! Contact sales for more information.')
    },

    // Utility Functions
    getPrimaryActionIcon() {
      return this.dashboardType === 'client' ? 'add' : 'person_add'
    },

    getCurrentUserName() {
      // Get current user name from localStorage
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
        console.warn('Failed to get user data for UnifiedDashboardHeader:', error)
      }

      // Fallback to 'User' if no data available
      return 'User'
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
      return role === 2 || role === 3 ? 'role-admin' : 'role-user'
    },

    getDashboardIcon() {
      return this.dashboardType === 'client' ? 'business' : 'engineering'
    },

    getDashboardIconClass() {
      const iconClasses = {
        client: 'client-icon',
        'service-provider': 'service-provider-icon'
      }
      return iconClasses[this.dashboardType] || ''
    },

    signOut() {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      this.$router.push('/')
    }
  },

  emits: ['navigate']
}
</script>

<style scoped>
.unified-dashboard-header {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

/* Mobile Header Styles */
.mobile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  min-height: 56px; /* Material Design standard */
}

.hamburger-btn {
  background: none;
  border: none;
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  color: #374151;
  transition: background-color 0.2s;
}

.hamburger-btn:hover {
  background-color: #f3f4f6;
}

.mobile-title {
  flex: 1;
  text-align: center;
}

.mobile-title .org-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mobile-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary-mobile {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary-mobile:hover:not(.disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.btn-primary-mobile.disabled {
  background: #9ca3af;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-primary-mobile .btn-text {
  display: none;
}

.btn-primary-mobile .material-icon-sm {
  font-size: 18px;
}

/* Large mobile screens - show text */
@media (min-width: 480px) {
  .btn-primary-mobile .btn-text {
    display: inline;
  }
}

.user-menu-dropdown {
  position: relative;
}

.user-avatar-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  padding: 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.user-avatar-btn:hover {
  background-color: #f3f4f6;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.user-menu-content {
  position: absolute;
  top: 100%;
  right: 0;
  min-width: 250px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  margin-top: 4px;
  max-height: 80vh;
  overflow-y: auto;
}

.user-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  border-bottom: 1px solid #f3f4f6;
}

.user-info-item:last-child {
  border-bottom: none;
}

.user-info-item .label {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.user-info-item .value {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

.role-badge {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.role-badge.role-admin {
  background: #dbeafe;
  color: #1e40af;
}

.role-badge.role-user {
  background: #dcfce7;
  color: #166534;
}

.profile-completeness-mini {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-completeness-mini .progress-bar {
  width: 60px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.profile-completeness-mini .progress-fill {
  height: 100%;
  background: #10b981;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.user-menu-actions {
  padding: 12px;
  border-top: 1px solid #e5e7eb;
}

.menu-action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 0;
  background: none;
  border: none;
  text-align: left;
  color: #374151;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.menu-action-btn:hover {
  background-color: #f9fafb;
}

.menu-action-btn.sign-out-btn {
  color: #dc2626;
}

.menu-action-btn.upgrade-btn {
  color: #7c3aed;
  background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
  border-radius: 6px;
  margin-bottom: 8px;
  justify-content: center;
  font-weight: 600;
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
  position: fixed;
  top: 56px;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1002;
}

.mobile-menu-content {
  height: 100%;
  background: white;
  width: 280px;
  max-width: 85vw;
}

.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.menu-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
}

.menu-body {
  padding: 16px 0;
}

.menu-section {
  margin-bottom: 24px;
}

.menu-section-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 0 20px;
  margin-bottom: 12px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
}

.menu-item .item-title {
  font-weight: 500;
  color: #111827;
}

.menu-item .item-subtitle {
  font-size: 0.8rem;
  color: #6b7280;
}

.menu-primary-btn {
  width: calc(100% - 40px);
  margin: 0 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.menu-primary-btn:hover:not(.disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.menu-primary-btn.disabled {
  background: #9ca3af;
  cursor: not-allowed;
  opacity: 0.6;
}

.menu-secondary-btn {
  width: calc(100% - 40px);
  margin: 0 20px 4px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: none;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  color: #374151;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.menu-secondary-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

/* Desktop Header Styles */
.desktop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  min-height: 72px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.org-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.profile-completeness-desktop {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-completeness-desktop .progress-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-completeness-desktop .progress-bar {
  width: 120px;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.profile-completeness-desktop .progress-fill {
  height: 100%;
  background: #10b981;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.percentage {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
}

.header-center {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(.disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.btn-primary.disabled {
  background: #9ca3af;
  cursor: not-allowed;
  opacity: 0.6;
}

.dropdown-container {
  position: relative;
}

.dropdown-trigger {
  background: none;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 8px;
  cursor: pointer;
  color: #6b7280;
  transition: background-color 0.2s;
}

.dropdown-trigger:hover {
  background-color: #f9fafb;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  min-width: 180px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  margin-top: 4px;
}

.dropdown-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: none;
  border: none;
  color: #374151;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.dropdown-item:hover {
  background-color: #f9fafb;
}

.user-info-section {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 6px 8px;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.user-info-section:hover {
  background-color: #f9fafb;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1.1rem;
  cursor: help;
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-weight: 500;
  color: #111827;
  font-size: 0.9rem;
}

.user-role {
  font-size: 0.8rem;
  color: #6b7280;
}

.user-tooltip {
  position: absolute;
  top: 100%;
  right: 0;
  min-width: 200px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  margin-top: 8px;
  padding: 12px;
}

.tooltip-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tooltip-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.tooltip-item .label {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.tooltip-item .value {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

/* Responsive Design */
@media (max-width: 767px) {
  .desktop-header {
    display: none !important;
  }

  .mobile-header {
    display: flex !important;
  }
}

@media (min-width: 768px) {
  .desktop-header {
    display: flex !important;
  }

  .mobile-header {
    display: none !important;
  }
}

/* Tablet adjustments (768px - 1024px) */
@media (min-width: 768px) and (max-width: 1024px) {
  .org-name {
    font-size: 1.3rem;
  }

  .header-left {
    gap: 12px;
  }

  .header-right {
    gap: 12px;
  }
}

/* Large screens */
@media (min-width: 1024px) {
  .desktop-header {
    padding: 20px 32px;
    min-height: 80px;
  }

  .org-name {
    font-size: 1.5rem;
  }
}

/* Touch targets for mobile */
@media (max-width: 767px) {
  .hamburger-btn,
  .user-avatar-btn,
  .dropdown-trigger,
  .btn-primary-mobile {
    min-height: 44px; /* iOS touch target guideline */
    min-width: 44px;
  }

  .menu-primary-btn,
  .menu-secondary-btn,
  .menu-action-btn {
    min-height: 44px;
  }
}

/* Dark mode support (if needed later) */
@media (prefers-color-scheme: dark) {
  /* Placeholder for dark mode styles */
}

/* Print styles */
@media print {
  .unified-dashboard-header {
    display: none;
  }
}
</style>
