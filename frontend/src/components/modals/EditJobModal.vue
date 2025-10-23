<template>
  <div class="edit-job-modal">
    <div class="modal-overlay" @click="$emit('close')"></div>
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <h2 class="modal-title">Edit Job: {{ job.item_identifier }}</h2>
        <button class="modal-close" @click="$emit('close')">
          <span class="material-icon">close</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Job Status Section -->
        <div class="section">
          <h3 class="section-title">Current Status: {{ job.job_status }}</h3>

          <!-- State Transitions -->
          <div class="transitions">
            <!-- Quote Request Transition -->
            <div v-if="availableTransitions.includes('Quote Requested')" class="transition-group">
              <button
                @click="selectedStateTransition = 'Quote Requested'"
                :class="{ 'active': selectedStateTransition === 'Quote Requested' }"
                class="transition-btn"
              >
                Request Quote
              </button>

              <!-- Quote Request Form -->
              <div v-if="selectedStateTransition === 'Quote Requested'" class="transition-form">
                <div class="form-group">
                  <label for="quote-by-date" class="form-label">Quote By Date *</label>
                  <input
                    id="quote-by-date"
                    type="date"
                    v-model="quoteByDate"
                    :min="getMinQuoteDate()"
                    :max="getMaxQuoteDate()"
                    class="form-input"
                    required
                  />
                  <p class="form-help">
                    Quotes must be provided within {{ calculateDaysBetween(new Date().toISOString().split('T')[0], quoteByDate) }} days
                    (Default: {{ calculateDaysBetween(new Date().toISOString().split('T')[0], calculateDefaultQuoteDueDate()) }} days)
                  </p>
                </div>

                <div class="form-group">
                  <label for="quote-note" class="form-label">Additional Notes</label>
                  <textarea
                    id="quote-note"
                    v-model="stateTransitionNote"
                    class="form-textarea"
                    placeholder="Any specific requirements for the quote..."
                    rows="3"
                  ></textarea>
                </div>

                <div class="form-actions">
                  <button @click="cancelTransition" class="btn-secondary">Cancel</button>
                  <button
                    @click="saveTransition('Quote Requested')"
                    :disabled="!quoteByDate || !isQuoteDateValid()"
                    class="btn-primary"
                  >
                    Request Quote
                  </button>
                </div>
              </div>
            </div>

            <!-- Other state transitions can be added here -->
          </div>
        </div>

        <!-- Job Details (read-only for now) -->
        <div class="section">
          <h3 class="section-title">Job Details</h3>
          <div class="job-details">
            <p><strong>Title:</strong> {{ job.item_identifier }}</p>
            <p><strong>Description:</strong> {{ job.fault_description }}</p>
            <p><strong>Location:</strong> {{ job.location_name }}</p>
            <p><strong>Reported by:</strong> {{ job.reporting_user }}</p>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-secondary">Close</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EditJobModal',
  props: {
    job: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'job-updated'],
  data() {
    return {
      loading: false,
      saving: false,
      error: null,
      editableJob: { ...this.job },
      originalJob: { ...this.job },
      selectedStateTransition: null,
      stateTransitionNote: '',
      approvedProviders: [],
      availableTechnicians: [],
      existingImages: [],
      newImages: [],
      selectedImageForModal: null,
      debugMode: true, // Enable debug panel by default
      lastAction: '',
      // Rejection workflow data
      rejectionNotes: '',
      rejectionImages: [],
      // Quote deadline data
      quoteByDate: this.calculateDefaultQuoteDueDate()
    }
  },

  methods: {
    calculateDefaultQuoteDueDate() {
      const today = new Date();
      const sevenDaysFromNow = new Date(today);
      sevenDaysFromNow.setDate(today.getDate() + 7);
      return sevenDaysFromNow.toISOString().split('T')[0]; // Return YYYY-MM-DD format
    },

    getMinQuoteDate() {
      const today = new Date();
      const tomorrow = new Date(today);
      tomorrow.setDate(today.getDate() + 1);
      return tomorrow.toISOString().split('T')[0]; // Return YYYY-MM-DD format
    },

    getQuoteUrgencyClass(daysRemaining) {
      if (daysRemaining <= 1) return 'text-red-600 font-bold';
      if (daysRemaining <= 3) return 'text-yellow-600';
      return 'text-gray-600';
    },

    calculateDaysRemaining(dueDate) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const due = new Date(dueDate);
      due.setHours(0, 0, 0, 0);
      const diffTime = due.getTime() - today.getTime();
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      return diffDays;
    },

    formatDaysRemaining(dueDate) {
      const days = this.calculateDaysRemaining(dueDate);
      if (days < 0) return `Overdue by ${Math.abs(days)} days`;
      if (days === 0) return 'Due today';
      if (days === 1) return '1 day remaining';
      return `${days} days remaining`;
    },

    getMaxQuoteDate() {
      // Maximum 90 days from now
      const today = new Date();
      const maxDate = new Date(today);
      maxDate.setDate(today.getDate() + 90);
      return maxDate.toISOString().split('T')[0];
    },

    calculateDaysBetween(startDate, endDate) {
      const start = new Date(startDate);
      const end = new Date(endDate);
      const diffTime = end.getTime() - start.getTime();
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    },

    isQuoteDateValid() {
      if (!this.quoteByDate) return false;
      const minDate = this.getMinQuoteDate();
      const maxDate = this.getMaxQuoteDate();
      return this.quoteByDate >= minDate && this.quoteByDate <= maxDate;
    },

    cancelTransition() {
      this.selectedStateTransition = null;
      this.stateTransitionNote = '';
      this.quoteByDate = this.calculateDefaultQuoteDueDate();
    },

    async saveTransition(targetStatus) {
      if (targetStatus === 'Quote Requested' && !this.isQuoteDateValid()) {
        this.error = 'Please select a valid quote deadline';
        return;
      }

      this.saving = true;
      this.error = null;

      try {
        const payload = {
          action: targetStatus,
          note: this.stateTransitionNote,
          provider_id: null // For quote requests, we'll let the system assign
        };

        if (targetStatus === 'Quote Requested') {
          payload.quote_by_date = this.quoteByDate;
        }

        const response = await fetch(`/api/client-jobs/${this.job.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify(payload)
        });

        if (!response.ok) {
          throw new Error('Failed to update job status');
        }

        const result = await response.json();
        this.$emit('job-updated', result);
        this.$emit('close');
      } catch (error) {
        this.error = error.message;
      } finally {
        this.saving = false;
      }
    }
  },

  computed: {
    availableTransitions() {
      // For now, only allow quote requests from 'Reported' status
      // This can be expanded based on business logic
      const allowedTransitions = {
        'Reported': ['Quote Requested']
      };

      return allowedTransitions[this.job.job_status] || [];
    }
  },

  mounted() {
    this.quoteByDate = this.calculateDefaultQuoteDueDate();
  }
}
</script>

<style scoped>
.edit-job-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  z-index: 1001;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-title {
  margin: 0;
  font-size: 1.5em;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
}

.section {
  margin-bottom: 24px;
}

.section-title {
  font-size: 1.2em;
  font-weight: 600;
  margin-bottom: 12px;
  color: #333;
}

.transitions {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.transition-btn {
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 12px 16px;
  cursor: pointer;
  text-align: left;
  font-size: 14px;
}

.transition-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.transition-form {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 16px;
  margin-top: 8px;
  background: #f8f9fa;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
  color: #333;
}

.form-input, .form-textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-textarea {
  resize: vertical;
}

.form-help {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 10px 16px;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-primary {
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 10px 16px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.job-details p {
  margin-bottom: 8px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: flex-end;
}
</style>
