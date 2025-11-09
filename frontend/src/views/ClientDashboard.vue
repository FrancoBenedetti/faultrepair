<template>
  <div class="client-dashboard">
    <!-- Unified Dashboard Header -->
    <UnifiedDashboardHeader
      :org-name="getOrganizationName()"
      :user-name="getCurrentUserName()"
      :user-role="userRole"
      :role-display-names="roleDisplayNames"
      :profile-completeness="profileCompleteness"
      dashboard-type="client"
      :is-admin="isAdmin"
      :show-invite-users="isAdmin"
      :is-primary-disabled="!clientProfile?.is_enabled"
      @navigate="handleNavigate"
    />

    <div class="dashboard-content space-y-6">
      <!-- Administrator Settings Section - Only for budget controllers (role 2) -->
      <div class="admin-settings-container bg-white rounded-xl shadow-lg border border-gray-200 p-0 mb-8" v-if="userRole === 2 && clientProfile?.is_enabled">
        <div class="admin-section-header rounded-t-xl bg-white rounded-b-none p-6 pb-4">
          <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-0 pb-0 border-b border-neutral-200" @click="toggleSection('administrator-settings')" style="cursor: pointer;">
            <div class="section-title flex items-center gap-3">
              <button class="expand-btn" :class="{ expanded: sectionsExpanded['administrator-settings'] }">
                <span class="material-icon-sm">expand_more</span>
              </button>
              <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
                <span class="material-icon text-purple-600">admin_panel_settings</span>
                Administrator Settings
              </h2>
            </div>
            <!-- Admin-specific actions: Upgrade and Subscription Plan -->
            <div class="admin-header-actions flex items-center gap-4">
              <span class="text-sm text-gray-600">Current Plan: Basic</span>
              <button @click.stop="handleUpgradeClick" class="btn-filled flex items-center gap-2" v-if="isAdmin">
                <span class="material-icon-sm">workspace_premium</span>
                Upgrade
              </button>
              <!-- Add other admin actions here if needed -->
            </div>
          </div>
        </div>

        <div v-show="sectionsExpanded['administrator-settings']" class="admin-subsections bg-gray-50 rounded-b-xl border-t border-gray-200">
          <div class="p-6 space-y-6">

            <!-- Business Profile Sub-Section -->
            <div class="subsection-card" v-if="userRole === 2">
              <div class="subsection-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4 pb-2 border-b border-gray-300" @click="toggleSection('profile')" style="cursor: pointer;">
                <div class="subsection-title flex items-center gap-3">
                  <button class="expand-btn small" :class="{ expanded: sectionsExpanded.profile }">
                    <span class="material-icon-sm">expand_more</span>
                  </button>
                  <h4 class="text-title-medium text-on-surface mb-0 flex items-center gap-3">
                    <span class="material-icon text-blue-600">business</span>
                    Business Profile
                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">{{ clientProfileCompleteness }}% Complete</span>
                  </h4>
                </div>
                <button @click.stop="$router.push('/client/edit-profile')" class="btn-outlined btn-small flex items-center gap-2">
                  <span class="material-icon-sm">edit</span>
                  Edit Profile
                </button>
              </div>

              <div v-show="sectionsExpanded.profile" class="subsection-content transition-all duration-300 ease-in-out">
                <BusinessProfileSection
                  :expanded="sectionsExpanded.profile"
                  :client-profile="clientProfile"
                  :client-profile-completeness="clientProfileCompleteness"
                  :is-admin="isAdmin"
                  @toggle="toggleSection('profile')"
                  @edit-profile="showEditProfileModal = true"
                />
              </div>
            </div>

            <!-- User Management Sub-Section -->
            <div class="subsection-card" v-if="userRole === 2">
              <div class="subsection-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4 pb-2 border-b border-gray-300" @click="toggleSection('users')" style="cursor: pointer;">
                <div class="subsection-title flex items-center gap-3">
                  <button class="expand-btn small" :class="{ expanded: sectionsExpanded.users }">
                    <span class="material-icon-sm">expand_more</span>
                  </button>
                  <h4 class="text-title-medium text-on-surface mb-0 flex items-center gap-3">
                    <span class="material-icon text-blue-600">group</span>
                    User Management
                    <span v-if="users?.length" class="text-sm font-normal text-blue-600">({{ users.length }})</span>
                  </h4>
                </div>
              </div>

              <div v-show="sectionsExpanded.users" class="subsection-content transition-all duration-300 ease-in-out">
                <UserManagementSection
                  :expanded="false"
                  :users="users"
                  :available-roles="availableRoles"
                  :is-admin="isAdmin"
                  :current-user-id="userId"
                  @add-user="showAddUserModal = true"
                  @edit-user="handleEditUser"
                  @delete-user="handleDeleteUser"
                />
              </div>
            </div>

            <!-- Locations Sub-Section -->
            <div class="subsection-card" v-if="userRole === 2">
              <div class="subsection-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4 pb-2 border-b border-gray-300" @click="toggleSection('locations')" style="cursor: pointer;">
                <div class="subsection-title flex items-center gap-3">
                  <button class="expand-btn small" :class="{ expanded: sectionsExpanded.locations }">
                    <span class="material-icon-sm">expand_more</span>
                  </button>
                  <h4 class="text-title-medium text-on-surface mb-0 flex items-center gap-3">
                    <span class="material-icon text-blue-600">location_on</span>
                    Locations
                    <span v-if="locations?.length" class="text-sm font-normal text-blue-600">({{ locations.length }})</span>
                  </h4>
                </div>
              </div>

              <div v-show="sectionsExpanded.locations" class="subsection-content transition-all duration-300 ease-in-out">
                <LocationManagementSection
                  :expanded="false"
                  :locations="locations"
                  :is-admin="isAdmin"
                  @edit-location="handleEditLocation"
                  @add-location="showAddLocationModal = true"
                  @filter-jobs="filterJobsByLocation"
                />
              </div>
            </div>

            <!-- Approved Providers Sub-Section -->
            <div class="subsection-card" v-if="userRole === 2">
              <div class="subsection-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4 pb-2 border-b border-gray-300" @click="toggleSection('providers')" style="cursor: pointer;">
                <div class="subsection-title flex items-center gap-3">
                  <button class="expand-btn small" :class="{ expanded: sectionsExpanded.providers }">
                    <span class="material-icon-sm">expand_more</span>
                  </button>
                  <h4 class="text-title-medium text-on-surface mb-0 flex items-center gap-3">
                    <span class="material-icon text-blue-600">business</span>
                    Approved Providers
                    <span v-if="approvedProviders?.length" class="text-sm font-normal text-blue-600">({{ approvedProviders.length }})</span>
                  </h4>
                </div>
              </div>

              <div v-show="sectionsExpanded.providers" class="subsection-content transition-all duration-300 ease-in-out">
                <ProviderManagementSection
                  :expanded="false"
                  :approved-providers="approvedProviders"
                  :is-admin="isAdmin"
                  @view-provider-jobs="handleViewProviderJobs"
                  @browse-providers="$router.push('/browse-providers')" @add-xs-provider="$router.push('/client/add-provider')"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Jobs Section - For all user roles -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('jobs')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.jobs }" @click.stop>
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">work</span>
              Job Management
              <span v-if="jobs && jobs.length" class="text-sm font-normal text-blue-600">({{ jobs.length }})</span>
            </h2>
          </div>
          <div class="section-header-actions flex items-center gap-4" @click.stop>
            <button @click.stop="$router.push('/client/create-job')" class="btn-filled flex items-center gap-2" :disabled="!clientProfile?.is_enabled">
              <span class="material-icon-sm">add</span>
              Service Request
            </button>
          </div>
        </div>

        <div v-show="sectionsExpanded.jobs" class="section-content transition-all duration-300 ease-in-out">
          <JobManagementSection
            :jobs="jobs"
            :job-filters="jobFilters"
            :locations="locations"
            :approved-providers="approvedProviders"
            :user-role="userRole"
            :client-profile="clientProfile"
            :is-admin="isAdmin"
            :expanded="sectionsExpanded.jobs"
            @update-job-filters="updateJobFilters"
            @load-jobs="loadJobs"
            @create-job="showCreateJobModal = true"
            @view-job-details="handleViewJobDetails($event)"
            @job-card-click="handleJobCardClick($event)"
            @edit-job="handleEditJob($event)"
            @toggle-archive-job="toggleArchiveJob($event)"
            @confirm-job="handleConfirmJob($event)"
            @reject-job="handleRejectJob($event)"
            @accept-quote="handleAcceptQuote($event)"
            @reject-quote="handleRejectQuote($event)"
            @view-quotation="handleViewQuotation($event)"
          />
        </div>
      </div>
    </div>

    <!-- Modals -->
    <AddUserModal
      v-if="showAddUserModal"
      :newUser="newUser"
      :available-roles="availableRoles"
      :adding-user="addingUser"
      @close="showAddUserModal = false"
      @submit="handleAddUser"
    />

    <EditUserModal
      v-if="showEditUserModal"
      :editingUser="editingUser"
      :available-roles="availableRoles"
      :updating-user="updatingUser"
      @close="showEditUserModal = false"
      @submit="handleUpdateUser"
    />

    <AddLocationModal
      v-if="showAddLocationModal"
      :newLocation="newLocation"
      :adding-location="addingLocation"
      @close="showAddLocationModal = false"
      @submit="handleAddLocation"
    />

    <CreateJobModal
      v-if="showCreateJobModal"
      :locations="locations"
      :creating-job="creatingJob"
      :new-job="newJob"
      @close="closeCreateJobModal"
      @submit="handleCreateJob"
      @qr-detected="handleQrDetected"
      @images-changed="handleImagesChanged"
    />

    <JobDetailsModal
      :show="showJobDetailsModal"
      :job="selectedJob"
      @close="showJobDetailsModal = false"
      @image-click="selectedImage = $event"
    />

    <EditJobModal
      v-if="showEditJobModal"
      :job="editingJob"
      :userRole="userRole"
      :userId="userId"
      :entityId="entityId"
      :entityType="'client'"
      :availableProviders="approvedProviders"
      @close="showEditJobModal = false"
      @job-updated="handleEditJobUpdated"
    />

    <EditProfileModal
      v-if="showEditProfileModal"
      :editing-profile="editingProfile"
      :updating-profile="updatingProfile"
      @close="showEditProfileModal = false"
      @submit="handleUpdateClientProfile"
    />

  </div>
