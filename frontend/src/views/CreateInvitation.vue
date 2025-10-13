<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create Invitation</h1>
        <p class="mt-2 text-sm text-gray-600">
          Invite someone to join the {{ userEntityType === 'client' ? 'service provider' : 'client' }} platform
        </p>
      </div>

      <div class="card p-8">
        <form @submit.prevent="createInvitation" class="space-y-6">
          <!-- Invitation Type Section -->
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <span class="material-icon-sm text-blue-600">person_add</span>
              Invitation Type
            </h3>
            <div class="space-y-3">
              <div class="flex gap-4">
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="form.invitationType"
                    value="service_provider"
                    class="mr-2"
                    :disabled="loading"
                  >
                  <span class="text-sm">Invite Service Provider</span>
                </label>
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="form.invitationType"
                    value="client"
                    class="mr-2"
                    :disabled="loading"
                  >
                  <span class="text-sm">Invite Client</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Invitee Details Section -->
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <span class="material-icon-sm text-blue-600">person</span>
              Invitee Details
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="inviteeFirstName" class="form-label">First Name *</label>
                <input
                  type="text"
                  id="inviteeFirstName"
                  v-model="form.inviteeFirstName"
                  required
                  class="form-input"
                  placeholder="Enter first name"
                  :disabled="loading"
                >
              </div>
              <div>
                <label for="inviteeLastName" class="form-label">Last Name *</label>
                <input
                  type="text"
                  id="inviteeLastName"
                  v-model="form.inviteeLastName"
                  required
                  class="form-input"
                  placeholder="Enter last name"
                  :disabled="loading"
                >
              </div>
            </div>
          </div>

          <!-- Contact Information Section -->
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <span class="material-icon-sm text-blue-600">contact_phone</span>
              Contact Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="inviteeEmail" class="form-label">
                  Email Address
                  <span v-if="form.communicationMethod === 'email'">*</span>
                </label>
                <input
                  type="email"
                  id="inviteeEmail"
                  v-model="form.inviteeEmail"
                  class="form-input"
                  placeholder="Enter email address"
                  :disabled="loading"
                  :required="form.communicationMethod === 'email'"
                >
              </div>
              <div>
                <label for="inviteePhone" class="form-label">
                  Phone Number
                  <span v-if="['whatsapp', 'telegram', 'sms'].includes(form.communicationMethod)">*</span>
                </label>
                <input
                  type="tel"
                  id="inviteePhone"
                  v-model="form.inviteePhone"
                  class="form-input"
                  placeholder="Enter phone number"
                  :disabled="loading"
                  :required="['whatsapp', 'telegram', 'sms'].includes(form.communicationMethod)"
                >
              </div>
            </div>
          </div>

          <!-- Communication Method Section -->
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <span class="material-icon-sm text-blue-600">message</span>
              Communication Method
            </h3>
            <div class="space-y-3">
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="whatsapp"
                  class="mr-3"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-5 h-5 bg-green-500 rounded-full flex-shrink-0"></span>
                  <span class="font-medium">WhatsApp (Recommended)</span>
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="email"
                  class="mr-3"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-5 h-5 bg-blue-500 rounded-full flex-shrink-0"></span>
                  <span class="font-medium">Email</span>
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="telegram"
                  class="mr-3"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-5 h-5 bg-blue-700 rounded-full flex-shrink-0"></span>
                  <span class="font-medium">Telegram</span>
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="sms"
                  class="mr-3"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-5 h-5 bg-gray-500 rounded-full flex-shrink-0"></span>
                  <span class="font-medium">SMS</span>
                </span>
              </label>
            </div>
          </div>

          <!-- Additional Notes Section -->
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <span class="material-icon-sm text-blue-600">notes</span>
              Additional Notes
            </h3>
            <div>
              <textarea
                id="notes"
                v-model="form.notes"
                rows="4"
                class="form-input resize-none"
                placeholder="Add any additional notes or context (optional)"
                :disabled="loading"
              ></textarea>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              class="btn-filled w-full flex items-center justify-center gap-2"
              :disabled="loading || !canSubmit"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">send</span>
              {{ loading ? 'Creating Invitation...' : 'Create Invitation' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
          <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
              <span class="material-icon text-green-600">check_circle</span>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Invitation Created!</h3>
            <p class="text-sm text-gray-500 mb-4">
              Your invitation has been created successfully. Share the link below with {{ form.inviteeFirstName }}.
            </p>

            <!-- Invitation Link -->
            <div class="bg-gray-50 rounded-lg p-3 mb-4">
              <p class="text-xs text-gray-600 mb-1">Invitation Link:</p>
              <p class="text-sm font-mono break-all">{{ invitationData.invitation_url }}</p>
            </div>

            <!-- WhatsApp Button -->
            <div v-if="invitationData.whatsapp_url" class="mb-4">
              <a
                :href="invitationData.whatsapp_url"
                target="_blank"
                class="btn-filled w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700"
              >
                <span class="w-4 h-4 bg-white rounded-full"></span>
                Share via WhatsApp
              </a>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
              <button
                @click="copyToClipboard"
                class="btn-outline flex-1"
                :disabled="copied"
              >
                {{ copied ? 'Copied!' : 'Copy Link' }}
              </button>
              <button
                @click="closeModal"
                class="btn-filled flex-1"
              >
                Done
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '../utils/api.js'

export default {
  name: 'CreateInvitation',
  data() {
    return {
      loading: false,
      copied: false,
      showSuccessModal: false,
      invitationData: null,
      form: {
        invitationType: 'service_provider',
        inviteeFirstName: '',
        inviteeLastName: '',
        inviteeEmail: '',
        inviteePhone: '',
        communicationMethod: 'whatsapp',
        notes: ''
      }
    }
  },
  computed: {
    userEntityType() {
      try {
        // Get user data from token instead of store
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.entity_type || 'client'
        }
        return 'client'
      } catch (error) {
        return 'client'
      }
    },
    canSubmit() {
      const required = ['inviteeFirstName', 'inviteeLastName', 'communicationMethod']
      const hasRequired = required.every(field => this.form[field])

      if (this.form.communicationMethod === 'email') {
        return hasRequired && this.form.inviteeEmail
      } else if (['whatsapp', 'telegram', 'sms'].includes(this.form.communicationMethod)) {
        return hasRequired && this.form.inviteePhone
      }

      return hasRequired
    }
  },
  async mounted() {
    // Check if user is authenticated
    const token = localStorage.getItem('token')
    if (!token) {
      alert('You must be logged in to create invitations')
      this.$router.push('/')
      return
    }

    // Set default invitation type based on user type
    this.form.invitationType = this.userEntityType === 'client' ? 'service_provider' : 'client'
  },
  methods: {
    async createInvitation() {
      this.loading = true
      try {
        const response = await apiFetch('/backend/api/create-invitation.php', {
          method: 'POST',
          body: JSON.stringify(this.form)
        })
        const data = await response.json()

        if (response.ok) {
          this.invitationData = data.invitation
          this.showSuccessModal = true
        } else {
          throw new Error(data.error || 'Failed to create invitation')
        }
      } catch (error) {
        alert(error.message || 'Failed to create invitation')
      } finally {
        this.loading = false
      }
    },
    copyToClipboard() {
      navigator.clipboard.writeText(this.invitationData.invitation_url)
      this.copied = true
      setTimeout(() => {
        this.copied = false
      }, 2000)
    },
    closeModal() {
      this.showSuccessModal = false
      this.invitationData = null
      this.form = {
        invitationType: 'service_provider',
        inviteeFirstName: '',
        inviteeLastName: '',
        inviteeEmail: '',
        inviteePhone: '',
        communicationMethod: 'whatsapp',
        notes: ''
      }
    }
  }
}
</script>

<style scoped>
/* Mobile responsiveness adjustments */
@media (max-width: 640px) {
  .card {
    padding: 1rem; /* 16px instead of 32px */
  }

  /* Reduce section padding on mobile */
  .card .bg-gray-50 {
    padding: 1rem;
    margin-bottom: 1rem;
  }

  /* Adjust section headers on mobile */
  .card h3 {
    font-size: 1rem; /* text-base */
    margin-bottom: 0.75rem;
  }

  .card h3 .material-icon-sm {
    font-size: 1rem;
  }
}

/* Force single column layout on mobile for responsive grids */
@media (max-width: 768px) {
  .card .grid.grid-cols-1.md\\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Focus states for better accessibility */
.form-input:focus {
  @apply ring-2 ring-blue-500 ring-offset-2 ring-offset-gray-50;
}

/* Hover effects for interactive elements */
.form-input:hover:not(:disabled) {
  @apply border-gray-400;
}

.card:hover {
  @apply shadow-xl;
}
</style>
