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
        <!-- Conditional Layout Based on Job Status -->
        <template v-if="job.job_status === 'Reported'">
          <!-- Job Origin Area (Read-Only) -->
          <div class="job-origin-area">
            <div class="origin-header">
              <span class="material-icon user-icon">person</span>
              <div class="origin-info">
                <div class="origin-text">Reported by {{ getReportedByFullName() }} on {{ formatDate(job.created_at) }}</div>
                <div class="origin-status">Status: {{ job.job_status }}</div>
              </div>
            </div>
          </div>

          <!-- Job Details Section -->
          <div class="job-section job-details-section">
            <div class="section-header">
              <h3 class="section-title">
                <span class="material-icon section-icon">edit_note</span>
                Job Details
              </h3>
            </div>

            <div class="section-content">
              <div class="form-grid">
                <div class="form-group">
                  <label for="item-identifier" class="form-label">
                    <span class="material-icon field-icon">build</span>
                    Item Identifier *
                  </label>
                  <input
                    id="item-identifier"
                    type="text"
                    v-model="editableJob.item_identifier"
                    class="form-input"
                    maxlength="100"
                    placeholder="Enter item identifier..."
                    required
                  />
                </div>

                <div class="form-group" v-if="userRole === 2">
                  <label for="location-select" class="form-label">
                    <span class="material-icon field-icon">location_on</span>
                    Location *
                  </label>
                  <select
                    id="location-select"
                    v-model="selectedLocationId"
                    class="form-input"
                    :disabled="loadingLocations"
                  >
                    <option value="">{{ loadingLocations ? 'Loading...' : '-- Select Location --' }}</option>
                    <option v-for="location in availableLocations" :key="location.id" :value="location.id">
                      {{ location.name }}
                      <span v-if="location.address" class="location-address">– {{ location.address }}</span>
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="contact-person" class="form-label">
                    <span class="material-icon field-icon">contact_mail</span>
                    Contact Person
                  </label>
                  <input
                    id="contact-person"
                    type="text"
                    v-model="editableJob.contact_person"
                    class="form-input"
                    maxlength="100"
                    placeholder="Contact person for this job..."
                  />
                </div>

                <div class="form-group full-width">
                  <label for="fault-description" class="form-label">
                    <span class="material-icon field-icon">description</span>
                    Fault Description *
                  </label>
                  <textarea
                    id="fault-description"
                    v-model="editableJob.fault_description"
                    class="form-textarea"
                    rows="4"
                    maxlength="1000"
                    placeholder="Describe the fault or issue in detail..."
                    required
                  ></textarea>
                </div>
              </div>

              <!-- Image Upload Area -->
              <div class="image-upload-area">
                <ImageUpload
                  ref="imageUpload"
                  :max-images="10"
                  :max-file-size="10 * 1024 * 1024"
                  :existing-images="existingImages"
                  @images-changed="handleImagesChanged"
                />
              </div>

              <!-- Section Actions -->
              <div class="section-actions">
                <button @click="saveAndContinue(jobDetailsSection)" :disabled="saving" class="btn-filled btn-save-continue">
                  <span class="material-icon icon-left">navigate_next</span>
                  Save & Continue
                </button>
                <button @click="saveAndClose(jobDetailsSection)" :disabled="saving" class="btn-outlined btn-save-close">
                  <span class="material-icon icon-left">check_circle</span>
                  Save & Close
                </button>
              </div>
            </div>
          </div>

          <!-- Job Assignment Section -->
          <div class="job-section job-assignment-section">
            <div class="section-header">
              <h3 class="section-title">
                <span class="material-icon section-icon">assignment</span>
                Job Assignment
              </h3>
            </div>

            <div class="section-content">
              <!-- Service Provider Selection -->
              <div class="form-group">
                <label for="provider-select" class="form-label">
                  <span class="material-icon field-icon">business</span>
                  Select Service Provider *
                </label>
                <select v-model="selectedProviderId" class="form-input">
                  <option value="">-- Choose a provider --</option>
                  <option v-for="provider in availableProviders" :key="provider.service_provider_id" :value="provider.service_provider_id">
                    {{ provider.name }}
                    <span v-if="provider.participant_type === 'XS'" class="provider-type external-provider">(External)</span>
                    <span v-else class="provider-type platform-provider">(Platform)</span>
                  </option>
                </select>
                <p class="form-help">
                  <strong>External providers</strong> use their own systems for service delivery.
                  <strong>Platform providers</strong> work within our integrated system.
                </p>
              </div>

              <!-- State Transitions -->
              <div class="transitions" v-if="selectedProviderId">
                <div class="transition-header">
                  <h4 class="transition-title">Next Steps</h4>
                  <p class="transition-description">Choose how to proceed with this service request</p>
                </div>

                <div class="radio-options-container">
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
                      <div class="option-header">
                        <strong>Request Service</strong>
                        <span class="material-icon option-icon">assignment</span>
                      </div>
                      <span class="option-desc">Assign provider immediately for work delivery</span>
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
                      <div class="option-header">
                        <strong>Request Quote</strong>
                        <span class="material-icon option-icon">request_quote</span>
                      </div>
                      <span class="option-desc">Get pricing before proceeding</span>
                    </label>
                  </div>
                </div>

                <!-- Action Forms based on selected transition -->
                <div v-if="selectedStateTransition === 'Quote Requested'" class="transition-form">
                  <div class="form-group">
                    <label for="quote-by-date" class="form-label">
                      <span class="material-icon field-icon">schedule</span>
                      Quote Due Date *
                    </label>
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
                      Provider must respond by {{ formatDate(quoteByDate) }} ({{ calculateDaysBetween(new Date().toISOString().split('T')[0], quoteByDate) }} days)
                    </p>
                  </div>

                  <div class="form-group">
                    <label for="quote-note" class="form-label">
                      <span class="material-icon field-icon">note_add</span>
                      Additional Instructions
                    </label>
                    <textarea
                      id="quote-note"
                      v-model="stateTransitionNote"
                      class="form-textarea"
                      placeholder="Any specific requirements or information for the quote..."
                      rows="3"
                    ></textarea>
                  </div>
                </div>

                <div v-else-if="selectedStateTransition === 'Assigned'" class="transition-form">
                  <div class="assignment-explanation">
                    <div class="explanation-header">
                      <span class="material-icon explanation-icon">info</span>
                      <strong>Service Request</strong>
                    </div>
                    <p>The selected provider will receive an immediate assignment for work delivery. You can include any specific instructions for the provider.</p>
                  </div>

                  <div class="form-group">
                    <label for="assignment-note" class="form-label">
                      <span class="material-icon field-icon">sticky_note</span>
                      Service Instructions
                    </label>
                    <textarea
                      id="assignment-note"
                      v-model="stateTransitionNote"
                      class="form-textarea"
                      placeholder="Any specific instructions for the service provider..."
                      rows="3"
                    ></textarea>
                  </div>
                </div>
              </div>

              <!-- Section Actions -->
              <div class="section-actions">
                <button @click="saveAndContinue(jobAssignmentSection)" :disabled="saving || !selectedProviderId || !selectedStateTransition" class="btn-assignment-continue">
                  <span class="material-icon icon-left">navigate_next</span>
                  Save & Continue
                </button>
                <button @click="saveAndClose(jobAssignmentSection)" :disabled="saving" class="btn-assignment-close">
                  <span class="material-icon icon-left">check_circle</span>
                  Save & Close
                </button>
              </div>
            </div>
          </div>
        </template>

        <!-- Legacy Layout for Non-Reported Jobs -->
        <template v-else>
          <!-- Job Status Section -->
          <div class="section">
            <h3 class="section-title">Current Status: {{ job.job_status }}</h3>

            <!-- Show a message for Assigned jobs with admin access -->
            <div v-if="job.job_status === 'Assigned' && userRole === 3" class="status-message">
              As a service provider administrator, you can assign technicians to this job and add instructions.
            </div>
          </div>

          <!-- Read-Only Job Details (for non-reported jobs) -->
          <div class="section readonly-job-details">
            <h3 class="section-title">Job Details</h3>

            <div class="info-grid">
              <div class="info-item">
                <label class="info-label">Title:</label>
                <span class="info-value">{{ job.item_identifier }}</span>
              </div>

              <div class="info-item">
                <label class="info-label">Contact Person:</label>
                <span class="info-value">{{ job.contact_person || 'Not specified' }}</span>
              </div>

              <div class="info-item">
                <label class="info-label">Reported by:</label>
                <span class="info-value">{{ job.reporting_user }}</span>
              </div>

              <div class="info-item full-width">
                <label class="info-label">Description:</label>
                <div class="info-description">{{ job.fault_description }}</div>
              </div>
            </div>
          </div>
        </template>

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
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-secondary">Close</button>
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
      selectedProviderId: this.job.assigned_provider_id || '',

      // Locations data for Role 2
      availableLocations: [],
      loadingLocations: false,
      selectedLocationId: '',

      // Scroll position preservation
      originalScrollPosition: null
    }
  },
  watch: {
    // Watch for changes to the job prop to reinitialize data when modal is reused
    job: {
      handler(newJob, oldJob) {
        console.log('EditJobModal: job prop changed, reinitializing data');
        console.log('EditJobModal: new job fault_description:', newJob?.fault_description);
        console.log('EditJobModal: old job fault_description:', oldJob?.fault_description);

        // Completely replace the objects to ensure reactivity
        this.editableJob = { ...newJob };
        this.originalJob = { ...newJob };

        // Reset other job-specific data
        this.selectedProviderId = newJob?.assigned_provider_id || '';
        this.selectedTechnicianId = newJob?.assigned_technician_user_id || '';
        this.technicianNotes = newJob?.technician_notes || '';

        // Force Vue to update bindings using nextTick
        this.$nextTick(() => {
          console.log('EditJobModal: After nextTick, editableJob.fault_description:', this.editableJob.fault_description);
          this.$forceUpdate();
        });

        // Reload images and locations if needed
        if (this.job?.job_status === 'Reported') {
          this.loadExistingImages();
          this.loadLocations();
        }
      },
      immediate: false, // Don't run on initial mount
      deep: false // Avoid deep watching for better performance
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    getReportedByFullName() {
      // Try to get the full name of the user who reported the job
      // Check for various possible fields in the job data
      if (this.job.reporting_user_full_name) {
        return this.job.reporting_user_full_name;
      }
      if (this.job.reporting_user_first_name && this.job.reporting_user_last_name) {
        return `${this.job.reporting_user_first_name} ${this.job.reporting_user_last_name}`;
      }
      if (this.job.reporting_user_name) {
        return this.job.reporting_user_name;
      }
      // Fallback to username if no full name available
      return this.job.reporting_user || 'Unknown User';
    },

    async saveAndContinue(sectionType) {
      this.saving = true;
      try {
        if (sectionType === 'job-details') {
          // Save job details (identifier, description, contact person)
          await this.saveJobDetails();
        } else if (sectionType === 'job-assignment') {
          // Execute the selected state transition
          if (this.selectedStateTransition) {
            await this.executeTransitionAndSaveImages(this.selectedStateTransition);
            return; // executeTransitionAndSaveImages handles closing
          }
        }
        // Scroll to next section
        this.scrollToSection(sectionType === 'job-details' ? 'job-assignment' : null);
      } catch (error) {
        console.error('Error in saveAndContinue:', error);
        alert(error.message || 'Failed to save changes');
      } finally {
        this.saving = false;
      }
    },

    async saveAndClose(sectionType) {
      this.saving = true;
      try {
        if (sectionType === 'job-details') {
          // Save job details (identifier, description, contact person, images)
          await this.saveJobDetails();
          await this.saveImageChanges();
          this.$emit('close');
        } else if (sectionType === 'job-assignment') {
          // Execute the selected state transition
          if (this.selectedStateTransition) {
            await this.executeTransitionAndSaveImages(this.selectedStateTransition);
            return; // executeTransitionAndSaveImages handles closing
          }
        }
        // Default: save images and close
        await this.saveImageChanges();
        this.$emit('close');
      } catch (error) {
        console.error('Error in saveAndClose:', error);
        alert(error.message || 'Failed to save changes');
      } finally {
        this.saving = false;
      }
    },

    async saveJobDetails() {
      // Debug: Log current state of editable and original data
      console.log('DEBUG saveJobDetails():');
      console.log('- editableJob.fault_description:', this.editableJob.fault_description);
      console.log('- originalJob.fault_description:', this.originalJob.fault_description);
      console.log('- Are they equal?', this.editableJob.fault_description === this.originalJob.fault_description);

      // Prepare updated job data
      const updateData = {
        job_id: this.job.id
      };

      // Only include changed fields
      const jobFields = ['item_identifier', 'contact_person', 'fault_description'];
      jobFields.forEach(field => {
        if (this.editableJob[field] !== this.originalJob[field]) {
          updateData[field] = this.editableJob[field] || null;
          console.log('DEBUG: Including changed field', field, ':', this.editableJob[field]);
        } else {
          console.log('DEBUG: Field', field, 'not changed');
        }
      });

      // Add location if for Role 2
      if (this.userRole === 2 && this.selectedLocationId) {
        updateData.client_location_id = this.selectedLocationId;
      }

      console.log('DEBUG: Final updateData:', updateData);

      if (Object.keys(updateData).length <= 1) {
        console.log('DEBUG: No job details to update, returning early');
        return;
      }

      try {
        console.log('DEBUG: Making API call with data:', updateData);
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(updateData)
        });

        if (!response.ok) {
          const errorData = await response.json();
          console.error('DEBUG: API call failed:', errorData);
          throw new Error(errorData.error || 'Failed to update job details');
        }

        const result = await response.json();
        console.log('DEBUG: Job details saved successfully:', result);
        return result;
      } catch (error) {
        console.error('DEBUG: Failed to save job details:', error);
        throw error;
      }
    },

    async loadLocations() {
      if (this.userRole !== 2) return;

      this.loadingLocations = true;
      try {
        const response = await apiFetch('/backend/api/client-locations.php');

        if (response.ok) {
          const data = await response.json();
          this.availableLocations = data.locations || [];

          // Set current location if job has one
          if (this.job.client_location_id) {
            this.selectedLocationId = this.job.client_location_id.toString();
          }
        } else {
          console.error('Failed to load locations');
          this.availableLocations = [];
        }
      } catch (error) {
        console.error('Error loading locations:', error);
        this.availableLocations = [];
      } finally {
        this.loadingLocations = false;
      }
    },

    scrollToSection(sectionId) {
      if (!sectionId) return;

      const element = this.$el.querySelector(`.job-section.${sectionId}-section`);
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },

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
    },

    canEditJobDetails() {
      // Allow editing only during 'Reported' stage for Role 1 (Reporting Employee) and Role 2 (Budget Controller)
      if (this.job.job_status !== 'Reported') {
        return false;
      }

      // Role 2 can edit all reported jobs, Role 1 can only edit their own jobs
      if (this.userRole === 2) {
        return true;
      }

      if (this.userRole === 1) {
        // Role 1 can only edit jobs they reported themselves
        return this.currentUserId === this.job.reported_by_user_id;
      }

      return false;
    },

    jobDetailsSection() {
      return 'job-details';
    },

    jobAssignmentSection() {
      return 'job-assignment';
    }
  },

  async mounted() {
    // Preserve original scroll position
    this.originalScrollPosition = window.pageYOffset;

    this.quoteByDate = this.calculateDefaultQuoteDueDate();

    // Load existing images for Reported jobs
    if (this.job.job_status === 'Reported') {
      await this.loadExistingImages();
    }

    // Load locations for Role 2 users
    if (this.job.job_status === 'Reported') {
      await this.loadLocations();
    }
  },

  beforeUnmount() {
    // Restore scroll position when modal closes
    if (this.originalScrollPosition !== null) {
      window.scrollTo(0, this.originalScrollPosition);
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

/* Radio button styles for state transitions - unified container */
.radio-options-container {
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  background: #f8f9fa;
  padding: 16px;
  margin-bottom: 16px;
}

.radio-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: transparent;
}

.radio-option:hover {
  background: rgba(0, 123, 255, 0.08);
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

/* Job Origin Area */
.job-origin-area {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}

.origin-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.origin-info {
  flex: 1;
}

.origin-text {
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
}

.origin-status {
  font-size: 12px;
  color: #007bff;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.user-icon {
  color: #007bff;
  font-size: 20px;
  margin-top: 2px;
}

/* Job Sections */
.job-section {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 24px;
  overflow: hidden;
}

.section-header {
  background: #007bff;
  color: white;
  padding: 16px 20px;
}

.job-section .section-title {
  margin: 0;
  color: white;
  font-size: 1.2em;
  font-weight: 600;
}

.section-content {
  padding: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

.form-group.full-width {
  grid-column: 1;
}

.section-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #e0e0e0;
}

/* Button Variants */
.btn-filled {
  @apply bg-blue-600 text-white font-medium px-6 py-2.5 rounded-full shadow-lg;
  @apply hover:bg-blue-700 hover:shadow-xl;
  @apply active:bg-blue-800 active:shadow-lg;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2;
  @apply transition-all duration-200 ease-out;
}

.btn-outlined {
  @apply bg-transparent text-blue-600 border-2 border-blue-600 font-medium px-4 py-2 rounded-lg;
  @apply hover:bg-blue-50 hover:shadow-lg;
  @apply active:bg-blue-100;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2;
  @apply transition-all duration-200 ease-out;
}

.btn-filled.btn-save-continue {
  @apply bg-blue-600 hover:bg-blue-700;
}

.btn-outlined.btn-save-close {
  @apply border-gray-400 text-gray-600 hover:bg-gray-50;
}

/* Assignment Section Buttons */
.btn-assignment-continue,
.btn-assignment-close {
  @apply font-medium px-6 py-2.5;
  @apply rounded-lg;
  @apply transition-all duration-200 ease-out;
  @apply focus:outline-none;
}

.btn-assignment-continue {
  @apply bg-blue-600 text-white;
  @apply hover:bg-blue-700;
  @apply border-none;
}

.btn-assignment-close {
  @apply bg-transparent text-gray-600 border-2 border-gray-400;
  @apply hover:bg-gray-50;
}

/* Image Upload Area */
.image-upload-area {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e0e0e0;
}

/* Transition Styles */
.transitions h4 {
  color: #333;
  margin: 0 0 12px 0;
  font-size: 1.1em;
}

.transitions p {
  color: #666;
  margin: 0 0 16px 0;
  font-size: 0.95em;
}

.transition-header {
  margin-bottom: 16px;
}

.transition-title {
  color: #333;
  margin: 0 0 4px 0;
  font-size: 1.1em;
  font-weight: 600;
}

.transition-description {
  color: #666;
  margin: 0;
  font-size: 0.9em;
}

/* Radio Options */
.option-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.option-icon {
  font-size: 18px;
}

/* Assignment Explanation */
.assignment-explanation {
  background: #e3f2fd;
  border: 1px solid #2196f3;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 16px;
}

.explanation-header {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 8px;
}

.explanation-icon {
  color: #2196f3;
  font-size: 18px;
  margin-top: 2px;
}

.assignment-explanation p {
  margin: 0;
  color: #1565c0;
  line-height: 1.4;
}

/* Provider Type Badges */
.provider-type.external-provider {
  color: #d9534f;
  font-weight: 600;
}

.provider-type.platform-provider {
  color: #5cb85c;
  font-weight: 600;
}

/* Location Address */
.location-address {
  color: #666;
  font-style: normal;
  font-weight: normal;
}
</style>