</template>

<script>
import ImageUpload from '@/components/ImageUpload.vue'
import QrScanner from '@/components/QrScanner.vue'
import { apiFetch, handleTokenExpiration, loadRoleSettings } from '@/utils/api.js'

// New component imports
import UnifiedDashboardHeader from '@/components/dashboard/UnifiedDashboardHeader.vue'
import BusinessProfileSection from '@/components/dashboard/BusinessProfileSection.vue'
import UserManagementSection from '@/components/dashboard/UserManagementSection.vue'
import ProviderManagementSection from '@/components/dashboard/ProviderManagementSection.vue'
import LocationManagementSection from '@/components/dashboard/LocationManagementSection.vue'
import JobManagementSection from '@/components/dashboard/JobManagementSection.vue'
import AddUserModal from '@/components/modals/AddUserModal.vue'
import EditUserModal from '@/components/modals/EditUserModal.vue'
import AddLocationModal from '@/components/modals/AddLocationModal.vue'
import EditLocationModal from '@/components/modals/EditLocationModal.vue'
import CreateJobModal from '@/components/modals/CreateJobModal.vue'
import EditJobModal from '@/components/modals/EditJobModal.vue'
import JobDetailsModal from '@/components/modals/JobDetailsModal.vue'
import ProviderDetailsModal from '@/components/modals/ProviderDetailsModal.vue'
import QuotationDetailsModal from '@/components/modals/QuotationDetailsModal.vue'
import EditProfileModal from '@/components/modals/EditProfileModal.vue'

