<template>
  <div class="edit-profile-page min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
          <!-- Breadcrumb Navigation -->
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <router-link
              :to="userType === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
              class="text-blue-600 hover:text-blue-800"
              @click="cancelEditing"
            >
              {{ userType === 'service-provider' ? 'Service Provider Dashboard' : 'Client Dashboard' }}
            </router-link>
            <span class="text-gray-400">></span>
            <span class="text-gray-900 font-medium">Edit Organization Profile</span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button
              @click="cancelEditing"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
              :disabled="updatingProfile"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="text-center">
          <div class="loading-spinner w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p class="text-gray-600">Loading profile data...</p>
        </div>
      </div>

      <!-- Page Content -->
      <div v-else class="space-y-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center space-x-3 mb-4">
            <span class="material-icon text-blue-600">business</span>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Edit Organization Profile</h1>
              <p class="text-gray-600">Update your business information and contact details</p>
            </div>
          </div>

          <div class="flex items-center gap-3 text-sm">
            <div class="profile-completeness-indicator">
              <div class="w-32 h-3 bg-gray-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-green-500 to-blue-500 rounded-full transition-all duration-500"
                  :style="{ width: profileCompleteness + '%' }"
                ></div>
              </div>
              <span class="ml-3 font-medium">{{ profileCompleteness }}% Complete</span>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 space-y-8">
          <!-- Basic Information -->
          <div class="bg-gray-50 rounded-lg p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-3 border-b border-gray-200 pb-2">
              <span class="material-icon text-blue-600">business_center</span>
              Basic Information
            </h3>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label for="business-name" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">store</span>
                  Business Name *
                </label>
                <input
                  type="text"
                  id="business-name"
                  v-model="localProfile.name"
                  required
                  class="form-input"
                  placeholder="Your business name"
                >
              </div>
              <div class="space-y-2">
                <label for="business-website" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">link</span>
                  Website
                </label>
                <input
                  type="text"
                  id="business-website"
                  v-model="localProfile.website"
                  class="form-input"
                  placeholder="https://yourbusiness.com"
                >
              </div>
            </div>

            <div class="space-y-2">
              <label for="business-address" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">location_on</span>
                Business Address
              </label>
              <textarea
                id="business-address"
                v-model="localProfile.address"
                rows="3"
                class="form-input resize-none"
                placeholder="Business address"
              ></textarea>
            </div>

            <div class="space-y-2">
              <label for="business-description" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">description</span>
                Business Description
              </label>
              <textarea
                id="business-description"
                v-model="localProfile.description"
                rows="4"
                class="form-input resize-none"
                placeholder="Brief description of your business and services"
              ></textarea>
            </div>
          </div>

          <!-- Manager Contact -->
          <div class="bg-gray-50 rounded-lg p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-3 border-b border-gray-200 pb-2">
              <span class="material-icon text-green-600">contact_phone</span>
              Manager Contact Information
            </h3>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label for="manager-name" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">person</span>
                  Manager Name
                </label>
                <input
                  type="text"
                  id="manager-name"
                  v-model="localProfile.manager_name"
                  class="form-input"
                  placeholder="Primary contact person"
                >
              </div>
              <div class="space-y-2">
                <label for="manager-email" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">email</span>
                  Manager Email
                </label>
                <input
                  type="email"
                  id="manager-email"
                  v-model="localProfile.manager_email"
                  class="form-input"
                  placeholder="manager@yourbusiness.com"
                >
              </div>
            </div>

            <div class="space-y-2">
              <label for="manager-phone" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">phone</span>
                Manager Phone
              </label>
              <input
                type="tel"
                id="manager-phone"
                v-model="localProfile.manager_phone"
                class="form-input"
                placeholder="+27 12 345 6789"
              >
            </div>
          </div>

          <!-- Business Details -->
          <div class="bg-gray-50 rounded-lg p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-3 border-b border-gray-200 pb-2">
              <span class="material-icon text-purple-600">assignment</span>
              Business Registration Details
            </h3>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label for="vat-number" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">receipt</span>
                  VAT Number
                </label>
                <input
                  type="text"
                  id="vat-number"
                  v-model="localProfile.vat_number"
                  class="form-input"
                  placeholder="VAT registration number"
                >
              </div>
              <div class="space-y-2">
                <label for="business-registration" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">business</span>
                  Business Registration Number
                </label>
                <input
                  type="text"
                  id="business-registration"
                  v-model="localProfile.business_registration_number"
                  class="form-input"
                  placeholder="Company registration number"
                >
              </div>
            </div>

            <div class="space-y-2">
              <label for="business-status" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">toggle_on</span>
                Status
              </label>
              <input
                type="text"
                id="business-status"
                :value="localProfile.is_active ? 'Active' : 'Inactive'"
                class="form-input"
                readonly
              >
              <p class="text-xs text-gray-500">Account status is managed by administrators and cannot be changed here.</p>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
            <router-link
              :to="userType === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
              class="btn btn-secondary order-2 sm:order-1"
            >
              Cancel
            </router-link>
            <button
              type="submit"
              class="btn btn-primary order-1 sm:order-2 flex items-center gap-2"
              :disabled="updatingProfile"
            >
              <span v-if="updatingProfile" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ updatingProfile ? 'Updating Profile...' : 'Update Profile' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'EditProfile',
  props: {
    userType: {
      type: String,
      default: 'client'
    },
    scrollPosition: {
      type: [String, Number],
      default: 0
    }
  },
  data() {
    return {
      loading: true,
      updatingProfile: false,
      localProfile: {
        name: '',
        website: '',
        address: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        vat_number: '',
        business_registration_number: '',
        is_active: true
      },
      profileCompleteness: 0
    }
  },
  async mounted() {
    await this.loadProfile()
  },
  methods: {
    async loadProfile() {
      try {
        this.loading = true

        // Determine API endpoint based on user type
        const apiEndpoint = this.userType === 'service-provider' ? '/service-provider-profile.php' : '/client-profile.php'

        const response = await apiFetch(`/backend/api${apiEndpoint}`)

        if (response.ok) {
          const data = await response.json()
          this.localProfile = { ...data.profile }
          this.calculateProfileCompleteness()
        } else {
          console.error('Failed to load profile')
          throw new Error('Failed to load profile')
        }
      } catch (error) {
        console.error('Error loading profile:', error)
        alert('Failed to load profile data. Please try again.')
        this.returnToDashboard()
      } finally {
        this.loading = false
      }
    },

    calculateProfileCompleteness() {
      let score = 0
      const maxScore = 100

      // Basic information (40 points)
      const basicFields = ['name', 'address', 'description']
      basicFields.forEach(field => {
        if (this.localProfile[field] && this.localProfile[field].trim()) score += 13
      })
      if (this.localProfile.website && this.localProfile.website.trim()) score += 1 // Website is part of basic

      // Manager contact (30 points)
      const managerFields = ['manager_name', 'manager_email', 'manager_phone']
      managerFields.forEach(field => {
        if (this.localProfile[field] && this.localProfile[field].trim()) score += 10
      })

      // Business details (20 points)
      if (this.localProfile.vat_number && this.localProfile.vat_number.trim()) score += 10
      if (this.localProfile.business_registration_number && this.localProfile.business_registration_number.trim()) score += 10

      // Account status (10 points)
      if (this.localProfile.is_active) score += 10

      this.profileCompleteness = Math.max(0, Math.min(100, Math.round(score)))
    },

    async handleSubmit() {
      this.updatingProfile = true

      try {
        // Create a mutable copy to avoid directly mutating the component's state before the API call
        const profileToSend = { ...this.localProfile };

        // Prepend https:// to website if it's missing
        if (profileToSend.website && !/^https?:\/\//i.test(profileToSend.website)) {
          profileToSend.website = 'https://' + profileToSend.website;
        }

        // Determine API endpoint based on user type
        const apiEndpoint = this.userType === 'service-provider' ? '/service-provider-profile.php' : '/client-profile.php'

        const response = await apiFetch(`/backend/api${apiEndpoint}`, {
          method: 'PUT',
          body: JSON.stringify(profileToSend)
        })

        if (response.ok) {
          const data = await response.json()

          // Update local profile with server response
          if (data.profile) {
            this.localProfile = { ...data.profile }
            this.calculateProfileCompleteness()
          }

          alert(data.message || 'Profile updated successfully!')
          this.returnToDashboard()
        } else {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to update profile')
        }
      } catch (error) {
        console.error('Error updating profile:', error)
        alert('Failed to update profile: ' + error.message)
      } finally {
        this.updatingProfile = false
      }
    },

    cancelEditing() {
      if (confirm('Are you sure you want to cancel editing your profile? Any unsaved changes will be lost.')) {
        this.returnToDashboard()
      }
    },

    async returnToDashboard() {
      const routeQuery = {
        scroll: this.scrollPosition || '0'
      }

      // Navigate back to originating dashboard
      await this.$router.push({
        path: this.userType === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
        query: routeQuery
      })
    }
  }
}
</script>

<style scoped>
.edit-profile-page {
  /* Full-screen responsive layout */
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
  display: block;
}

.form-input {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
  background: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.form-input[readonly] {
  background: #f8f9fa;
  color: #4b5563;
  cursor: default;
}

.btn {
  padding: 12px 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
  border-color: #2563eb;
}

.btn-secondary {
  background: white;
  color: #6b7280;
  border-color: #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.profile-completeness-indicator {
  display: flex;
  align-items: center;
  gap: 3;
  color: #374151;
  font-size: 14px;
}

/* Material Icons */
.material-icon {
  font-family: 'Material Symbols Outlined', sans-serif;
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
}

.material-icon-sm {
  font-size: 18px;
}

@media (max-width: 768px) {
  .max-w-4xl {
    max-width: 100%;
  }

  .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .grid-cols-1.lg\\:grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>
