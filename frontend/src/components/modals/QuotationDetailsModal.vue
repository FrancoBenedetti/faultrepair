<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Quotation Details</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <div v-if="quotation" class="quotation-details-content">
        <!-- Quotation Information -->
        <div class="quotation-info-section">
          <div class="info-grid">
            <div class="info-item">
              <label>Quote ID:</label>
              <span>Quote {{ quotation.id }}</span>
            </div>
            <div class="info-item">
              <label>Amount:</label>
              <span class="amount-display">{{ formatCurrency(quotation.quotation_amount) }}</span>
            </div>
            <div class="info-item">
              <label>Valid Until:</label>
              <span :class="getValidityClass(quotation.valid_until)">
                {{ formatDate(quotation.valid_until) }}
              </span>
            </div>
            <div class="info-item">
              <label>Status:</label>
              <StatusBadge :status="quotation.status === 'accepted' ? 'Accepted' : quotation.status === 'rejected' ? 'Rejected' : 'Pending'" />
            </div>
            <div class="info-item">
              <label>Submitted:</label>
              <span>{{ formatDate(quotation.submitted_at) }}</span>
            </div>
            <div class="info-item">
              <label>Provider:</label>
              <span>{{ quotation.provider_name }}</span>
            </div>
            <div v-if="quotation.responded_at" class="info-item">
              <label>Responded:</label>
              <span>{{ formatDate(quotation.responded_at) }}</span>
            </div>
            <div v-if="quotation.response_notes" class="info-item">
              <label>Response Notes:</label>
              <span>{{ quotation.response_notes }}</span>
            </div>
          </div>

          <!-- Associated Job Information -->
          <div class="job-section">
            <h4>Associated Job</h4>
            <div class="job-info">
              <div class="job-title">
                <span class="label">Item:</span> {{ quotation.item_identifier || 'No Item ID' }}
              </div>
              <div class="job-description">
                <span class="label">Description:</span> {{ quotation.fault_description }}
              </div>
              <div class="job-status">
                <span class="label">Status:</span>
                <StatusBadge :status="quotation.job_status" />
              </div>
            </div>
          </div>

          <!-- Quotation Description -->
          <div class="description-section">
            <label>Quotation Description:</label>
            <p class="quotation-description">{{ quotation.quotation_description }}</p>
          </div>

          <!-- Document View -->
          <div v-if="quotation.quotation_document_url" class="document-section">
            <label>Attached Document:</label>
            <button @click="viewDocument" class="btn btn-outlined">
              <span class="material-icon-sm mr-2">visibility</span>
              View Quotation Document
            </button>
          </div>
        </div>

        <!-- Response Form - Clean Radio Button Design -->
        <div class="response-form-section">
          <h4>Respond to Quote</h4>
          <div class="response-form-content">
            <!-- Radio Buttons -->
            <div class="radio-group">
              <label class="radio-option">
                <input
                  type="radio"
                  value="accept"
                  v-model="selectedResponse"
                />
                <span class="radio-text">
                  <span class="material-icon-sm radio-icon">check_circle</span>
                  Accept Quote
                </span>
              </label>

              <label class="radio-option">
                <input
                  type="radio"
                  value="reject"
                  v-model="selectedResponse"
                />
                <span class="radio-text">
                  <span class="material-icon-sm radio-icon">cancel</span>
                  Reject Quote
                </span>
              </label>

              <label class="radio-option">
                <input
                  type="radio"
                  value="request"
                  v-model="selectedResponse"
                />
                <span class="radio-text">
                  <span class="material-icon-sm radio-icon">refresh</span>
                  Request New Quote
                </span>
              </label>
            </div>

            <!-- Comments Textarea -->
            <div class="form-group">
              <label for="response-notes" class="textarea-label">
                Additional Notes (Optional)
              </label>
              <textarea
                id="response-notes"
                v-model="responseNotes"
                placeholder="Add any comments about your decision..."
                rows="4"
                class="response-textarea"
              ></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions flex gap-3 justify-end">
              <button
                @click="$emit('close')"
                class="btn-secondary"
              >
                Cancel
              </button>
              <button
                @click="submitResponse"
                :disabled="!selectedResponse"
                class="btn-primary flex items-center gap-2"
                :class="{ 'opacity-50 cursor-not-allowed': !selectedResponse }"
              >
                <span class="material-icon-sm">send</span>
                Submit Response
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'QuotationDetailsModal',
  components: {
    StatusBadge
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    quotation: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'accept-quote', 'reject-quote', 'request-quote'],
  data() {
    return {
      selectedResponse: null,
      responseNotes: ''
    }
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-ZA', {
        style: 'currency',
        currency: 'ZAR'
      }).format(amount)
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },
    getValidityClass(validUntil) {
      if (!validUntil) return ''
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const validDate = new Date(validUntil)
      validDate.setHours(23, 59, 59, 999)

      if (validDate < today) return 'text-red-600 font-bold'
      const daysDiff = Math.ceil((validDate - today) / (1000 * 60 * 60 * 24))
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

      // Open document in new tab/window to view
      const url = `/backend/api/upload-quote-document.php?path=${encodeURIComponent(this.quotation.quotation_document_url)}&token=${encodeURIComponent(token)}`
      window.open(url, '_blank')
    },

    submitResponse() {
      if (!this.selectedResponse) return

      const actionData = {
        quotation: this.quotation,
        notes: this.responseNotes.trim() || null
      }

      if (this.selectedResponse === 'accept') {
        this.$emit('accept-quote', actionData)
      } else if (this.selectedResponse === 'reject') {
        this.$emit('reject-quote', actionData)
      } else if (this.selectedResponse === 'request') {
        this.$emit('request-quote', actionData)
      }

      // Reset form
      this.selectedResponse = null
      this.responseNotes = ''
    }
  }
}
</script>

