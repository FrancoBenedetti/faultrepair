<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Step Indicator -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-2xl mx-auto px-6 py-4">
        <div class="flex items-center justify-center">
          <div class="flex items-center space-x-4">
            <div class="flex items-center">
              <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                1
              </div>
              <span class="ml-2 text-sm font-medium text-gray-900">Choose Account Type</span>
            </div>
            <span class="text-gray-400">→</span>
            <div class="flex items-center">
              <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium">
                2
              </div>
              <span class="ml-2 text-sm text-gray-500">Create Account</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Selection -->
    <div class="max-w-4xl mx-auto px-6 py-12" v-if="step === 1">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Create Your Account</h1>
        <p class="text-xl text-gray-700">Choose the account type that best describes your organization</p>
      </div>

      <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        <!-- Client Registration Option -->
        <div
          class="registration-card card cursor-pointer transition-all duration-200 hover:shadow-xl"
          :class="{ 'ring-2 ring-blue-500 bg-blue-50': selectedRole === 'client' }"
          @click="selectRole('client')"
        >
          <div class="p-8 text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span class="material-icon text-blue-600 text-3xl">business</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Asset Owner</h3>
            <p class="text-gray-700 mb-6">
              For businesses and organizations that own equipment and assets that need repair and maintenance services.
            </p>
            <ul class="text-left space-y-2 mb-8">
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Report equipment faults easily</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Manage approved service providers</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Track repair progress in real-time</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Control access and permissions</span>
              </li>
            </ul>
            <button class="btn-filled w-full">
              Select Asset Owner Account
            </button>
          </div>
        </div>

        <!-- Service Provider Registration Option -->
        <div
          class="registration-card card cursor-pointer transition-all duration-200 hover:shadow-xl"
          :class="{ 'ring-2 ring-green-500 bg-green-50': selectedRole === 'service_provider' }"
          @click="selectRole('service_provider')"
        >
          <div class="p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span class="material-icon text-green-600 text-3xl">engineering</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Service Provider</h3>
            <p class="text-gray-700 mb-6">
              For repair companies and maintenance service providers that work with client organizations.
            </p>
            <ul class="text-left space-y-2 mb-8">
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Receive instant service requests</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Manage technicians and jobs</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Complete jobs and provide quotes</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Build client relationships</span>
              </li>
            </ul>
            <button class="btn-filled w-full" style="background-color: #16a34a; border-color: #16a34a;" onmouseover="this.style.backgroundColor='#15803d'" onmouseout="this.style.backgroundColor='#16a34a'">
              Select Service Provider Account
            </button>
          </div>
        </div>
      </div>

      <!-- Beta Notice -->
      <div class="max-w-2xl mx-auto mt-12">
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
          <div class="flex items-center justify-center gap-2 text-green-800 mb-3">
            <span class="material-icon-sm">info</span>
            <span class="font-semibold">Beta Testing - Free Access</span>
          </div>
          <p class="text-green-700 text-center">
            Registration and use is completely free during our beta testing period.
            Future subscription options will provide advanced features.
            <router-link to="/subscription" class="text-green-800 hover:text-green-900 underline ml-1">
              Learn more about subscriptions
            </router-link>
          </p>
        </div>
      </div>
    </div>

    <!-- Registration Form -->
    <div class="max-w-2xl mx-auto px-6 py-12" v-if="step === 2">
      <div class="text-center mb-8">
        <div class="flex items-center justify-center mb-4">
          <button @click="backToRoleSelection" class="text-blue-600 hover:text-blue-800 p-2">
            <span class="material-icon">arrow_back</span>
          </button>
          <h1 class="text-2xl font-bold text-gray-900 ml-2">
            {{ selectedRole === 'client' ? 'Asset Owner' : 'Service Provider' }} Registration
          </h1>
        </div>
        <p class="text-gray-600">Complete your account details below</p>
      </div>

      <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4">
          <form @submit.prevent="register" class="space-y-6">
          <!-- Common Fields -->
          <!-- Username no longer needed - email serves as unique identifier -->

          <!-- Company Information -->
          <div>
            <label for="companyName" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">business</span>
              {{ selectedRole === 'client' ? 'Company' : 'Service Company' }} Name *
            </label>
            <input
              type="text"
              id="companyName"
              v-model="form.companyName"
              required
              class="form-input"
              :placeholder="`Enter your ${selectedRole === 'client' ? 'company' : 'service company'} name`"
            >
          </div>

          <!-- Personal Information -->
          <div>
            <label for="firstName" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">person</span>
              First Name *
            </label>
            <input
              type="text"
              id="firstName"
              v-model="form.firstName"
              required
              class="form-input"
              placeholder="Enter your first name"
            >
          </div>

          <div>
            <label for="lastName" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">person</span>
              Last Name *
            </label>
            <input
              type="text"
              id="lastName"
              v-model="form.lastName"
              required
              class="form-input"
              placeholder="Enter your last name"
            >
          </div>

          <div>
            <label for="address" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">location_on</span>
              Address
            </label>
            <textarea
              id="address"
              v-model="form.address"
              required
              rows="3"
              class="form-input resize-none"
              :placeholder="`Enter your ${selectedRole === 'client' ? 'company' : 'service company'} address`"
            ></textarea>
          </div>

          <!-- Email is now the unique identifier instead of username -->

          <div>
            <label for="email" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">email</span>
              Email Address
            </label>
            <input
              type="email"
              id="email"
              v-model="form.email"
              required
              class="form-input"
              placeholder="Enter your business email"
            >
          </div>

          <div>
            <label for="password" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">lock</span>
              Password
            </label>
            <input
              type="password"
              id="password"
              v-model="form.password"
              required
              minlength="6"
              class="form-input"
              placeholder="Create a secure password (min 6 characters)"
            >
          </div>

          <!-- Terms Agreement -->
          <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
            <input
              type="checkbox"
              id="terms"
              v-model="form.agreeToTerms"
              required
              class="mt-1"
            >
            <label for="terms" class="text-sm text-gray-700">
              I agree to the
              <router-link to="/terms" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                Terms & Conditions
              </router-link>
              and understand that this service is currently free during beta testing.
            </label>
          </div>

          <div>
            <button
              type="submit"
              class="btn-filled w-full flex items-center justify-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">person_add</span>
              {{ loading ? 'Creating Account...' : 'Create Account' }}
            </button>
          </div>
          </form>
        </div>

        <div class="mt-6 text-center px-6 pb-4">
          <p class="text-sm text-gray-600">
            Already have an account?
            <router-link to="/" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
              Sign in here
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'UnifiedRegistration',
  data() {
    return {
      step: 1,
      selectedRole: null,
      loading: false,
      invitationData: null,
      form: {
        companyName: '',
        firstName: '',
        lastName: '',
        address: '',
        email: '',
        password: '',
        agreeToTerms: false,
        invitationToken: null
      }
    }
  },
  async mounted() {
    // Check for invitation token and role in URL
    const urlParams = new URLSearchParams(window.location.search)
    const token = urlParams.get('token')
    const role = urlParams.get('role')

    if (token) {
      this.form.invitationToken = token
      await this.loadInvitationData(token)

      // Pre-select role if provided
      if (role && (role === 'client' || role === 'service_provider')) {
        this.selectedRole = role
        // Auto-advance to registration form after brief delay
        setTimeout(() => {
          this.goToRegistrationForm()
        }, 500)
      }
    }
  },
  methods: {
    async loadInvitationData(token) {
      try {
        const response = await fetch(`/backend/api/validate-invitation.php?token=${encodeURIComponent(token)}`)
        const data = await response.json()

        if (response.ok && data.success) {
          // Store invitation data for potential display
          this.invitationData = data.invitation
          // Pre-populate form fields if available
          if (this.invitationData.invitee_email && !this.form.email) {
            this.form.email = this.invitationData.invitee_email
          }
          if (this.invitationData.invitee_first_name && !this.form.firstName) {
            this.form.firstName = this.invitationData.invitee_first_name
          }
          if (this.invitationData.invitee_last_name && !this.form.lastName) {
            this.form.lastName = this.invitationData.invitee_last_name
          }
          if (this.invitationData.invitee_phone && !this.form.phone) {
            // Phone field might be added later if needed
          }
        }
      } catch (error) {
        console.error('Failed to load invitation data:', error)
        // Don't show error to user - continue with normal registration
      }
    },
    selectRole(role) {
      this.selectedRole = role
    },
    goToRegistrationForm() {
      this.step = 2
    },
    backToRoleSelection() {
      this.step = 1
      this.selectedRole = null
      this.resetForm()
    },
    resetForm() {
      this.form = {
        companyName: '',
        firstName: '',
        lastName: '',
        address: '',
        email: '',
        password: '',
        agreeToTerms: false,
        invitationToken: this.form.invitationToken
      }
    },
    async register() {
      this.loading = true
      try {
        const apiEndpoint = this.selectedRole === 'client'
          ? '/backend/api/register-client.php'
          : '/backend/api/register-service-provider.php'

        const response = await fetch(apiEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            ...this.form,
            [this.selectedRole === 'client' ? 'clientName' : 'providerName']: this.form.companyName
          })
        })

        const data = await response.json()

        if (response.ok) {
          // Handle auto-approval for client→service provider invitations
          if (this.invitationData && this.invitationData.invitation_type === 'service_provider' &&
              this.invitationData.inviter_details.entity_type === 'client') {
            alert('Registration successful! You have been automatically approved as a service provider for ' +
                  this.invitationData.inviter_details.entity_name + '. Please check your email to verify your account before signing in.')
          } else {
            alert('Registration successful! Please check your email to verify your account before signing in.')
          }
          this.$router.push('/')
        } else {
          alert(data.error || 'Registration failed. Please try again.')
        }
      } catch (error) {
        alert('Registration failed. Please check your connection and try again.')
      } finally {
        this.loading = false
      }
    }
  },
  watch: {
    selectedRole(newRole) {
      if (newRole) {
        setTimeout(() => {
          this.goToRegistrationForm()
        }, 300) // Small delay for visual feedback
      }
    }
  }
}
</script>

<style scoped>
.registration-card {
  border: 2px solid transparent;
}

.registration-card:hover {
  border-color: #e5e7eb;
}

@media (max-width: 768px) {
  .registration-card {
    margin-bottom: 1rem;
  }
}
</style>