export default {
  name: 'ClientDashboard',
  components: {
    ImageUpload,
    QrScanner,
    UnifiedDashboardHeader,
    BusinessProfileSection,
    UserManagementSection,
    ProviderManagementSection,
    LocationManagementSection,
    JobManagementSection,
    AddUserModal,
    EditUserModal,
    AddLocationModal,
    CreateJobModal,
    JobDetailsModal,
    EditJobModal,
    EditLocationModal,
    QuotationDetailsModal,
    EditProfileModal
  },
  data() {
    return {
      roleDisplayNames: {}, // Store role names loaded from site settings
      users: null, // Start as null to show loading state
      approvedProviders: null, // Start as null to show loading state
      jobs: null, // Start as null to show loading state
      locations: [], // Client locations for job creation
      availableRoles: [],
      userRole: null, // Store user's role
      userId: null, // Store user's ID
      isAdmin: false, // Whether user can manage users/providers
      loading: true,
      profileCompleteness: 0,
      clientProfile: null, // Client profile data
      clientProfileCompleteness: 0, // Client profile completeness percentage
      wasEnabled: true, // Track if account was previously enabled
      showAddUserModal: false,
      showEditUserModal: false,
      showCreateJobModal: false,
      showAssignModal: false,
      showAddLocationModal: false,
      showEditLocationModal: false,
      showEditProfileModal: false, // Client profile editing modal
      editingLocation: {
        id: null, name: '', address: '', coordinates: '', access_rules: '', access_instructions: ''
      },
      editingProfile: { // Client profile editing data
        name: '',
        address: '',
        website: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        vat_number: '',
        business_registration_number: '',
        is_active: true
      },
      showJobDetailsModal: false,
      showQuotationDetailsModal: false,
      showJobConfirmationModal: false,
      showJobRejectionModal: false,
      showQuoteResponseModal: false,
      showQuoteRejectionModal: false,
      confirmationJob: null,
      rejectionJob: null,
      quoteResponseJob: null,
      quoteRejectionJob: null,
      confirmationNotes: '',
      rejectionNotes: '',
      quoteResponseNotes: '',
      quoteRejectionNotes: '',
      addingUser: false,
      updatingUser: false,
      deletingUser: false,
      creatingJob: false,
      updatingProfile: false, // Client profile update loading state
      acceptingQuote: false,
      rejectingQuote: false,
      jobFilters: {
        status: '',
        location_id: '',
        provider_id: '',
        archive_status: 'active'
      },
      newJob: {
        client_location_id: '',
        item_identifier: '',
        fault_description: '',
        contact_person: ''
      },
      jobToAssign: null,
      selectedImages: [], // Array to store selected images from component
      newUser: {
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        role_id: ''
      },
      editingUser: {
        id: null,
        username: '',
        email: '',
        phone: '',
        role_id: null,
        newPassword: ''
      },
      newLocation: {
        name: '',
        address: '',
        coordinates: '',
        access_rules: '',
        access_instructions: ''
      },
      addingLocation: false,
      selectedJob: null,
      selectedImage: null,
      locationsViewMode: 'cards', // 'cards' or 'table'
      originalJobStatus: null,
      originalProviderId: null,
      editingImages: [], // Array to store additional images for editing
      selectedQuotation: null,
      // Section collapse/expand state
      sectionsExpanded: {
        'administrator-settings': false, // Administrator settings collapsed by default
        profile: false, // Profile section collapsed by default
        users: false,
        locations: false,
        providers: false,
        jobs: true // Jobs section expanded by default
      }
    }
  },
  async mounted() {
    // Handle expired token on component mount
    if (handleTokenExpiration()) {
      return // Stop execution if token was expired and user was redirected
    }

    // CRITICAL FIX: Explicitly reset all modals on component mount to prevent modal state bleeding
    // Force reset all modals to false on every component mount
    this.showJobDetailsModal = false; this.showAddUserModal = false
    this.showEditUserModal = false
    this.showCreateJobModal = false
    this.showAddLocationModal = false
    this.showEditLocationModal = false
    this.showEditProfileModal = false
    this.showJobConfirmationModal = false
    this.showJobRejectionModal = false
    this.showQuoteResponseModal = false
    this.showQuoteRejectionModal = false

    this.checkUserPermissions()

    // Load role settings first so they're available for role display
    try {
      const settings = await loadRoleSettings()
      this.roleDisplayNames = settings || {}
    } catch (error) {
      console.warn('Failed to load role settings, using defaults:', error)
      this.roleDisplayNames = {}
    }

    this.loadClientProfile()
    this.loadUsers()
    this.loadApprovedProviders()
    this.loadAvailableRoles()
    this.loadLocations()
    this.loadJobs()
  },
  computed: {
    entityId() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.entity_id || payload.client_id
        }
      } catch (error) {
        console.error('Failed to parse JWT token:', error)
      }
      return null
    },

    getProfileDisabled() {
      return this.editingProfile && !this.editingProfile.is_enabled
    }
  },
  watch: {
    users() {
      this.calculateProfileCompleteness()
    },
    approvedProviders() {
      this.calculateProfileCompleteness()
    },
    locations() {
      this.calculateProfileCompleteness()
    },
    clientProfile() {
      this.calculateClientProfileCompleteness()
    },
    showEditProfileModal(newValue) {
      // Populate form fields with current profile data when modal opens
      if (newValue) {
        this.resetEditProfileForm()
      }
    }
  },
 methods: {
    checkUserPermissions() {
      try {
        const token = localStorage.getItem('token')

        if (token) {
          try {
            const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
            this.userRole = payload.role_id
            this.userId = payload.user_id
            // Site Budget Controller (role_id = 2) can manage users/providers
            // Reporting Employee (role_id = 1) cannot
            this.isAdmin = payload.role_id === 2
          } catch (decodeError) {
            console.error('Failed to decode JWT payload:', decodeError)
            this.isAdmin = false
            this.userId = null
          }
        } else {
          this.isAdmin = false
          this.userId = null
        }
      } catch (error) {
        console.error('Failed to decode token:', error)
        this.isAdmin = false
        this.userId = null
      }
    },

    async loadUsers() {
      try {
        const response = await apiFetch('/backend/api/client-users.php')

        if (response.ok) {
          const data = await response.json()
          this.users = data.users
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load users')
      }
    },

    async loadApprovedProviders() {
      try {
        const response = await apiFetch('/backend/api/client-approved-providers.php')

        if (response.ok) {
          const data = await response.json()
          this.approvedProviders = data.approved_providers
        } else {
          console.error('Failed to load approved providers')
        }
      } catch (error) {
        console.error('Failed to load approved providers')
      }
    },

    async loadAvailableRoles() {
      // Load available roles for client users from site settings or database
      const settings = await loadRoleSettings()
      this.availableRoles = Object.entries(settings).map(([id, name]) => ({
        id: parseInt(id),
        name: name
      })).filter(role => role.id === 1 || role.id === 2) // Only show client roles

      // Ensure we always have the basic client roles even if settings are empty
      if (this.availableRoles.length === 0) {
        this.availableRoles = [
          { id: 1, name: 'Reporting Employee' },
          { id: 2, name: 'Site Budget Controller' }
        ];
      }
    },

    async addUser() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.addingUser = true
      try {
        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'POST',
          body: JSON.stringify({
            email: this.newUser.email,
            first_name: this.newUser.first_name,
            last_name: this.newUser.last_name,
            phone: this.newUser.phone,
            role_id: this.newUser.role_id
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showAddUserModal = false
          this.resetNewUserForm()
          this.loadUsers()
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to add user')
      } finally {
        this.addingUser = false
      }
    },

    editUser(user) {
      this.editingUser = {
        id: user.id,
        username: user.username,
        email: user.email,
        first_name: user.first_name,
        last_name: user.last_name,
        phone: user.phone || '',
        role_id: user.role_id,
        newPassword: ''
      }
      this.showEditUserModal = true
    },

    async updateUser() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.updatingUser = true
      try {
        const updateData = {
          user_id: this.editingUser.id,
          email: this.editingUser.email,
          first_name: this.editingUser.first_name,
          last_name: this.editingUser.last_name,
          mobile: this.editingUser.phone || null,
          role_id: this.editingUser.role_id
        }

        if (this.editingUser.newPassword) {
          updateData.password = this.editingUser.newPassword
        }

        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'PUT',
          body: JSON.stringify(updateData)
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showEditUserModal = false
          this.loadUsers()
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update user')
      } finally {
        this.updatingUser = false
      }
    },

    async deleteUser(user) {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      if (!confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
        return
      }

      try {
        const response = await apiFetch(`/backend/api/client-users.php?user_id=${user.id}`, {
          method: 'DELETE'
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.loadUsers()
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to delete user')
      }
    },

    canDeleteUser(user) {
      // Prevent deletion of the current user and ensure at least one admin remains
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}')
      return user.id !== currentUser.id
    },

    // New component event handlers
    handleEditUser(user) {
      this.editingUser = {
        id: user.id,
        username: user.username,
        email: user.email,
        first_name: user.first_name,
        last_name: user.last_name,
        phone: user.phone || '',
        role_id: user.role_id,
        newPassword: ''
      }
      this.showEditUserModal = true
    },

    async handleDeleteUser(user) {
      this.deletingUser = true
      try {
        if (!confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
          return
        }

        const response = await apiFetch(`/backend/api/client-users.php?user_id=${user.id}`, {
          method: 'DELETE'
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.loadUsers()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to delete user')
      } finally {
        this.deletingUser = false
      }
    },

    async handleAddUser(userData) {
      this.addingUser = true
      try {
        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'POST',
          body: JSON.stringify(userData)
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showAddUserModal = false
          this.loadUsers()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to add user')
      } finally {
        this.addingUser = false
      }
    },

    async handleAddLocation(locationData) {
      this.newLocation = { ...locationData }
      await this.addLocation()
    },

    async handleUpdateUser(userData) {
      this.updatingUser = true
      try {
        // Merge form data with the existing user ID
        const updateData = {
          user_id: userData.id,
          email: userData.email,
          first_name: userData.first_name,
          last_name: userData.last_name,
          mobile: userData.phone || null,
          role_id: userData.role_id
        }

        // Include password if it was provided for password change
        if (userData.newPassword) {
          updateData.password = userData.newPassword
        }

        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'PUT',
          body: JSON.stringify(updateData)
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showEditUserModal = false
          this.loadUsers()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to update user')
      } finally {
        this.updatingUser = false
      }
    },

    async handleUpdateLocation(locationData) {
      try {
        const response = await apiFetch('/backend/api/client-locations.php', {
          method: 'PUT',
          body: JSON.stringify({
            location_id: locationData.id,
            name: locationData.name.trim(),
            address: locationData.address ? locationData.address.trim() : '',
            coordinates: locationData.coordinates ? locationData.coordinates.trim() : '',
            access_rules: locationData.access_rules ? locationData.access_rules.trim() : '',
            access_instructions: locationData.access_instructions ? locationData.access_instructions.trim() : ''
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showEditLocationModal = false
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to update location')
      }
    },

    handleEditLocation(location) {
      this.editingLocation = {
        id: location.id,
        name: location.name,
        address: location.address || '',
        coordinates: location.coordinates || '',
        access_rules: location.access_rules || '',
        access_instructions: location.access_instructions || ''
      }
      this.showEditLocationModal = true
    },

    async handleEditJobUpdated(updatedJobData) {
      console.log('ClientDashboard: handleEditJobUpdated called with:', updatedJobData)

      // Refresh jobs list after edit modal updates to ensure we have the latest data
      await this.loadJobs()

      // Optional: Emit a success message if we have one
      if (updatedJobData?.message) {
        // Show success message after a brief delay to allow data to settle
        setTimeout(() => {
          alert(updatedJobData.message)
        }, 100)
      }
    },

    handleEditJob(job) {
      // Navigate to the dedicated edit job page with scroll position context
      const scrollPosition = window.pageYOffset || 0

      this.$router.push({
        path: `/jobs/${job.id}/edit`,
        query: {
          from: 'client',
          scroll: scrollPosition.toString()
        }
      })
    },

    handleViewProviderJobs(provider) {
      // Navigate to the dynamic provider details page
      const providerId = provider.service_provider_id || provider.id;
      this.$router.push(`/client/provider/${providerId}`);
    },

    async handleViewJobDetails(job) {
      this.selectedJob = job
      this.showJobDetailsModal = true

      // Load job images if not already loaded
      if (!job.images) {
        try {
          const response = await apiFetch(`/backend/api/job-images.php?job_id=${job.id}`)

          if (response.ok) {
            const data = await response.json()
            this.selectedJob.images = data.images || []
          }
        } catch (error) {
          console.error('Failed to load job images:', error)
          this.selectedJob.images = []
        }
      }
    },

    handleJobCardClick(job) {
      // If user can edit, card click navigates to edit page.
      // Otherwise, it opens the view details modal.
      if (this.canEditJob(job)) {
        this.handleEditJob(job);
      } else {
        this.handleViewJobDetails(job);
      }
    },

    updateJobFilters(filters) {
      this.jobFilters = { ...this.jobFilters, ...filters }
    },

    closeCreateJobModal() {
      this.showCreateJobModal = false
      this.resetNewJobForm()
    },

    async handleCreateJob(jobData) {
      await this.createJob()
    },

    viewProviderJobs(provider) {
      // Filter jobs to show only those for the selected provider
      this.jobFilters.provider_id = provider.service_provider_id
      this.loadJobs()

      // Scroll to jobs section for better UX
      const jobsSection = this.$el.querySelector('.jobs-section')
      if (jobsSection) {
        jobsSection.scrollIntoView({ behavior: 'smooth' })
      }
    },

    resetNewUserForm() {
      this.newUser = {
        username: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        role_id: ''
      }
    },

    getRoleClass(roleName) {
      const roleClasses = {
        'Reporting Employee': 'admin',
        'Site Budget Controller': 'controller'
      }
      return roleClasses[roleName] || 'user'
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
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
    },

    // Job-related methods
    async loadLocations() {
      try {
        const response = await apiFetch('/backend/api/client-locations.php')

        if (response.ok) {
          const data = await response.json()
          this.locations = data.locations
        } else {
          console.error('Failed to load locations')
        }
      } catch (error) {
        console.error('Failed to load locations:', error)
      }
    },

    async loadJobs() {
      try {
        const params = new URLSearchParams()

        if (this.jobFilters.status) params.append('status', this.jobFilters.status)
        if (this.jobFilters.location_id) params.append('location_id', this.jobFilters.location_id)
        if (this.jobFilters.provider_id) params.append('provider_id', this.jobFilters.provider_id)

        // For reporting employees, only show their own jobs
        if (this.userRole === 1) {
          params.append('user_id', this.userId)
        }

        const response = await apiFetch(`/backend/api/client-jobs.php?${params}`)

        if (response.ok) {
          let data = await response.json()
          let jobs = data.jobs

          // Filter by archive status on the frontend (since backend doesn't support it yet)
          if (this.jobFilters.archive_status === 'active') {
            jobs = jobs.filter(job => !job.archived_by_client)
          } else if (this.jobFilters.archive_status === 'archived') {
            jobs = jobs.filter(job => job.archived_by_client)
          }

          this.jobs = jobs
        } else {
          console.error('Failed to load jobs')
          this.jobs = []
        }
      } catch (error) {
        console.error('Failed to load jobs:', error)
        this.jobs = []
      }
    },

    getStatusClass(status) {
      const statusClasses = {
        'Reported': 'reported',
        'Assigned': 'assigned',
        'In Progress': 'in-progress',
        'Completed': 'completed',
        'Confirmed': 'confirmed',
        'Incomplete': 'incomplete',
        'Cannot repair': 'cannot-repair'
      }
      return statusClasses[status] || 'reported'
    },

    canAssignJob(job) {
      return job.job_status === 'Reported'
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

      // Budget controllers (Role 2) can edit these specific states:
      // Content editing + state transitions happen in EditJob.vue
      const role2EditableStates = [
        'Reported',         // Early job editing + provider selection
        'Unable to Quote',  // Can reassign provider
        'Completed',        // Can confirm/reject completion
        'Cannot repair',    // Can edit + reassign provider
        'Declined',         // Can reassign + cancel job
        'Quote Provided',   // Can respond to quotes
        'Quote Rejected',   // Can restart quote process + cancel
        'Quote Expired'     // Can extend deadline + cancel
      ];

      // XS provider jobs: Role 2 can edit ANY state (for tracking)
      const isXSProviderJob = job && job.assigned_provider_type === 'XS';

      if (this.userRole === 2 && (role2EditableStates.includes(job.job_status) || isXSProviderJob)) {
        return true
      }

      return false
    },

    canEditJobDetails(job) {
      // Only allow editing full details when status is 'Reported'
      return job.job_status === 'Reported'
    },

    async viewJobDetails(job) {
      this.selectedJob = job
      this.showJobDetailsModal = true

      // Load job images if not already loaded
      if (!job.images) {
        try {
          const response = await apiFetch(`/backend/api/job-images.php?job_id=${job.id}`)

          if (response.ok) {
            const data = await response.json()
            this.selectedJob.images = data.images || []
          }
        } catch (error) {
          console.error('Failed to load job images:', error)
          this.selectedJob.images = []
        }
      }
    },

    showAssignModal(job) {
      this.jobToAssign = job
      this.showAssignModal = true
      // This would open a modal to assign the job to a provider
      // For now, just show a simple prompt
      const providerOptions = this.approvedProviders.map(p => `${p.id}: ${p.name}`).join('\n')
      const providerId = prompt(`Select a provider to assign this job to:\n\n${providerOptions}`)

      if (providerId) {
        this.assignJob(job.id, parseInt(providerId))
      }
    },

    async assignJob(jobId, providerId) {
      // Check if client account is administratively disabled
      if (!this.clientProfile?.is_enabled) {
        alert('Cannot assign jobs when account is administratively disabled. Please contact support.')
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: jobId,
            assigned_provider_id: providerId,
            job_status: 'Assigned'
          })
        })

        if (response.ok) {
          alert('Job assigned successfully!')
          this.loadJobs() // Refresh the jobs list
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to assign job')
      }
    },

    // Job creation methods
    async createJob() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.creatingJob = true
      try {
        // First create the job
        const jobData = {
          client_location_id: this.newJob.client_location_id,
          item_identifier: this.newJob.item_identifier || null,
          fault_description: this.newJob.fault_description,
          contact_person: this.newJob.contact_person || null
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'POST',
          body: JSON.stringify(jobData)
        })

        if (response.ok) {
          const data = await response.json()
          const jobId = data.job_id

          // Upload images if any were selected - handle directly in ClientDashboard
          if (this.selectedImages.length > 0) {
            console.log('ClientDashboard: Starting image upload for job', jobId, 'with', this.selectedImages.length, 'images')
            try {
              let successCount = 0
              const token = localStorage.getItem('token')

              for (let i = 0; i < this.selectedImages.length; i++) {
                const image = this.selectedImages[i]
                const formData = new FormData()
                formData.append('image', image.file)
                formData.append('job_id', jobId.toString())

                console.log('ClientDashboard: Uploading image', i + 1, 'of', this.selectedImages.length, 'for job', jobId)

                const response = await fetch(`/backend/api/upload-job-image.php?token=${encodeURIComponent(token)}`, {
                  method: 'POST',
                  body: formData
                })

                if (response.ok) {
                  successCount++
                  console.log('ClientDashboard: Image upload successful')
                } else {
                  const errorData = await response.json()
                  console.error('ClientDashboard: Image upload failed:', errorData)
                }
              }

              if (successCount === this.selectedImages.length) {
                console.log('ClientDashboard: All image uploads successful')
              } else {
                console.log('ClientDashboard: Some uploads failed:', successCount, 'of', this.selectedImages.length, 'successful')
              }
            } catch (imageError) {
              console.error('ClientDashboard: Image upload failed:', imageError)
              // Show a warning but don't fail the job creation
              setTimeout(() => {
                alert('Job created successfully, but image upload failed. You can try uploading images again by editing the job.')
              }, 100)
            }
          } else {
            console.log('ClientDashboard: No images selected, skipping upload')
          }

          alert('Service request submitted successfully!')
          this.showCreateJobModal = false
          this.resetNewJobForm()
          this.loadJobs() // Refresh the jobs list
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to report fault')
      } finally {
        this.creatingJob = false
      }
    },

    handleImagesChanged(images) {
      this.selectedImages = images
    },

    handleEditImagesChanged(images) {
      this.editingImages = images
    },

    resetNewJobForm() {
      this.newJob = {
        client_location_id: '',
        item_identifier: '',
        fault_description: '',
        contact_person: ''
      }
      // Clear selected images in component
      if (this.$refs.imageUpload) {
        // The component manages its own images, so we just need to reset our local array
        this.selectedImages = []
      }
    },

    // Location management methods
    async addLocation() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.addingLocation = true
      try {
        const response = await apiFetch('/backend/api/client-locations.php', {
          method: 'POST',
          body: JSON.stringify({
            name: this.newLocation.name.trim(),
            address: this.newLocation.address ? this.newLocation.address.trim() : '',
            coordinates: this.newLocation.coordinates ? this.newLocation.coordinates.trim() : '',
            access_rules: this.newLocation.access_rules ? this.newLocation.access_rules.trim() : '',
            access_instructions: this.newLocation.access_instructions ? this.newLocation.access_instructions.trim() : ''
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showAddLocationModal = false
          this.resetNewLocationForm()
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to add location')
      } finally {
        this.addingLocation = false
      }
    },

    resetNewLocationForm() {
      this.newLocation = {
        name: '',
        address: '',
        coordinates: '',
        access_rules: '',
        access_instructions: ''
      }
    },

    // Removed duplicate editLocation method - using handleEditLocation instead

    async updateLocation() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-locations.php', {
          method: 'PUT',
          body: JSON.stringify({
            location_id: this.editingLocation.id,
            name: this.editingLocation.name.trim(),
            address: this.editingLocation.address ? this.editingLocation.address.trim() : '',
            coordinates: this.editingLocation.coordinates ? this.editingLocation.coordinates.trim() : '',
            access_rules: this.editingLocation.access_rules ? this.editingLocation.access_rules.trim() : '',
            access_instructions: this.editingLocation.access_instructions ? this.editingLocation.access_instructions.trim() : ''
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showEditLocationModal = false
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to update location')
      }
    },

    async deleteLocation(location) {
      if (!confirm(`Are you sure you want to delete "${location.name}"? This can only be done if there are no associated fault reports.`)) {
        return
      }

      try {
        const response = await apiFetch(`/backend/api/client-locations.php?location_id=${location.id}`, {
          method: 'DELETE'
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to delete location')
      }
    },

    filterJobsByLocation(location) {
      // Set the location filter to this location
      this.jobFilters.location_id = location.id
      this.loadJobs()
    },

    // Job confirmation and rejection handlers
    async handleConfirmJob(job) {
      if (confirm(`Confirm completion of job "${job.item_identifier}"?\n\nThis will mark the job as confirmed.`)) {
        await this.confirmJobCompletion(job);
      }
    },

    handleRejectJob(job) {
      // Open edit modal to handle rejection with notes and status selection
      this.editingJob = { ...job };
      // Set to null to force reload of technicians if needed
      this.editingJob.assigned_technician_user_id = null;
      this.showEditJobModal = true;
    },

    async handleAcceptQuote(job) {
      // Enhanced confirmation dialog with more details
      const confirmMessage = `Accept this quotation for "${job.item_identifier}"?

Quote Details:
• Job status will change to "Assigned"
• Quote will be marked as accepted
• Service provider can begin work immediately

Are you sure you want to proceed?`;

      if (!confirm(confirmMessage)) {
        return;
      }

      try {
        // Validate that we have a current quotation
        if (!job.current_quotation_id) {
          alert('No quote found for this job. Please refresh the page and try again.');
          return;
        }

        const response = await apiFetch('/backend/api/job-quotations.php', {
          method: 'PUT',
          body: JSON.stringify({
            quote_id: job.current_quotation_id,
            action: 'accept',
            notes: 'Quote accepted by client'
          })
        });

        if (response.ok) {
          const data = await response.json();
          alert(data.message || 'Quote accepted and job assigned to provider!');

          // Refresh jobs list to show updated status
          this.loadJobs();
        } else {
          const errorData = await response.json();
          this.handleError(errorData);
        }
      } catch (error) {
        alert('Failed to accept quote');
      }
    },

    handleRejectQuote(job) {
      // Set job being rejected and open EditJobModal for state transitions
      this.editingJob = { ...job };
      this.showEditJobModal = true;
      this.rejectQuoteMode = true;
    },

    async confirmJobCompletion(job) {
      try {
        const response = await apiFetch('/backend/api/job-completion-confirmation.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: job.id,
            action: 'confirm',
            notes: ''
          })
        })

        if (response.ok) {
          const data = await response.json()
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === job.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = 'Confirmed'
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }
          alert('Job confirmed successfully!')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to confirm job')
      }
    },

    openImageModal(image) {
      this.selectedImage = image
    },

    async editJob(job) {
      // Set the job being edited and store original values for comparison
      this.editingJob = { ...job }
      this.originalJobStatus = job.job_status
      this.originalProviderId = job.assigned_provider_id
      this.originalItemIdentifier = job.item_identifier
      this.originalFaultDescription = job.fault_description
      this.originalContactPerson = job.contact_person

      // Load job images if not already loaded
      if (!job.images) {
        try {
          const response = await apiFetch(`/backend/api/job-images.php?job_id=${job.id}`)

          if (response.ok) {
            const data = await response.json()
            this.editingJob.images = data.images || []
          } else {
            console.error('Failed to load job images')
            this.editingJob.images = []
          }
        } catch (error) {
          console.error('Failed to load job images:', error)
          this.editingJob.images = []
        }
      } else {
        this.editingJob.images = job.images
      }

      // Refresh approved providers list to ensure it's current
      this.loadApprovedProviders().then(() => {
        // Ensure the current assigned provider is still approved
        const isProviderStillApproved = this.approvedProviders.some(provider => provider.id == this.editingJob.assigned_provider_id)
        if (this.editingJob.assigned_provider_id && !isProviderStillApproved) {
          // Provider is no longer approved, clear the assignment
          this.editingJob.assigned_provider_id = null
        }
        this.showEditJobModal = true
      })
    },

    calculateProfileCompleteness() {
      let completeness = 0
      const totalCriteria = 4

      // Check if users exist
      if (this.users && this.users.length > 0) {
        completeness += 1
      }

      // Check if locations exist
      if (this.locations && this.locations.length > 0) {
        completeness += 1
      }

      // Check if approved providers exist
      if (this.approvedProviders && this.approvedProviders.length > 0) {
        completeness += 1
      }

      // Check if client profile is complete (25% weight)
      if (this.clientProfileCompleteness >= 70) {
        completeness += 1
      }

      this.profileCompleteness = Math.round((completeness / totalCriteria) * 100)
    },

    calculateClientProfileCompleteness() {
      if (!this.clientProfile) {
        this.clientProfileCompleteness = 0
        return
      }

      let score = 0
      const total = 100

      // Basic information (40 points)
      const basicFields = ['name', 'address', 'description']
      basicFields.forEach(field => {
        if (this.clientProfile[field]) score += 13
      })

      // Website (10 points)
      if (this.clientProfile.website) score += 10

      // Manager contact (30 points)
      const managerFields = ['manager_name', 'manager_email', 'manager_phone']
      managerFields.forEach(field => {
        if (this.clientProfile[field]) score += 10
      })

      // Business details (20 points)
      const businessFields = ['vat_number', 'business_registration_number']
      businessFields.forEach(field => {
        if (this.clientProfile[field]) score += 10
      })

      this.clientProfileCompleteness = Math.min(score, total)
    },

    async loadClientProfile() {
      try {
        const response = await apiFetch('/backend/api/client-profile.php')

        if (response.ok) {
          const data = await response.json()
          console.log('ClientDashboard: Loading client profile:', data)
          this.clientProfile = data.profile
          this.clientProfileCompleteness = data.profile_completeness
          console.log('ClientDashboard: Profile loaded. is_enabled:', this.clientProfile?.is_enabled, 'type:', typeof this.clientProfile?.is_enabled)
        } else {
          console.error('Failed to load client profile')
          this.clientProfile = null
        }
      } catch (error) {
        console.error('Failed to load client profile:', error)
        this.clientProfile = null
      }
    },

    async updateClientProfile() {
      this.updatingProfile = true
      try {
        const response = await apiFetch('/backend/api/client-profile.php', {
          method: 'PUT',
          body: JSON.stringify(this.editingProfile)
        })

        if (response.ok) {
          const data = await response.json()
          this.clientProfile = data.profile
          this.clientProfileCompleteness = data.profile_completeness
          this.showEditProfileModal = false
          this.resetEditProfileForm()
          alert('Business profile updated successfully!')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to update business profile')
      } finally {
        this.updatingProfile = false
      }
    },

    async handleUpdateClientProfile(profileData) {
      // Set the editing profile data
      this.editingProfile = { ...profileData }

      // Call the existing update method
      await this.updateClientProfile()
    },

    resetEditProfileForm() {
      if (this.clientProfile) {
        this.editingProfile = {
          name: this.clientProfile.name || '',
          address: this.clientProfile.address || '',
          website: this.clientProfile.website || '',
          description: this.clientProfile.description || '',
          manager_name: this.clientProfile.manager_name || '',
          manager_email: this.clientProfile.manager_email || '',
          manager_phone: this.clientProfile.manager_phone || '',
          vat_number: this.clientProfile.vat_number || '',
          business_registration_number: this.clientProfile.business_registration_number || '',
          is_active: this.clientProfile.is_active !== false,
          is_enabled: this.clientProfile.is_enabled
        }
      } else {
        this.editingProfile = {
          name: '',
          address: '',
          website: '',
          description: '',
          manager_name: '',
          manager_email: '',
          manager_phone: '',
          vat_number: '',
          business_registration_number: '',
          is_active: true,
          is_enabled: true
        }
      }
    },

    getCurrentUserName() {
      // Get current user name from localStorage or users array
      try {
        const userData = localStorage.getItem('user')
        if (userData) {
          const user = JSON.parse(userData)
          // Return first_name + last_name if available, fallback to username
          if (user.first_name && user.last_name) {
            return `${user.first_name} ${user.last_name}`.trim()
          }
          return user.username || 'User'
        }

        // Fallback: find current user in users array
        if (this.users && this.users.length > 0 && this.userId) {
          const currentUser = this.users.find(u => u.id == this.userId)
          if (currentUser && currentUser.first_name && currentUser.last_name) {
            return `${currentUser.first_name} ${currentUser.last_name}`.trim()
          }
          return currentUser ? currentUser.username : 'User'
        }

        return 'User'
      } catch (error) {
        return 'User'
      }
    },

    getOrganizationName() {
      return this.clientProfile?.name || 'Organization'
    },

    async getRoleDisplayName(role) {
      // Load dynamic role names from site settings
      const settings = await loadRoleSettings()
      return settings[role] || 'User'
    },

    getFallbackRoleName(roleId) {
      switch (roleId) {
        case 1:
          return 'Client User'
        case 2:
          return 'Client Admin'
        default:
          return `Role ${roleId}`
      }
    },

    getRoleBadgeClass(role) {
      return role === 2 ? 'role-admin' : 'role-user'
    },

    handleUpgradeClick() {
      // For now, show a simple alert. In a real implementation, this would navigate to a premium upgrade page or modal
      alert('Upgrade to Premium feature coming soon! Contact sales for more information.')
    },

    handleNavigate(route) {
      this.$router.push(route)
    },

    // Handle XS provider added event
    handleXSProviderAdded(provider) {
      console.log('XS Provider added, refreshing provider list:', provider)
      // Refresh the approved providers list to include the new XS provider
      this.loadApprovedProviders()
    },

    // QR Scanner methods
    handleQrDetected(qrData) {
      console.log('QR data detected:', qrData)

      try {
        // Initialize extracted data tracking
        let extractedFields = {}
        let extractionMethod = 'structured' // 'structured' or 'fallback'

        // First, try to validate client ID if present
        if (qrData.clientId && qrData.clientId !== this.getClientId()) {
          alert(`QR code is for a different client (ID: ${qrData.clientId}). This QR code is not valid for your account.`)
          return
        }

        // Check if structured data was successfully parsed
        if (qrData.itemIdentifier && qrData.itemIdentifier.trim()) {
          // We have structured item data - use it
          this.newJob.item_identifier = qrData.itemIdentifier.trim()
          extractedFields.item = qrData.itemIdentifier.trim()
        } else {
          // No structured item data - check if we have any location data
          if (qrData.locationName && qrData.locationName.trim()) {
            // We have location data but no item identifier - use location as item identifier
            this.newJob.item_identifier = qrData.locationName.trim()
            extractedFields.item = qrData.locationName.trim()
            extractedFields.note = 'location_used_as_item'
          } else {
            // No structured data at all - check if qrData is available as string
            // This handles cases where parsing completely failed
            const rawQrString = arguments[0] // Get the original QR data string
            if (rawQrString && typeof rawQrString === 'string' && rawQrString.trim()) {
              this.newJob.item_identifier = rawQrString.trim()
              extractedFields.item = rawQrString.trim()
              extractedFields.note = 'raw_qr_used_as_item'
              extractionMethod = 'fallback'
            } else {
              // Complete failure - shouldn't happen but handle gracefully
              alert('Could not extract any usable data from QR code. Please enter the item identifier manually.')
              return
            }
          }
        }

        // Handle location matching (if location data is available)
        if (qrData.locationName && qrData.locationName.trim() && this.locations && this.locations.length > 0) {
          const matchingLocation = this.locations.find(location =>
            location.name.toLowerCase() === qrData.locationName.toLowerCase()
          )
          if (matchingLocation) {
            this.newJob.client_location_id = matchingLocation.id
            extractedFields.location = qrData.locationName.trim()
          } else {
            extractedFields.location_attempted = qrData.locationName.trim()
            extractedFields.location_note = 'location_not_found'
          }
        }

        // Generate user-friendly success message
        let message = 'QR code scanned successfully!'

        if (extractionMethod === 'structured') {
          message += ` Item: "${extractedFields.item}"`
          if (extractedFields.location) {
            message += `, Location: "${extractedFields.location}"`
          } else if (extractedFields.location_attempted) {
            message += `. Location "${extractedFields.location_attempted}" not found in your locations`
          }
        } else {
          if (extractedFields.note === 'location_used_as_item') {
            message += ` Used location "${extractedFields.item}" as item identifier`
          } else if (extractedFields.note === 'raw_qr_used_as_item') {
            message += ` Used QR code content as item identifier: "${extractedFields.item}"`
          }
        }

        alert(message)

      } catch (error) {
        console.error('Error processing QR data:', error)
        // Fallback: try to use raw QR data
        const rawData = arguments[0]
        if (rawData && typeof rawData === 'string' && rawData.trim()) {
          this.newJob.item_identifier = rawData.trim()
          alert(`QR code scanned! Used raw content as item identifier: "${rawData.trim()}"`)
        } else {
          alert('Error reading QR code. Please try again or enter the item identifier manually.')
        }
      }
    },

    getClientId() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.entity_id
        }
      } catch (error) {
        console.error('Failed to get client ID from token:', error)
      }
      return null
    },

    // Section collapse/expand functionality - Vue 3 compatible
    toggleSection(sectionName) {
      // Vue 3 reactivity: replace entire object for nested property updates
      const currentValue = this.sectionsExpanded[sectionName]
      this.sectionsExpanded = { ...this.sectionsExpanded, [sectionName]: !currentValue }
    },

    // Archive/unarchive job functionality
    async toggleArchiveJob(job) {
      const action = job.archived_by_client ? 'unarchive' : 'archive'
      const confirmMessage = `Are you sure you want to ${action} this job?`

      if (!confirm(confirmMessage)) {
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: job.id,
            archived_by_client: !job.archived_by_client
          })
        })

        if (response.ok) {
          // Refresh jobs list from server to ensure proper filtering
          await this.loadJobs()

          alert(`Job ${action}d successfully!`)
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert(`Failed to ${action} job`)
      }
    },

    // Generate image URL for displaying images
    generateImageUrl(image) {
      const token = localStorage.getItem('token')
      if (!token) {
        console.warn('No JWT token found for image access')
        return ''
      }
      return `/backend/api/serve-image.php?filename=${image.filename}&token=${encodeURIComponent(token)}`
    },

    // Open image in modal for full-size view
    openImageModal(image) {
      this.selectedImage = image
    }
  }
}
</script>