<style scoped>
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
  z-index: 10000;
  user-select: none;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 800px;
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

.quotation-details-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

/* Quotation Information */
.quotation-info-section {
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

.amount-display {
  font-size: 16px;
  color: #007bff;
  font-weight: bold;
}

/* Job Information */
.job-section {
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 6px;
}

.job-section h4 {
  margin: 0 0 10px 0;
  color: #333;
  font-size: 16px;
}

.job-info {
  display: grid;
  gap: 10px;
}

.job-title, .job-description, .job-status {
  display: flex;
  align-items: center;
  gap: 10px;
}

.job-description {
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
}

.label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  min-width: 80px;
}

.job-description .label {
  margin-bottom: 2px;
}

/* Description Section */
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

.quotation-description {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
  white-space: pre-wrap;
}

/* Document Section */
.document-section {
  margin-bottom: 20px;
}

.document-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.btn {
  display: inline-flex;
  align-items: center;
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
  font-size: 14px;
  color: #666;
}

.btn:hover {
  background: #f0f0f0;
}

.btn-outlined:hover {
  background: #f8f9fa;
  border-color: #ccc;
}

/* Response Form Styles */
.response-form-section {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #e0e0e0;
}

.response-form-section h4 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 18px;
}

.response-form-content {
  max-width: 600px;
}

/* Radio Button Group */
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 25px;
}

.radio-option {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 15px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  transition: all 0.2s;
  background: #f8f9fa;
}

.radio-option:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.radio-option input[type="radio"] {
  display: none;
}

.radio-option input[type="radio"]:checked + .radio-text {
  color: #007bff;
  font-weight: 600;
}

.radio-option input[type="radio"]:checked ~ .radio-text .radio-icon {
  color: #007bff;
}

.radio-text {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;
  color: #333;
  transition: color 0.2s;
}

.radio-icon {
  font-size: 20px !important;
  color: #666;
  transition: color 0.2s;
}

/* Textarea Styles */
.form-group {
  margin-bottom: 25px;
}

.textarea-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.response-textarea {
  width: 100%;
  padding: 12px;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1.4;
  resize: vertical;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.response-textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

/* Modal Footer */
.modal-footer {
  padding: 20px;
  border-top: 1px solid #e0e0e0;
  background: #f8f9fa;
}

.quote-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.action-buttons, .primary-actions {
  display: flex;
  gap: 12px;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-primary {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-danger {
  background: #dc3545;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn-danger:hover {
  background: #c82333;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .quotation-details-content {
    padding: 15px;
  }
}
</style>
