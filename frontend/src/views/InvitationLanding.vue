<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Loading State -->
    <div v-if="loading" class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading invitation details...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="min-h-screen flex items-center justify-center">
      <div class="max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <span class="material-icon text-red-600 text-3xl">error</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Invalid Invitation</h2>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <button @click="$router.push('/')" class="btn-filled">
          Go to Homepage
        </button>
      </div>
    </div>

    <!-- Invitation Content -->
    <div v-else-if="invitationData" class="min-h-screen">
      <!-- Header -->
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-6 py-4">
          <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
              <span class="material-symbols-rounded text-white text-lg">mail</span>
            </div>
            <div>
              <h1 class="text-lg font-semibold text-gray-900">You've been invited!</h1>
              <p class="text-sm text-gray-600">Join our maintenance management platform</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500">Expires</p>
            <p class="text-sm font-medium text-gray-900">{{ formatDate(invitationData.expires_at) }}</p>
          </div>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="max-w-4xl mx-auto px-6 py-12">
        <!-- Welcome and Value Proposition -->
        <div class="text-center mb-12">
          <div class="w-20 h-20 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-rounded text-white text-3xl">{{ getInvitationIcon() }}</span>
          </div>

          <h2 class="text-3xl font-bold text-gray-900 mb-4">
            {{ getWelcomeTitle() }}
          </h2>

          <p class="text-xl text-gray-600 mb-4 max-w-2xl mx-auto">
            {{ getWelcomeMessage() }}
          </p>

          <!-- Recipient Name Display -->
          <div v-if="invitationData.invitee_first_name || invitationData.invitee_last_name" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-base text-blue-800">
              <span class="font-medium">This invitation is for:</span>
              <span class="ml-2">{{ invitationData.invitee_first_name }} {{ invitationData.invitee_last_name }}</span>
            </p>
          </div>

          <!-- Personal Message from Sender -->
          <div v-if="invitationData.notes" class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <p class="text-base text-yellow-800">
              <span class="material-symbols-rounded mr-2 text-lg">message</span>
              <strong>Personal message from {{ invitationData.inviter_details.name }}:</strong>
            </p>
            <p class="mt-2 text-yellow-700 italic">{{ invitationData.notes }}</p>
          </div>

          <!-- Trust Badge -->
          <div class="inline-flex items-center bg-green-50 border border-green-200 rounded-full px-4 py-2 mb-8">
            <span class="material-symbols-rounded text-green-600 mr-2">verified</span>
            <span class="text-sm font-medium text-green-800">Secure Invitation from Verified Organization</span>
          </div>
        </div>

        <!-- Inviter Details Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 mb-8">
          <h3 class="text-xl font-semibold text-gray-900 mb-6 text-center">Invitation from</h3>

          <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
            <!-- Inviter Profile -->
            <div class="flex-shrink-0 text-center md:text-left">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto md:mx-0 mb-4">
                <span class="material-icon text-blue-600 text-2xl">person</span>
              </div>
              <h4 class="font-semibold text-gray-900">{{ invitationData.inviter_details.name }}</h4>
              <p class="text-sm text-gray-600">{{ invitationData.inviter_details.entity_name }}</p>
              <p class="text-xs text-gray-500 mt-1">
                <span class="material-icon-sm mr-1">business</span>
                {{ getEntityTypeLabel(invitationData.inviter_details.entity_type) }}
              </p>
            </div>

            <!-- Inviter Contact Info -->
            <div class="flex-1 border-t md:border-t-0 md:border-l border-gray-200 pt-6 md:pt-0 md:pl-8">
              <div class="space-y-3">
                <div v-if="invitationData.inviter_details.email" class="flex items-center text-sm text-gray-600">
                  <span class="material-icon-sm mr-3">email</span>
                  {{ invitationData.inviter_details.email }}
                </div>
                <div v-if="invitationData.inviter_details.phone" class="flex items-center text-sm text-gray-600">
                  <span class="material-icon-sm mr-3">phone</span>
                  {{ invitationData.inviter_details.phone }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Value Proposition -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 mb-8">
          <h3 class="text-xl font-semibold text-gray-900 mb-6 text-center">Why join our platform?</h3>

          <div class="grid md:grid-cols-2 gap-8">
            <div v-for="benefit in getValueProposition()" :key="benefit.icon" class="flex items-start space-x-4">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <span class="material-icon text-blue-600">{{ benefit.icon }}</span>
                </div>
              </div>
              <div>
                <h4 class="font-semibold text-gray-900 mb-2">{{ benefit.title }}</h4>
                <p class="text-gray-600 text-sm">{{ benefit.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Social Proof -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-8 text-white text-center mb-8">
          <div class="flex items-center justify-center mb-4">
            <span class="material-icon text-white mr-2">groups</span>
            <span class="text-lg font-semibold">Join hundreds of organizations already benefiting from streamlined maintenance management</span>
          </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center">
          <button @click="proceedToRegistration" class="btn-filled text-lg px-8 py-4 mb-4">
            {{ getCallToActionText() }}
          </button>
          <p class="text-sm text-gray-600 mb-2">
            By proceeding, you'll create your account and {{ getAccountTypeText() }}
          </p>
          <!-- Free Registration Message -->
          <div class="inline-block bg-green-100 border border-green-300 rounded-lg px-4 py-2">
            <span class="material-symbols-rounded text-green-600 mr-2 text-sm">free_cancellation</span>
            <span class="text-sm font-medium text-green-800">100% Free Registration</span>
          </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-12 pt-8 border-t border-gray-200">
          <p class="text-sm text-gray-500">
            This invitation is private and can only be used once.
            <br>
            Questions? Contact our support team.
          </p>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'InvitationLanding',
  data() {
    return {
      loading: true,
      error: null,
      invitationData: null,
      token: null
    }
  },
  async mounted() {
    // Get invitation token from URL
    const urlParams = new URLSearchParams(window.location.search)
    this.token = urlParams.get('token')

    if (!this.token) {
      this.error = 'No invitation token provided'
      this.loading = false
      return
    }

    await this.loadInvitationData()
  },
  methods: {
    async loadInvitationData() {
      try {
        const response = await fetch(`/backend/api/validate-invitation.php?token=${encodeURIComponent(this.token)}`)
        const data = await response.json()

        if (response.ok && data.success) {
          this.invitationData = data.invitation
        } else {
          this.error = data.error || 'Invalid invitation'
        }
      } catch (error) {
        console.error('Failed to load invitation data:', error)
        this.error = 'Failed to load invitation details. Please try again.'
      } finally {
        this.loading = false
      }
    },

    getInvitationIcon() {
      // Client invitation (invited by service provider)
      if (this.invitationData.invitation_type === 'client') {
        return 'engineering'
      }
      // Service provider invitation (invited by client)
      return 'business'
    },

    getWelcomeTitle() {
      if (this.invitationData.invitation_type === 'client') {
        return `Join ${this.invitationData.inviter_details.entity_name} as a Partner`
      }
      return `Partner with ${this.invitationData.inviter_details.entity_name}`
    },

    getWelcomeMessage() {
      if (this.invitationData.invitation_type === 'client') {
        return `${this.invitationData.inviter_details.name} from ${this.invitationData.inviter_details.entity_name} has invited you to streamline your equipment maintenance process.`
      }
      return `${this.invitationData.inviter_details.name} from ${this.invitationData.inviter_details.entity_name} has invited you to join their trusted network of service providers.`
    },

    getValueProposition() {
      const benefits = []

      if (this.invitationData.invitation_type === 'client') {
        // Benefits for clients (invited by service providers)
        benefits.push(
          { icon: 'report_problem', title: 'Easy Fault Reporting', description: 'Report equipment issues quickly and track resolution in real-time' },
          { icon: 'engineering', title: 'Expert Service Providers', description: 'Access to qualified technicians and repair services' },
          { icon: 'timeline', title: 'Progress Tracking', description: 'Monitor repair status and get notified of updates' },
          { icon: 'security', title: 'Quality Assurance', description: 'Verified service providers and work guarantees' }
        )
      } else {
        // Benefits for service providers (invited by clients)
        benefits.push(
          { icon: 'notifications', title: 'Instant Service Requests', description: 'Receive immediate notifications for maintenance jobs' },
          { icon: 'people', title: 'Client Relationships', description: 'Build and maintain relationships with client organizations' },
          { icon: 'assignment', title: 'Job Management', description: 'Manage technicians, jobs, and service quotes efficiently' },
          { icon: 'analytics', title: 'Performance Insights', description: 'Track your service performance and client satisfaction' }
        )
      }

      return benefits
    },

    getEntityTypeLabel(entityType) {
      return entityType === 'client' ? 'Asset Owner' : 'Service Provider'
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getCallToActionText() {
      return `Create Your ${this.invitationData.invitation_type === 'client' ? 'Client' : 'Service Provider'} Account`
    },

    getAccountTypeText() {
      if (this.invitationData.invitation_type === 'client') {
        return 'connect with service providers for your maintenance needs'
      }
      return 'start receiving service requests from clients'
    },

    proceedToRegistration() {
      // Navigate to unified registration with token and pre-selected role
      this.$router.push({
        path: '/register',
        query: {
          token: this.token,
          role: this.invitationData.invitation_type
        }
      })
    }
  }
}
</script>

<style scoped>
/* Load Material Symbols font if not already loaded */
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap');

.material-icon {
  font-family: 'Material Symbols Rounded';
}

.material-icon-sm {
  font-family: 'Material Symbols Rounded';
  font-size: 18px;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .material-icon {
    font-size: 24px;
  }

  .material-icon-sm {
    font-size: 16px;
  }
}
</style>
