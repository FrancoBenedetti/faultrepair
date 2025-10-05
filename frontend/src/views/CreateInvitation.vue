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

      <div class="card">
        <form @submit.prevent="createInvitation" class="space-y-6">
          <!-- Invitation Type -->
          <div>
            <label class="form-label">Invitation Type</label>
            <div class="mt-2">
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

          <!-- Invitee Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

          <!-- Contact Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

          <!-- Communication Method -->
          <div>
            <label class="form-label">Communication Method *</label>
            <div class="mt-2 space-y-2">
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="whatsapp"
                  class="mr-2"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-4 h-4 bg-green-500 rounded-full"></span>
                  WhatsApp (Recommended)
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="email"
                  class="mr-2"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-4 h-4 bg-blue-500 rounded-full"></span>
                  Email
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="telegram"
                  class="mr-2"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-4 h-4 bg-blue-600 rounded-full"></span>
                  Telegram
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  v-model="form.communicationMethod"
                  value="sms"
                  class="mr-2"
                  :disabled="loading"
                >
                <span class="text-sm flex items-center gap-2">
                  <span class="w-4 h-4 bg-gray-500 rounded-full"></span>
                  SMS
                </span>
              </label>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label for="notes" class="form-label">Notes (Optional)</label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="3"
              class="form-input resize-none"
              placeholder="Add any additional notes or context"
              :disabled="loading"
            ></textarea>
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
