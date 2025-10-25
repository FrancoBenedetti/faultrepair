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

          <!-- Show a message for Assigned jobs with admin access -->
          <div v-if="job.job_status === 'Assigned' && userRole === 3" class="status-message">
            As a service provider administrator, you can assign technicians to this job and add instructions.
          </div>

          <!-- Service Provider Selection (for Reported jobs) -->
          <div v-if="job.job_status === 'Reported'" class="section">
            <h4 class="section-subtitle">Service Provider Selection</h4>

            <div class="form-group">
              <label class="form-label">Select Service Provider *</label>
              <select v-model="selectedProviderId" class="form-input">
                <option value="">-- Choose a provider --</option>
                <option v-for="provider in availableProviders" :key="provider.service_provider_id" :value="provider.service_provider_id">
                  {{ provider.name }}
                  <span v-if="provider.participant_type === 'XS'" class="xs-indicator">(External)</span>
                  <span v-else class="platform-indicator">(Platform)</span>
                </option>
              </select>
              <p class="form-help">
                Required for Request Service or Request Quote options.
                External providers use their own systems.
              </p>
            </div>
          </div>

          <!-- State Transitions -->
          <div class="transitions">
            <!-- Reported Job Transitions (radio button style) -->
            <div v-if="job.job_status === 'Reported'" class="transition-group">
              <div class="radio-options">
                <div class="radio-option">
                  <input
                    id="request-service"
                    type="radio"
                    value="Assigned"
                    v-model="selectedStateTransition"
                    @change="handleTransitionChange"
                    class="radio-input"
                  />
                  <label for="request-service" class="radio-label">
                    <strong>Request a Service</strong>
                    <span class="option-desc">Assign to provider for immediate work delivery</span>
                  </label>
                </div>

                <div class="radio-option">
                  <input
                    id="request-quote"
                    type="radio"
                    value="Quote Requested"
                    v-model="selectedStateTransition"
                    @change="handleTransitionChange"
                    class="radio-input"
                  />
                  <label for="request-quote" class="radio-label">
                    <strong>Request a Quote</strong>
                    <span class="option-desc">Request quotation before proceeding</span>
                  </label>
                </div>

                <div class="radio-option">
                  <input
                    id="reject-job"
                    type="radio"
                    value="Rejected"
                    v-model="selectedStateTransition"
                    @change="handleTransitionChange"
                    class="radio-input"
                  />
                  <label for="reject-job" class="radio-label">
                    <strong>Reject Job</strong>
                    <span class="option-desc">Terminate the request with a reason</span>
                  </label>
                </div>
              </div>

              <!-- Action Forms based on selected transition -->
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
                    @click="executeTransitionAndSaveImages(selectedStateTransition)"
                    :disabled="!selectedProviderId || !quoteByDate || !isQuoteDateValid()"
                    class="btn-primary"
                  >
                    Request Quote & Save Images
                  </button>
                </div>
              </div>

              <div v-else-if="selectedStateTransition === 'Assigned'" class="transition-form">
                <p class="form-explanation">
                  <strong>Service Request:</strong> The selected provider will be assigned this job for immediate work delivery.
                  Please provide any specific instructions if needed.
                </p>

                <div class="form-group">
                  <label for="assignment-note" class="form-label">Instructions (Optional)</label>
                  <textarea
                    id="assignment-note"
                    v-model="stateTransitionNote"
                    class="form-textarea"
                    placeholder="Any specific instructions for the service provider..."
                    rows="3"
                  ></textarea>
                </div>

                <div class="form-actions">
                  <button @click="cancelTransition" class="btn-secondary">Cancel</button>
                  <button
                    @click="executeTransitionAndSaveImages(selectedStateTransition)"
                    :disabled="!selectedProviderId"
                    class="btn-primary"
                  >
                    Request Service & Save Images
                  </button>
                </div>
              </div>

              <div v-else-if="selectedStateTransition === 'Rejected'" class="transition-form">
                <p class="form-explanation">
                  <strong>Job Rejection:</strong> This will terminate the service request.
                  A reason is required.
                </p>

                <div class="form-group">
                  <label for="rejection-note" class="form-label">Reason for Rejection *</label>
                  <textarea
                    id="rejection-note"
                    v-model="stateTransitionNote"
                    class="form-textarea"
                    placeholder="Please provide the reason for rejecting this service request..."
                    rows="3"
                    required
                  ></textarea>
                </div>

                <div class="form-actions">
                  <button @click="cancelTransition" class="btn-secondary">Cancel</button>
                  <button
                    @click="executeTransitionAndSaveImages(selectedStateTransition)"
                    :disabled="!stateTransitionNote || !stateTransitionNote.trim()"
                    class="btn-primary"
                  >
                    Reject Job & Save Images
                  </button>
                </div>
              </div>
            </div>

            <!-- Image Upload Section for Reported Jobs -->
            <div v-if="job.job_status === 'Reported'" class="section">
              <h4 class="section-subtitle">Add/Edit Images</h4>
              <ImageUpload
                ref="imageUpload"
                :max-images="10"
                :max-file-size="10 * 1024 * 1024"
                :existing-images="existingImages"
                @images-changed="handleImagesChanged"
              />
            </div>

            <!-- Other state transitions (non-Reported) can be added here -->
          </div>
        </div>

        <!-- Technician Assignment for Assigned Jobs (Role 3 only) -->
        <div v-if="job.job_status === 'Assigned' && userRole === 3" class="section">
          <h3 class="section-title">Technician Assignment</h3>

          <div class="technician-assignment">
            <div class="form-group">
              <label class="form-label">Assigned Technician</label>
              <select v-model="selectedTechnicianId" class="form-input">
                <option value="">-- Unassigned --</option>
                <option v-for="tech in technicians" :key="tech.id" :value="tech.id">
                  {{ tech.full_name || tech.username }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Technician Notes</label>
              <textarea
                v-model="technicianNotes"
                placeholder="Instructions for the technician..."
                class="form-textarea"
                rows="3"
              ></textarea>
            </div>

            <div class="form-actions">
              <button @click="assignTechnician" :disabled="!selectedTechnicianId || saving" class="btn-primary">
                {{ saving ? 'Assigning...' : 'Assign Technician' }}
              </button>
            </div>
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
            <p v-if="job.assigned_technician"><strong>Current Technician:</strong> {{ job.assigned_technician }}</p>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-secondary">Close</button>
        <!-- Show save button when images have been modified -->
        <button
          v-if="hasImageChanges && !selectedStateTransition"
          @click="saveImageChanges"
          :disabled="saving"
          class="btn-primary"
        >
          {{ saving ? 'Saving...' : 'Save Images Only' }}
        </button>
      </div>
    </div>
  </div>
</template>


<script>
import { apiFetch } from '@/utils/api.js'
import ImageUpload from '@/components/ImageUpload.vue'

export default {
  name: 'EditJobModal',
  components: {
    ImageUpload
  },
  props: {
    job: {
      type: Object,
      required: true
    },
    userRole: {
      type: Number,
      default: 1
    },
    technicians: {
      type: Array,
      default: () => []
    },
    currentUserId: {
      type: Number,
      default: null
    },
    availableProviders: {
      type: Array,
      default: () => []
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
      quoteByDate: this.calculateDefaultQuoteDueDate(),
      // Technician assignment data
      selectedTechnicianId: this.job.assigned_technician_user_id || '',
      technicianNotes: this.job.technician_notes || '',
      // Provider selection for Reported jobs
      selectedProviderId: this.job.assigned_provider_id || ''
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

    handleTransitionChange() {
      // Clear notes when switching between transition types
      this.stateTransitionNote = '';
      this.quoteByDate = this.calculateDefaultQuoteDueDate();
    },

    async executeTransition(targetStatus) {
      // Validate inputs based on transition type
      if (targetStatus === 'Rejected' && (!this.stateTransitionNote || !this.stateTransitionNote.trim())) {
        alert('Please provide a reason for rejecting this job.');
        return;
      }

      if (targetStatus === 'Quote Requested' && !this.isQuoteDateValid()) {
        alert('Please select a valid quote deadline.');
        return;
      }

      if ((targetStatus === 'Assigned' || targetStatus === 'Quote Requested') && !this.selectedProviderId) {
        alert('Please select a service provider before proceeding.');
        return;
      }

      this.saving = true;
      this.error = null;

      try {
        const payload = {
          action: targetStatus,
          note: this.stateTransitionNote,
          assigned_provider_id: targetStatus !== 'Rejected' ? parseInt(this.selectedProviderId) : null
        };

        // Set job status for non-quote transitions
        if (targetStatus !== 'Quote Requested') {
          payload.job_status = targetStatus;
        }

        if (targetStatus === 'Quote Requested') {
          payload.quote_by_date = this.quoteByDate;
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.job.id,
            ...payload
          })
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to update job status');
        }

        const result = await response.json();
        this.$emit('job-updated', result);
        this.$emit('close');
      } catch (error) {
        this.error = error.message;
        alert('Failed to update job: ' + error.message);
      } finally {
        this.saving = false;
      }
    },

    handleImagesChanged(images) {
      this.newImages = images;
    },

    async assignTechnician() {
      if (!this.selectedTechnicianId) {
        alert('Please select a technician');
        return;
      }

      this.saving = true;
      this.error = null;

      try {
        const payload = {
          assigned_technician_user_id: parseInt(this.selectedTechnicianId),
          technician_notes: this.technicianNotes
        };

        const response = await fetch(`/backend/api/service-provider-jobs.php`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify({
            job_id: this.job.id,
            ...payload
          })
        });

        if (!response.ok) {
          throw new Error('Failed to assign technician');
        }

        const result = await response.json();
        alert('Technician assigned successfully!');
        this.$emit('job-updated', result);
        this.$emit('close');
      } catch (error) {
        this.error = error.message;
        alert('Failed to assign technician: ' + error.message);
      } finally {
        this.saving = false;
      }
    },

    async loadExistingImages() {
      try {
        const response = await apiFetch(`/backend/api/job-images.php?job_id=${this.job.id}`);
        if (response.ok) {
          const data = await response.json();
          this.existingImages = data.images || [];
        } else {
          console.error('Failed to load existing images');
          this.existingImages = [];
        }
      } catch (error) {
        console.error('Error loading existing images:', error);
        this.existingImages = [];
      }
    },

    async saveImageChanges() {
      this.saving = true;
      this.error = null;

      try {
        console.log('EditJobModal: Calling uploadImages on ImageUpload component');
        const result = await this.$refs.imageUpload.uploadImages(this.job.id);

        console.log('EditJobModal: uploadImages result:', result);

        if (!result || !result.success) {
          throw new Error(result?.error || 'Failed to upload images');
        }

        // Close modal and refresh, even if no images were uploaded (no error)
        this.$emit('job-updated', {
          success: true,
          message: result.message || 'Images updated successfully'
        });
        this.$emit('close');

      } catch (error) {
        console.error('EditJobModal: Error in saveImageChanges:', error);
        this.error = error.message;
        alert('Failed to save image changes: ' + error.message);
      } finally {
        this.saving = false;
      }
    },

    async executeTransitionAndSaveImages(targetStatus) {
      this.saving = true;
      this.error = null;

      try {
        // First, try to upload/save images if any have been changed
        let imageUploadSuccessful = true;
        let imageUploadMessage = '';

        if (this.hasImageChanges) {
          console.log('EditJobModal: Uploading images first before state transition');
          const imageResult = await this.$refs.imageUpload.uploadImages(this.job.id);

          console.log('EditJobModal: Image upload result:', imageResult);

          if (!imageResult || !imageResult.success) {
            imageUploadSuccessful = false;
            imageUploadMessage = imageResult?.error || 'Failed to upload images';
            console.error('EditJobModal: Image upload failed:', imageUploadMessage);
          } else {
            imageUploadMessage = imageResult.message;
            console.log('EditJobModal: Images uploaded successfully');
          }
        } else {
          console.log('EditJobModal: No images to upload, proceeding with state transition only');
        }

        // Now execute the state transition (regardless of image upload result)
        console.log('EditJobModal: Executing state transition:', targetStatus);

        // Use the existing executeTransition method but modify to handle combined results
        const payload = {
          action: targetStatus,
          note: this.stateTransitionNote,
          assigned_provider_id: targetStatus !== 'Rejected' ? parseInt(this.selectedProviderId) : null
        };

        // Set job status for non-quote transitions
        if (targetStatus !== 'Quote Requested') {
          payload.job_status = targetStatus;
        }

        if (targetStatus === 'Quote Requested') {
          payload.quote_by_date = this.quoteByDate;
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.job.id,
            ...payload
          })
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to update job status');
        }

        const result = await response.json();

        // Prepare success message that includes both image and transition results
        let combinedMessage = `${targetStatus === 'Rejected' ? 'Job rejected' : targetStatus === 'Assigned' ? 'Service requested' : 'Quote requested'} successfully`;

        if (!imageUploadSuccessful) {
          combinedMessage += `, but image upload failed: ${imageUploadMessage}`;
        } else if (this.hasImageChanges) {
          combinedMessage += ` and ${imageUploadMessage || 'images saved'}`;
        }

        this.$emit('job-updated', {
          ...result,
          message: combinedMessage
        });

        // Still close modal even if images failed, but show the combined message
        this.$emit('close');

        if (!imageUploadSuccessful) {
          // Show a delayed alert about image upload failure
          setTimeout(() => {
            alert(`Warning: ${imageUploadMessage}. You can try uploading images again by editing the job.`);
          }, 500);
        }

      } catch (error) {
        console.error('EditJobModal: Error in executeTransitionAndSaveImages:', error);
        this.error = error.message;
        alert('Failed to update job: ' + error.message);
      } finally {
        this.saving = false;
      }
    }
  },

    computed: {
    hasImageChanges() {
      // Check if there are any new (non-existing) images to upload
      const newImages = this.existingImages.filter(img => !img.existing)
      return newImages.length > 0
    },

    availableTransitions() {
      // For now, only allow quote requests from 'Reported' status
      // This can be expanded based on business logic
      const allowedTransitions = {
        'Reported': ['Quote Requested']
      };

      return allowedTransitions[this.job.job_status] || [];
    }
  },

  async mounted() {
    this.quoteByDate = this.calculateDefaultQuoteDueDate();

    // Load existing images for Reported jobs
    if (this.job.job_status === 'Reported') {
      await this.loadExistingImages();
    }
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

.status-message {
  background: #e8f4fd;
  border: 1px solid #1e88e5;
  border-radius: 4px;
  padding: 12px 16px;
  margin-bottom: 16px;
  color: #1565c0;
  font-weight: 500;
}

/* Radio button styles for state transitions */
.radio-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.radio-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.radio-option:hover {
  border-color: #007bff;
  background: #f8f9fa;
}

.radio-option input[type="radio"] {
  margin: 0;
  margin-top: 2px;
}

.radio-label {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  cursor: pointer;
}

.option-desc {
  font-size: 0.9em;
  color: #666;
  font-weight: normal;
}

.form-explanation {
  margin: 0 0 16px 0;
  padding: 12px;
  background: #fff3cd;
  border: 1px solid #ffeeba;
  border-radius: 4px;
  color: #856404;
  font-size: 14px;
  line-height: 1.4;
}

.section-subtitle {
  font-size: 1.1em;
  font-weight: 600;
  margin-bottom: 12px;
  color: #555;
  border-left: 3px solid #007bff;
  padding-left: 12px;
}

.technician-assignment {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
}

/* Provider type indicators in dropdown */
.xs-indicator {
  color: #856404;
  font-style: italic;
  font-weight: normal;
}

.platform-indicator {
  color: #155724;
  font-style: italic;
  font-weight: normal;
}

/* Material Icons CSS Classes */
.material-icon {
  font-family: 'Material Icons', sans-serif;
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

/* Button Style Improvements */
.btn-primary, .btn-secondary {
  font-weight: 500;
  border-radius: 6px;
  transition: all 0.2s ease;
  min-height: 40px;
  font-size: 15px;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.btn-secondary {
  background: #f8f9fa;
  color: #495057;
  border: 1px solid #dee2e6;
}

.btn-secondary:hover {
  background: #e9ecef;
}

/* Form Improvements */
.form-input, .form-textarea {
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus, .form-textarea:focus {
  border-color: #007bff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Section Spacing */
.section {
  margin-bottom: 28px;
}

.section-title {
  font-size: 1.3em;
  font-weight: 600;
  margin-bottom: 16px;
  color: #2c3e50;
}

.section-subtitle {
  font-size: 1.15em;
  font-weight: 600;
  margin-bottom: 14px;
  color: #34495e;
  border-left: 3px solid #007bff;
  padding-left: 14px;
}

/* Modal Container Improvements */
.modal-content {
  max-width: 650px;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

/* Footer Button Layout */
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-top: 1px solid #e9ecef;
}
</style>
