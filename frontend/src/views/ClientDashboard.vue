<template>
    <div class="client-dashboard max-w-7xl mx-auto px-6 py-8">
      <!-- Dashboard Header (keeping existing) -->
      <div class="dashboard-header flex justify-between items-center mb-8 pb-6 border-b border-gray-200">
        <div class="left">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ getOrganizationName() }}</h1>
          <div class="profile-completeness">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-20 h-4 bg-gray-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-green-500 transition-all duration-300"
                  :style="{ width: profileCompleteness + '%' }"
                ></div>
              </div>
              <span class="text-xs font-medium text-gray-600">{{ profileCompleteness }}% Complete</span>
            </div>
            <p class="text-sm text-gray-600">Complete your profile to unlock all features</p>
          </div>
        </div>
        <div class="right flex items-center gap-4">
          <button
            @click="$router.push('/create-invitation')"
            class="btn-secondary flex items-center gap-2 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <span class="material-icon-sm">person_add</span>
            Invite Users
          </button>
          <button
            v-if="isAdmin"
            @click="handleUpgradeClick"
            class="btn-primary flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            <span class="material-icon-sm">upgrade</span>
            Upgrade
          </button>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Role:</span>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
              {{ roleDisplayNames && roleDisplayNames[userRole] ? roleDisplayNames[userRole] : getFallbackRoleName(userRole) }}
            </span>
          </div>
          <button
            @click="signOut()"
            class="btn-secondary flex items-center gap-2 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <span class="material-icon-sm">logout</span>
            Sign Out
          </button>
        </div>
      </div>

      <div class="dashboard-content grid gap-8">
        <!-- User Identity Bar -->
        <div class="user-identity-bar bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-6 shadow-sm">
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
                  <span class="text-sm font-semibold text-gray-900">{{ getOrganizationName() }}</span>
                </div>
              </div>
            </div>
            <div class="subscription-info flex items-center gap-4">
              <div class="subscription-badge flex items-center gap-2 px-3 py-1 bg-white border border-gray-300 rounded-full">
                <span class="material-icon-sm text-green-600">workspace_premium</span>
                <span class="text-xs font-medium text-gray-700">Free Plan</span>
              </div>
              <!-- Upgrade button for admin users only -->
              <button
                v-if="isAdmin"
                @click="handleUpgradeClick"
                class="upgrade-btn flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-sm transform hover:scale-105"
              >
                <span class="material-icon-sm">upgrade</span>
                Upgrade to Premium
              </button>
            </div>
          </div>
        </div>

        <!-- User Management Section - Only for budget controllers -->
        <UserManagementSection
          v-if="userRole === 2"
          :expanded="sectionsExpanded.users"
          :users="users"
          :available-roles="availableRoles"
          :is-admin="isAdmin"
          :current-user-id="userId"
          @toggle="sectionsExpanded = {...sectionsExpanded, users: !sectionsExpanded.users}"
          @add-user="showAddUserModal = true"
          @edit-user="handleEditUser"
        />

        <!-- Locations Section - Only for budget controllers -->
        <LocationManagementSection
          v-if="userRole === 2"
          :expanded="sectionsExpanded.locations"
          :locations="locations"
          :locations-view-mode="locationsViewMode"
          :is-admin="isAdmin"
          @toggle="sectionsExpanded = {...sectionsExpanded, locations: !sectionsExpanded.locations}"
          @view-mode-changed="locationsViewMode = $event"
          @edit-location="handleEditLocation"
          @filter-jobs="filterJobsByLocation"
        />

        <!-- Approved Providers Section - Only for budget controllers -->
        <ProviderManagementSection
          v-if="userRole === 2"
          :expanded="sectionsExpanded.providers"
          :approved-providers="approvedProviders"
          :is-admin="isAdmin"
          @toggle="sectionsExpanded = {...sectionsExpanded, providers: !sectionsExpanded.providers}"
          @view-provider-jobs="handleViewProviderJobs"
          @browse-providers="$router.push('/browse-providers')"
        />

        <!-- Jobs Section - For all user roles -->
        <JobManagementSection
          :jobs="jobs"
          :job-filters="jobFilters"
          :locations="locations"
          :approved-providers="approvedProviders"
        :user-role="userRole"
        :client-profile="clientProfile"
        @update-job-filters="updateJobFilters"
        @load-jobs="loadJobs"
        @create-job="$emit('show-create-job')"
        @view-job-details="handleViewJobDetails"
        @edit-job="handleEditJob"
        @toggle-archive-job="toggleArchiveJob"
        @confirm-job="console.log('EVENT RECEIVED: confirm-job', $event); showJobConfirmationModal = true; confirmationJob = $event; console.log('ClientDashboard: AFTER event handler - showJobConfirmationModal:', showJobConfirmationModal, 'confirmationJob:', confirmationJob)"
        @reject-job="showJobRejectionModal = true; rejectionJob = $event"
        @accept-quote="showQuoteResponseModal = true; quoteResponseJob = $event; quoteResponseAction = 'accept'"
        @reject-quote="showQuoteRejectionModal = true; quoteRejectionJob = $event; quoteRejectionAction = 'reject'"
        />
      </div>

      <!-- Modals -->
      <AddUserModal
        v-if="showAddUserModal"
        :available-roles="availableRoles"
        :adding-user="addingUser"
        @close="showAddUserModal = false"
        @submit="handleAddUser"
      />

      <EditUserModal
        v-if="showEditUserModal"
        :user="editingUser"
        :available-roles="availableRoles"
        :updating-user="updatingUser"
        @close="showEditUserModal = false"
        @submit="handleUpdateUser"
      />

      <CreateJobModal
        v-if="showCreateJobModal"
        :locations="locations"
        :creating-job="creatingJob"
        :new-job="newJob"
        @close="closeCreateJobModal"
        @submit="handleCreateJob"
        @qr-detected="handleQrDetected"
      />

      <JobDetailsModal
        v-if="showJobDetailsModal"
        :job="selectedJob"
        @close="showJobDetailsModal = false"
        @image-click="selectedImage = $event"
      />

      <!-- Job Confirmation Modal -->
      <!-- EMERGENCY: Force modal OFF to test if template condition is driving it -->
      <div v-if="false && showJobConfirmationModal" class="modal-overlay" @click="console.log('ClientDashboard: Modal overlay clicked'); closeJobConfirmationModal()">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Confirm Job Completion</h3>
            <button @click="console.log('ClientDashboard: Close button clicked'); closeJobConfirmationModal()" class="close-btn">&times;</button>
          </div>
          <div v-if="confirmationJob" class="confirmation-form">
            <div class="job-summary">
              <h4>{{ confirmationJob.item_identifier || 'No Item ID' }}</h4>
              <p class="job-description">{{ confirmationJob.fault_description }}</p>
              <div class="job-meta">
                <span><strong>Client:</strong> {{ confirmationJob.client_name }}</span>
                <span><strong>Location:</strong> {{ confirmationJob.location_name }}</span>
              </div>
            </div>
            <div class="form-group">
              <label for="confirmation-notes">Confirmation Notes (Optional)</label>
              <textarea id="confirmation-notes" v-model="confirmationNotes" rows="3"
                        placeholder="Add any notes about confirming this work..."></textarea>
            </div>
            <div class="confirmation-info">
              <div class="info-banner">
                <div class="info-icon">ℹ️</div>
                <div class="info-content">
                  <p><strong>What happens when you confirm?</strong></p>
                  <p>This job will be marked as "Confirmed" and archived for both parties. This action cannot be undone.</p>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button @click="closeJobConfirmationModal" class="btn-secondary">Cancel</button>
              <button @click="confirmJob" class="btn-primary" :disabled="confirmingJob">
                {{ confirmingJob ? 'Confirming...' : 'Confirm Job' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Image Modal for Full Size View -->
      <div v-if="selectedImage" class="modal-overlay" @click="selectedImage = null">
        <div class="image-modal-content" @click.stop>
          <div class="image-modal-header">
            <h3>{{ selectedImage.original_filename }}</h3>
            <button @click="selectedImage = null" class="close-btn">&times;</button>
          </div>
          <div class="image-modal-body">
            <img :src="generateImageUrl(selectedImage)"
                 :alt="selectedImage.original_filename"
                 class="full-size-image">
          </div>
        </div>
      </div>
  </div>
</template>

<script>
import ImageUpload from '@/components/ImageUpload.vue'
import QrScanner from '@/components/QrScanner.vue'
import { apiFetch, handleTokenExpiration, loadRoleSettings } from '@/utils/api.js'

// New component imports
import BusinessProfileSection from '@/components/dashboard/BusinessProfileSection.vue'
import UserManagementSection from '@/components/dashboard/UserManagementSection.vue'
import ProviderManagementSection from '@/components/dashboard/ProviderManagementSection.vue'
import LocationManagementSection from '@/components/dashboard/LocationManagementSection.vue'
import JobManagementSection from '@/components/dashboard/JobManagementSection.vue'
import AddUserModal from '@/components/modals/AddUserModal.vue'
import EditUserModal from '@/components/modals/EditUserModal.vue'
import CreateJobModal from '@/components/modals/CreateJobModal.vue'
import JobDetailsModal from '@/components/modals/JobDetailsModal.vue'

export default {
  name: 'ClientDashboard',
  components: {
    ImageUpload,
    QrScanner,
    // New components
    BusinessProfileSection,
    UserManagementSection,
    ProviderManagementSection,
    LocationManagementSection,
    JobManagementSection,
    AddUserModal,
    EditUserModal,
    CreateJobModal,
    JobDetailsModal
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
        id: null,
        name: '',
        address: '',
        coordinates: '',
        access_rules: '',
        access_instructions: ''
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
      showEditJobModal: false,
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
      editingJob: null,
      originalJobStatus: null,
      originalProviderId: null,
      editingImages: [], // Array to store additional images for editing
      // Section collapse/expand state
      sectionsExpanded: {
        profile: false, // Profile section collapsed by default
        users: false,
        locations: false,
        providers: false,
        jobs: true // Jobs section expanded by default
      },

      // EMERGENCY DEBUG FLAG - For testing modal blocking
      forceModalReset: true
    }
  },
  async mounted() {
    // Handle expired token on component mount
    if (handleTokenExpiration()) {
      return // Stop execution if token was expired and user was redirected
    }

    // CRITICAL FIX: Explicitly reset all modals on component mount to prevent modal state bleeding
    console.log('ClientDashboard: Forcing modal reset before mount, previous states:', {
      showJobDetailsModal: this.showJobDetailsModal,
      showEditJobModal: this.showEditJobModal,
      showAddUserModal: this.showAddUserModal,
      showCreateJobModal: this.showCreateJobModal,
      showJobConfirmationModal: this.showJobConfirmationModal,
      showJobRejectionModal: this.showJobRejectionModal,
      showQuoteResponseModal: this.showQuoteResponseModal,
      showQuoteRejectionModal: this.showQuoteRejectionModal
    })

    // Force reset all modals to false on every component mount
    this.showJobDetailsModal = false
    this.showEditJobModal = false
    this.showAddUserModal = false
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

    // DEBUG: Log modal state after force reset
    console.log('ClientDashboard: Modal state AFTER force reset:', {
      showJobDetailsModal: this.showJobDetailsModal,
      showEditJobModal: this.showEditJobModal,
      showAddUserModal: this.showAddUserModal,
      showCreateJobModal: this.showCreateJobModal,
      showJobConfirmationModal: this.showJobConfirmationModal,
      showJobRejectionModal: this.showJobRejectionModal,
      showQuoteResponseModal: this.showQuoteResponseModal,
      showQuoteRejectionModal: this.showQuoteRejectionModal,
      confirmationJob: this.confirmationJob ? 'SET' : 'NULL'
    })

    // Load role settings first so they're available for role display
    try {
      const settings = await loadRoleSettings()
      this.roleDisplayNames = settings || {}
    } catch (error) {
      console.warn('Failed to load role settings, using defaults:', error)
      this.roleDisplayNames = {}
    }

    // DEBUG: Check immediately after loading that modal state hasn't changed
    console.log('ClientDashboard: Post-role-settings modal state:', {
      showJobConfirmationModal: this.showJobConfirmationModal,
      confirmationJob: this.confirmationJob
    })

    this.loadClientProfile()
    this.loadUsers()
    this.loadApprovedProviders()
    this.loadAvailableRoles()
    this.loadLocations()
    this.loadJobs()
  },
  computed: {
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

    async handleUpdateUser(userData) {
      this.updatingUser = true
      try {
        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'PUT',
          body: JSON.stringify(userData)
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

    handleViewProviderJobs(provider) {
      // Filter jobs to show only those for the selected provider
      this.jobFilters.provider_id = provider.service_provider_id
      this.loadJobs()
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

    async handleEditJob(job) {
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

    updateJobFilters(filters) {
      this.jobFilters = { ...this.jobFilters, ...filters }
    },

    closeCreateJobModal() {
      this.showCreateJobModal = false
      this.resetNewJobForm()
    },

    async handleCreateJob(jobData) {
      // This is now handled by the modal component itself
      // We can keep this as a passthrough if needed
      console.log('Creating job:', jobData)
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

      // Budget controllers can edit when status is 'Reported', 'Declined', or 'Quote Requested'
      if (this.userRole === 2 && ['Reported', 'Declined', 'Quote Requested'].includes(job.job_status)) {
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

      // Upload images if any were selected - let the ImageUpload component handle this
      if (this.selectedImages.length > 0) {
        console.log('ClientDashboard: Starting image upload for job', jobId, 'with', this.selectedImages.length, 'images')
        try {
          console.log('ClientDashboard: Calling uploadImages with jobId:', jobId)
          await this.$refs.imageUpload.uploadImages(jobId)
          console.log('ClientDashboard: Image upload completed successfully')
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

    async editLocation(location) {
      // Populate the editing form with current location data
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

    async updateJob() {
      // Validate that provider is selected for status changes that require it
      if ((this.editingJob.job_status === 'Assigned' || this.editingJob.job_status === 'Quote Requested') && !this.editingJob.assigned_provider_id) {
        alert('A service provider must be selected to set this status.')
        return
      }

      try {
        const updateData = {
          job_id: this.editingJob.id
        }

        // Only include fields that can be edited based on status and role
        if (this.canEditJobDetails(this.editingJob)) {
          // Full edit when status is 'Reported' - only add changed fields
          if (this.editingJob.item_identifier !== this.originalItemIdentifier) {
            updateData.item_identifier = this.editingJob.item_identifier || null
          }
          if (this.editingJob.fault_description !== this.originalFaultDescription) {
            updateData.fault_description = this.editingJob.fault_description
          }
          if (this.editingJob.contact_person !== this.originalContactPerson) {
            updateData.contact_person = this.editingJob.contact_person || null
          }
        }

        // Status and provider can always be edited (when allowed)
        if (this.editingJob.job_status !== this.originalJobStatus) {
          updateData.job_status = this.editingJob.job_status
        }

        if (this.editingJob.assigned_provider_id !== this.originalProviderId) {
          updateData.assigned_provider_id = this.editingJob.assigned_provider_id || null
        }

        // Only proceed if there are changes or new images to upload
        const hasChanges = Object.keys(updateData).length > 1
        const hasNewImages = this.editingImages && this.editingImages.length > 0

        if (!hasChanges && !hasNewImages) {
          alert('No changes to save')
          return
        }

        // Update job details if there are changes
        if (hasChanges) {
          const response = await apiFetch('/backend/api/client-jobs.php', {
            method: 'PUT',
            body: JSON.stringify(updateData)
          })

          if (!response.ok) {
            const errorData = await response.json()
            this.handleError(errorData)
            return
          }
        }

        // Upload additional images if any were selected
        if (hasNewImages) {
          await this.$refs.editImageUpload.uploadImages(this.editingJob.id)
        }

        alert('Job updated successfully!')
        this.showEditJobModal = false
        this.loadJobs()
      } catch (error) {
        alert('Failed to update job')
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

    // Test method for QR handling logic (can be removed after testing)
    testQrHandling() {
      console.log('Testing QR handling logic...')

      // Test cases for different QR scenarios
      const testCases = [
        // Full structured data
        { clientId: 1, itemIdentifier: 'TEST-001', locationName: 'Main Office' },
        // Item only
        { clientId: 1, itemIdentifier: 'TEST-002' },
        // Location only (will use location as item)
        { clientId: 1, locationName: 'Warehouse' },
        // No structured data (fallback to raw string)
        'RAW-QR-DATA-123',
        // Invalid client ID
        { clientId: 999, itemIdentifier: 'INVALID-CLIENT' }
      ]

      testCases.forEach((testData, index) => {
        console.log(`Test case ${index + 1}:`, testData)
        // This would normally be called by the QR scanner
        // For testing, we could modify the method to accept test data
      })
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
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === job.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].archived_by_client = !job.archived_by_client
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

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
    },

    // Handle provider selection change - automatically set status to 'Assigned'
    onProviderSelected() {
      if (this.editingJob.assigned_provider_id && this.editingJob.job_status !== 'Assigned' && this.editingJob.job_status !== 'Quote Requested') {
        this.editingJob.job_status = 'Assigned'
      }
    },

    // Job Confirmation/Rejection Methods
    showJobConfirmationModal(job) {
      this.confirmationJob = job
      this.confirmationNotes = ''
      this.showJobConfirmationModal = true
    },

    closeJobConfirmationModal() {
      this.showJobConfirmationModal = false
      this.confirmationJob = null
      this.confirmationNotes = ''
    },

    showJobRejectionModal(job) {
      this.rejectionJob = job
      this.rejectionNotes = ''
      this.showJobRejectionModal = true
    },

    closeJobRejectionModal() {
      this.showJobRejectionModal = false
      this.rejectionJob = null
      this.rejectionNotes = ''
    },

    async confirmJob() {
      if (!this.confirmationJob) return

      this.confirmingJob = true
      try {
        const response = await apiFetch('/backend/api/job-completion-confirmation.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.confirmationJob.id,
            action: 'confirm',
            notes: this.confirmationNotes
          })
        })

        if (response.ok) {
          const data = await response.json()
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === this.confirmationJob.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = 'Confirmed'
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

          this.closeJobConfirmationModal()
          alert('Job confirmed successfully!')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to confirm job')
      } finally {
        this.confirmingJob = false
      }
    },

    async rejectJob() {
      if (!this.rejectionJob) return

      if (!this.rejectionNotes.trim()) {
        alert('Please provide a reason for rejecting this job.')
        return
      }

      this.rejectingJob = true
      try {
        const response = await apiFetch('/backend/api/job-completion-confirmation.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.rejectionJob.id,
            action: 'reject',
            notes: this.rejectionNotes
          })
        })

        if (response.ok) {
          const data = await response.json()
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === this.rejectionJob.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = 'Incomplete'
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

          this.closeJobRejectionModal()
          alert('Job rejected and returned for rework!')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to reject job')
      } finally {
        this.rejectingJob = false
      }
    },

    // Quote Response Methods
    showQuoteResponseModal(job) {
      this.quoteResponseJob = job
      this.quoteResponseNotes = ''
      this.showQuoteResponseModal = true
    },

    closeQuoteResponseModal() {
      this.showQuoteResponseModal = false
      this.quoteResponseJob = null
      this.quoteResponseNotes = ''
    },

    showQuoteRejectionModal(job) {
      this.quoteRejectionJob = job
      this.quoteRejectionNotes = ''
      this.showQuoteRejectionModal = true
    },

    closeQuoteRejectionModal() {
      this.showQuoteRejectionModal = false
      this.quoteRejectionJob = null
      this.quoteRejectionNotes = ''
    },

    async acceptQuote() {
      if (!this.quoteResponseJob) return

      this.acceptingQuote = true
      try {
        const response = await apiFetch('/backend/api/job-quotation-responses.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.quoteResponseJob.id,
            action: 'accept',
            notes: this.quoteResponseNotes
          })
        })

        if (response.ok) {
          const data = await response.json()
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === this.quoteResponseJob.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = 'Quote Accepted'
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

          this.closeQuoteResponseModal()
          alert('Quote accepted successfully! The service provider will be notified to proceed with the work.')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to accept quote')
      } finally {
        this.acceptingQuote = false
      }
    },

    async rejectQuote() {
      if (!this.quoteRejectionJob) return

      if (!this.quoteRejectionNotes.trim()) {
        alert('Please provide a reason for rejecting this quote.')
        return
      }

      this.rejectingQuote = true
      try {
        const response = await apiFetch('/backend/api/job-quotation-responses.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.quoteRejectionJob.id,
            action: 'reject',
            notes: this.quoteRejectionNotes
          })
        })

        if (response.ok) {
          const data = await response.json()
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === this.quoteRejectionJob.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].job_status = 'Quote Rejected'
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

          this.closeQuoteRejectionModal()
          alert('Quote rejected successfully! The service provider will be notified.')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to reject quote')
      } finally {
        this.rejectingQuote = false
      }
    },

    formatCurrency(amount) {
      return parseFloat(amount || 0).toLocaleString('en-ZA', {
        style: 'currency',
        currency: 'ZAR'
      })
    }


  }
}
</script>

<style scoped>
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

/*.dashboard-header h1 {
  color: #333;
  margin-bottom: 10px;
}*/

.profile-completeness {
  display: flex;
  align-items: center;
  gap: 15px;
  position: relative;
  left: 32px;
}

.completeness-bar {
  width: 200px;
  height: 8px;
  background-color: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.completeness-fill {
  height: 100%;
  background-color: #28a745;
  transition: width 0.3s ease;
}

.completeness-text {
  font-weight: 500;
  color: #666;
}
/*
.dashboard-header p {
  color: #666;
  font-size: 1.1em;
}*/

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

/* Users Section */
.users-section, .providers-section, .jobs-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users-grid, .providers-grid, .jobs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 15px;
}

/* Job Filters */
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

/* Job Cards */
.job-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
  background: white;
}

.job-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.job-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.job-status {
  display: flex;
  align-items: center;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.reported {
  background: #ffc107;
  color: #212529;
}

.status-badge.assigned {
  background: #17a2b8;
  color: white;
}

.status-badge.in-progress {
  background: #007bff;
  color: white;
}

.status-badge.completed {
  background: #28a745;
  color: white;
}

.job-actions {
  display: flex;
  gap: 8px;
}

.job-content {
  padding: 15px;
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

.job-footer {
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.job-date {
  font-size: 0.85em;
  color: #666;
  margin: 0;
}

/* No Jobs State */
.no-jobs {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-jobs-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.no-jobs h3 {
  margin-bottom: 10px;
}

/* User Cards */
.user-card, .provider-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
}

.user-card:hover, .provider-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.user-header, .provider-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.user-avatar, .provider-logo {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #007bff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.2em;
}

.avatar-placeholder, .logo-placeholder {
  font-size: 1.5em;
}

.user-actions, .provider-actions {
  display: flex;
  gap: 8px;
}

.user-content, .provider-content {
  padding: 15px;
}

.user-name, .provider-name {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 1.1em;
}

.user-email, .provider-address {
  color: #666;
  margin-bottom: 8px;
  font-size: 0.9em;
}

.user-role {
  margin-bottom: 12px;
}

.role-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 500;
  text-transform: uppercase;
}

.role-badge.admin {
  background: #28a745;
  color: white;
}

.role-badge.controller {
  background: #17a2b8;
  color: white;
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

.user-footer, .provider-footer {
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.user-date, .approval-date {
  font-size: 0.85em;
  color: #666;
  margin: 0;
}

/* No Items States */
.no-users, .no-providers {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-users-icon, .no-providers-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.no-users h3, .no-providers h3 {
  margin-bottom: 10px;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000; /* Increased for better modal layering */
  user-select: none; /* Prevent text selection on overlay */
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 5px 10px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

/* Form Styles */
.user-form {
  padding: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group input:disabled {
  background: #f8f9fa;
  color: #666;
}

.form-group textarea {
  width: 100%;
  min-height: 80px;
  resize: vertical;
  box-sizing: border-box;
}

.input-with-button {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.input-with-button input {
  flex: 1;
}

.qr-scanner-inline {
  flex-shrink: 0;
}

.form-help {
  display: block;
  margin-top: 3px;
  font-size: 0.8em;
  color: #666;
}

.readonly-field {
  padding: 10px;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #666;
  font-style: italic;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-primary, .btn-secondary, .btn-danger {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.9em;
}

/* Loading and Error States */
.loading-state, .error-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.loading-state h3, .error-state h3 {
  margin-bottom: 10px;
}

/* View Mode Toggle */
.header-left {
  align-items: left;
  gap: 20px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 15px;
}

.view-mode-toggle {
  display: flex;
  border: 1px solid #ddd;
  border-radius: 6px;
  overflow: hidden;
}

.view-mode-btn {
  padding: 8px 16px;
  border: none;
  background: white;
  color: #666;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.view-mode-btn:hover {
  background: #f8f9fa;
}

.view-mode-btn.active {
  background: #007bff;
  color: white;
}

.view-icon {
  font-size: 16px;
}

/* Locations Table View */
.locations-table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.locations-table-wrapper {
  min-width: 600px;
}

.locations-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.locations-table th {
  background: #f8f9fa;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #e0e0e0;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.5px;
}

.locations-table td {
  padding: 15px;
  border-bottom: 1px solid #e0e0e0;
  vertical-align: middle;
}

.location-row {
  transition: background-color 0.2s;
  cursor: pointer;
}

.location-row:hover {
  background: #f8f9fa;
}

.location-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.location-name-cell .location-icon {
  color: #007bff;
  font-size: 16px;
}

.location-name-text {
  font-weight: 500;
  color: #333;
}

.col-name {
  width: 30%;
}

.col-address {
  width: 40%;
}

.col-jobs {
  width: 15%;
  text-align: center;
}

.col-actions {
  width: 15%;
  text-align: center;
}

.job-count-badge {
  display: inline-block;
  background: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  min-width: 20px;
  text-align: center;
}

.table-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
}

/* Large Modal for Job Details */
.large-modal .modal-content {
  max-width: 900px;
  padding: 5px 10px;
}

.job-details-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

.job-info-section {
  margin-bottom: 30px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item span {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.description-section {
  margin-bottom: 20px;
}

.description-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.fault-description {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
}

.contact-section {
  margin-bottom: 20px;
}

.contact-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

/* Images Gallery */
.images-section h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 16px;
}

.no-images {
  text-align: center;
  padding: 40px 20px;
  background: #f8f9fa;
  border-radius: 8px;
  color: #666;
}

.no-images-icon {
  font-size: 3em;
  margin-bottom: 10px;
}

.images-gallery {
  margin-top: 15px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.gallery-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.gallery-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.gallery-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 8px;
  font-size: 11px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.image-filename {
  font-weight: 500;
}

/* Image Modal */
.image-modal-content {
  background: white;
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.image-modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.image-modal-body {
  padding: 20px;
  text-align: center;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.full-size-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 4px;
}

/* Edit Job Modal Styles */
.edit-details-section {
  margin-bottom: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.edit-details-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 16px;
}

.readonly-details-section {
  margin-bottom: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.readonly-details-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 16px;
}

.readonly-info {
  display: grid;
  gap: 10px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row label {
  font-weight: 600;
  color: #666;
  font-size: 14px;
}

.info-row span {
  color: #333;
  font-size: 14px;
}

/* Profile Section Styles */
.profile-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-content {
  margin-top: 15px;
}

.profile-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.profile-card {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.profile-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.profile-card-title {
  color: #333;
  font-size: 1.1em;
  font-weight: 600;
  margin: 0 0 15px 0;
  border-bottom: 2px solid #007bff;
  padding-bottom: 8px;
}

.profile-field {
  margin-bottom: 12px;
}

.profile-field label {
  display: block;
  font-size: 0.85em;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.profile-field span {
  color: #333;
  font-size: 0.95em;
  line-height: 1.4;
}

.profile-completeness-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #e3f2fd;
  border: 1px solid #2196f3;
  border-radius: 12px;
  padding: 8px 12px;
  min-width: 60px;
}

.completeness-percentage {
  font-size: 1.2em;
  font-weight: bold;
  color: #1976d2;
  line-height: 1;
}

.completeness-label {
  font-size: 0.7em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.active {
  background: #28a745;
  color: white;
}

.status-badge.inactive {
  background: #dc3545;
  color: white;
}

/* Admin Disabled Notice */
.admin-disabled-notice {
  background: #fff3cd;
  border: 1px solid #ffeeba;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.disabled-banner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
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

/* Profile Form Styles */
.profile-form {
  padding: 20px;
}

.form-section {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.section-title {
  color: #333;
  font-size: 1.1em;
  font-weight: 600;
  margin: 0 0 20px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #007bff;
}

.form-section .form-row {
  margin-bottom: 15px;
}

.form-section .form-group {
  margin-bottom: 15px;
}

/* No Profile State */
.no-profile {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-profile-icon {
  font-size: 4em;
  margin-bottom: 20px;
  opacity: 0.5;
}

.no-profile h3 {
  margin-bottom: 10px;
  color: #333;
}

/* Quote Modal Styles */
.quote-response-form,
.quote-rejection-form {
  padding: 20px;
}

.quote-details-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  border: 1px solid #e0e0e0;
}

.quote-details-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 16px;
  border-bottom: 2px solid #28a745;
  padding-bottom: 8px;
}

.quote-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.quote-info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.quote-info-item label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.quote-info-item span {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.quote-amount {
  font-size: 18px;
  font-weight: bold;
  color: #28a745;
}

.quote-description-section {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #e0e0e0;
}

.quote-description-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.quote-description-content {
  background: white;
  padding: 12px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
  border: 1px solid #ddd;
}

.quote-document-section {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #e0e0e0;
}

.quote-document-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.quote-document-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #007bff;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.quote-document-link:hover {
  background: #0056b3;
  color: white;
  text-decoration: none;
}

.quote-response-info,
.quote-rejection-info {
  margin-top: 20px;
}

.info-banner,
.warning-banner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 15px;
  border-radius: 8px;
  background: #e3f2fd;
  border: 1px solid #2196f3;
}

.warning-banner {
  background: #fff3cd;
  border: 1px solid #ffc107;
}

.info-icon,
.warning-icon {
  font-size: 20px;
  color: #1976d2;
  flex-shrink: 0;
  margin-top: 2px;
}

.warning-icon {
  color: #856404;
}

.info-content,
.warning-content {
  flex: 1;
}

.info-content p,
.warning-content p {
  margin: 0 0 8px 0;
  font-size: 14px;
  line-height: 1.4;
}

.info-content p:last-child,
.warning-content p:last-child {
  margin-bottom: 0;
}

.info-content strong,
.warning-content strong {
  font-weight: 600;
}

/* Responsive Design */
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

  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
  }

  .locations-table {
    font-size: 12px;
  }

  .locations-table th,
  .locations-table td {
    padding: 10px;
  }

  .edit-details-section,
  .readonly-details-section,
  .quote-details-section {
    padding: 15px;
  }

  .quote-info-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }

  .profile-completeness-badge {
    min-width: 50px;
    padding: 6px 10px;
  }

  .completeness-percentage {
    font-size: 1em;
  }

  .info-banner,
  .warning-banner {
    flex-direction: column;
    gap: 8px;
  }

  .info-icon,
  .warning-icon {
    align-self: flex-start;
  }
}


</style>
