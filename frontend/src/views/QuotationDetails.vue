<template>
  <div class="quotation-details-page min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
          <!-- Breadcrumb Navigation -->
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <router-link
              :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
              class="text-blue-600 hover:text-blue-800"
              @click="returnToDashboard"
            >
              {{ from === 'service-provider' ? 'Service Provider Dashboard' : 'Client Dashboard' }}
            </router-link>
            <span class="text-gray-400">></span>
            <span class="text-gray-900 font-medium">
              Quote Details: {{ quotation?.item_identifier || 'Loading...' }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button
              @click="returnToDashboard"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Back to Dashboard
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
          <p class="text-gray-600">Loading quotation details...</p>
        </div>
      </div>

      <!-- Quotation Details Content -->
      <div v-else-if="quotation" class="space-y-8">
        <!-- Quotation Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-900">
              Quotation Details
            </h1>
            <div class="flex items-center gap-2">
              <StatusBadge :status="quotation.status === 'accepted' ? 'Accepted' : quotation.status === 'rejected' ? 'Rejected' : 'Pending'" />
              <div v-if="quotation.status === 'submitted'" class="text-sm text-orange-600 font-medium">
                Action Required
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <span class="font-medium text-gray-700">Quote ID:</span>
              <span class="text-gray-900 ml-2">{{ quotation.id }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Provider:</span>
              <span class="text-gray-900 ml-2">{{ quotation.provider_name }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Amount:</span>
              <span class="text-gray-900 ml-2 font-bold text-lg">{{ formatCurrency(quotation.quotation_amount) }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Submitted:</span>
              <span class="text-gray-900 ml-2">{{ formatDate(quotation.submitted_at) }}</span>
            </div>
            <div v-if="quotation.valid_until">
              <span class="font-medium text-gray-700">Valid Until:</span>
              <span :class="getValidityClass(quotation.valid_until)" class="ml-2">
                {{ formatDate(quotation.valid_until) }}
              </span>
            </div>
            <div v-if="quotation.responded_at">
              <span class="font-medium text-gray-700">Responded:</span>
              <span class="text-gray-900 ml-2">{{ formatDate(quotation.responded_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Associated Job Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
            <span class="material-icon text-blue-600">work</span>
            Associated Job
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <span class="font-medium text-gray-700">Item:</span>
              <span class="text-gray-900 ml-2">{{ quotation.item_identifier || 'No Item ID' }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Location:</span>
              <span class="text-gray-900 ml-2">{{ quotation.location_name }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Status:</span>
              <span class="text-gray-900 ml-2"><StatusBadge :status="quotation.job_status" /></span>
            </div>
            <div v-if="quotation.contact_person">
              <span class="font-medium text-gray-700">Contact:</span>
              <span class="text-gray-900 ml-2">{{ quotation.contact_person }}</span>
            </div>
          </div>

          <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <span class="font-medium text-gray-700">Fault Description:</span>
            <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ quotation.fault_description }}</p>
          </div>
        </div>

        <!-- Quotation Description -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
            <span class="material-icon text-green-600">description</span>
            Quotation Description
          </h3>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-gray-900 whitespace-pre-wrap">{{ quotation.quotation_description }}</p>
          </div>
        </div>

        <!-- Document View -->
        <div v-if="quotation.quotation_document_url" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
            <span class="material-icon text-purple-600">attachment</span>
            Attached Document
          </h3>
          <button @click="viewDocument" class="btn btn-primary flex items-center gap-2">
            <span class="material-icon-sm">visibility</span>
            View Quotation Document
          </button>
        </div>

        <!-- Response Actions (Only for client admin viewing submitted quotes) -->
        <div v-if="quotation.status === 'submitted' && canRespondToQuote"
             class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
            <span class="material-icon text-orange-600">decision</span>
            Respond to Quote
          </h3>

          <div class="space-y-6">
            <!-- Radio Buttons for Response Type -->
            <div class="space-y-4">
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 hover:bg-blue-50">
                <input
                  type="radio"
                  value="accept"
                  v-model="selectedResponse"
                  class="mr-3"
                />
                <div class="flex items-center gap-3">
                  <span class="material-icon text-green-600">check_circle</span>
                  <div>
                    <div class="font-medium text-gray-900">Accept Quote</div>
                    <div class="text-sm text-gray-600">Job will be assigned to the service provider</div>
                  </div>
                </div>
              </label>

              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-red-300 hover:bg-red-50">
                <input
                  type="radio"
                  value="reject"
                  v-model="selectedResponse"
                  class="mr-3"
                />
                <div class="flex items-center gap-3">
                  <span class="material-icon text-red-600">cancel</span>
                  <div>
                    <div class="font-medium text-gray-900">Reject Quote</div>
                    <div class="text-sm text-gray-600">Job status will change to Rejected (terminal state)</div>
                  </div>
                </div>
              </label>

              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-yellow-300 hover:bg-yellow-50">
                <input
                  type="radio"
                  value="request"
                  v-model="selectedResponse"
                  class="mr-3"
                />
                <div class="flex items-center gap-3">
                  <span class="material-icon text-yellow-600">refresh</span>
                  <div>
                    <div class="font-medium text-gray-900">Request New Quote</div>
                    <div class="text-sm text-gray-600">Ask provider to submit a revised quotation</div>
                  </div>
                </div>
              </label>
            </div>

            <!-- Comments Textarea -->
            <div class="space-y-2">
              <label for="response-notes" class="block font-medium text-gray-700">
                Additional Notes (Optional)
              </label>
              <textarea
                id="response-notes"
                v-model="responseNotes"
                placeholder="Add any comments about your decision..."
                rows="4"
                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              ></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
              <router-link
                :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
                class="btn btn-secondary"
              >
                Cancel
              </router-link>
              <button
                @click="submitResponse"
                :disabled="!selectedResponse || submittingResponse"
                class="btn btn-primary flex items-center gap-2"
              >
                <span v-if="submittingResponse" class="material-icon-sm animate-spin">refresh</span>
                <span v-else class="material-icon-sm">send</span>
                Submit Response
              </button>
            </div>
          </div>
        </div>

        <!-- Response Status (if already responded) -->
        <div v-if="quotation.status !== 'submitted'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
            <span class="material-icon text-blue-600">info</span>
            Response Status
          </h3>

          <div class="space-y-3">
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <StatusBadge :status="quotation.status" />
              <span class="text-gray-700">{{ getStatusMessage(quotation.status) }}</span>
            </div>

            <div v-if="quotation.response_notes" class="p-3 bg-blue-50 border-l-4 border-blue-400 rounded-lg">
              <div class="font-medium text-blue-900 mb-1">Response Notes:</div>
              <div class="text-blue-800">{{ quotation.response_notes }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-16">
        <div class="error-icon mx-auto w-16 h-16 flex items-center justify-center bg-red-100 rounded-full mb-4">
          <span class="material-icon text-red-600">error</span>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Error Loading Quotation</h3>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <router-link
          :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
          class="btn btn-primary"
        >
          Return to Dashboard
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'QuotationDetails',
  components: {
    StatusBadge
  },
  props: {
    jobId: {
      type: Number,
      required: true
    },
    quoteId: {
      type: Number,
      required: true
    },
    from: {
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
      quotation: null,
      loading: true,
      error: null,
      selectedResponse: null,
      responseNotes: '',
      submittingResponse: false
    }
  },
  computed: {
    canRespondToQuote() {
      // Only client admins can respond to quotes
      try {
        const token = localStorage.getItem('token')
        if (!token) return false

        const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
        return payload.entity_type === 'client' && payload.role_id >= 2 // Client admin or higher
      } catch (error) {
        return false
      }
    }
  },
  async mounted() {
    await this.loadQuotation()
  },
  methods: {
    async loadQuotation() {
      try {
        this.loading = true
        const response = await apiFetch(`/backend/api/job-quotations.php?quote_id=${this.quoteId}`)

        if (response.ok) {
          const data = await response.json()
          this.quotation = data.quote || null

          if (!this.quotation) {
            throw new Error('Quotation not found')
          }
        } else {
          throw new Error('Failed to load quotation')
        }
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    formatCurrency(amount) {
      if (!amount) return 'R0.00'
      return new Intl.NumberFormat('en-ZA', {
        style: 'currency',
        currency: 'ZAR'
      }).format(amount)
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-ZA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getValidityClass(validUntil) {
      if (!validUntil) return 'text-gray-600'
      const now = new Date()
      const validDate = new Date(validUntil)

      if (validDate < now) return 'text-red-600 font-bold'
      const daysDiff = Math.ceil((validDate - now) / (1000 * 60 * 60 * 24))
      if (daysDiff <= 3) return 'text-yellow-600'
      return 'text-green-600'
    },

    viewDocument() {
      if (!this.quotation?.quotation_document_url) return

      const token = localStorage.getItem('token')
      if (!token) {
        console.warn('No JWT token found for document viewing')
        return
      }

      const url = `/backend/api/upload-quote-document.php?path=${encodeURIComponent(this.quotation.quotation_document_url)}&token=${encodeURIComponent(token)}`
      window.open(url, '_blank')
    },

    async submitResponse() {
      if (!this.selectedResponse) return

      if (!confirm(this.getConfirmationMessage())) {
        return
      }

      this.submittingResponse = true
      try {
        const response = await apiFetch('/backend/api/job-quotations.php', {
          method: 'PUT',
          body: JSON.stringify({
            quote_id: this.quoteId,
            action: this.selectedResponse,
            notes: this.responseNotes.trim() || null
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message || 'Quote response submitted successfully!')

          // Navigate back to dashboard
          this.returnToDashboard()
        } else {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to submit response')
        }
      } catch (error) {
        alert('Failed to submit response: ' + (error.message || error))
      } finally {
        this.submittingResponse = false
      }
    },

    getConfirmationMessage() {
      switch (this.selectedResponse) {
        case 'accept':
          return `Accept this quotation for "${this.quotation.item_identifier}"?\n\nJob will be assigned to ${this.quotation.provider_name} and work can begin immediately.`
        case 'reject':
          return `Reject this quotation for "${this.quotation.item_identifier}"?\n\nThis job will be marked as rejected (terminal state) and cannot be restarted.`
        case 'request':
          return `Request a new quotation for "${this.quotation.item_identifier}"?\n\n${this.quotation.provider_name} will be asked to submit a revised quote.`
        default:
          return 'Are you sure you want to submit this response?'
      }
    },

    getStatusMessage(status) {
      switch (status) {
        case 'accepted': return 'Quotation was accepted and job has been assigned'
        case 'rejected': return 'Quotation was rejected'
        case 'expired': return 'New quotation requested from provider'
        case 'submitted': return 'Quotation is pending response'
        default: return `Status: ${status}`
      }
    },

    returnToDashboard() {
      const routeQuery = {
        scroll: this.scrollPosition || '0'
      }

      this.$router.push({
        path: this.from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
        query: routeQuery
      })
    }
  }
}
</script>

<style scoped>
.quotation-details-page {
  /* Full-screen responsive layout */
}

.form-input:focus {
  outline: none;
}

.btn {
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  font-size: 14px;
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
    @apply max-w-full;
  }

  .max-w-7xl {
    @apply max-w-full;
  }

  .px-6 {
    @apply px-4;
  }
}
</style>