<style scoped>
/* Admin sections nested visual hierarchy */
.admin-settings-container {
  margin-bottom: 2rem;
}

.admin-subsections {
  background-color: #f9fafb;
  padding: 1.5rem;
  border-radius: 0 0 0.5rem 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.admin-subsections .subsection-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.subsection-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.subsection-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.subsection-title h4 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #374151;
}

.subsection-content {
  padding: 1.5rem;
}

/* Existing styles remain unchanged */
.client-dashboard {
  max-width: 1400px;
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

.dashboard-content {
  display: grid;
  gap: 40px;
}

/* Section Headers */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
  cursor: pointer;
}

.section-header h2 {
  margin: 0;
  color: #333;
  }

  .info-item.full-width {
    grid-column: 1 / -1;
}

/* Expand Button */
.expand-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  transition: transform 0.3s ease, background-color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.expand-btn:hover {
  background-color: #f0f0f0;
}

.expand-btn.expanded {
  transform: rotate(180deg);
}

.expand-btn .material-icon-sm {
  font-size: 20px;
  color: #666;
}

/* Section Content */
.section-content {
  overflow: hidden;
  transition: all 0.3s ease-in-out;
}

/* Basic responsive styles */
@media (max-width: 768px) {
  .client-dashboard {
    padding: 10px;
  }

  .dashboard-content {
    gap: 20px;
  }

  .section-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .header-left, .header-right {
    flex-direction: column;
    gap: 10px;
  }

  .view-mode-toggle {
    order: -1;
  }

  .users-grid, .providers-grid, .locations-grid, .profile-grid {
    grid-template-columns: 1fr;
  }

  .user-header, .provider-header {
    flex-direction: column;
    gap: 10px;
  }

  .user-actions, .provider-actions {
    align-self: flex-start;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .large-modal .modal-content {
    max-width: 95vw;
  }
}
</style>
