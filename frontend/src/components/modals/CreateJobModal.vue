<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content large-modal" @click.stop>
      <div class="modal-header">
        <h3>New Service Request</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="job-form">
        <div class="form-row">
          <div class="form-group">
            <label for="item-identifier">Item Identifier *</label>
            <div class="input-with-button">
              <input type="text" id="item-identifier" v-model="newJob.item_identifier" required
                     placeholder="e.g., Computer-001, Printer-ABC or scan QR code">
              <QrScanner
                :client-id="getClientId()"
                @qr-detected="$emit('qr-detected', $event)"
                class="qr-scanner-inline"
              />
            </div>
            <small class="form-help">Required: Unique identifier for the item (scan QR or enter manually)</small>
          </div>
          <div class="form-group">
            <label for="location">Location</label>
            <select v-if="locations && locations.length > 0" id="location" v-model="newJob.client_location_id">
              <option value="">Select Location (optional)</option>
              <option v-for="location in locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
            <div v-else class="default-location-display">
              <span class="default-location-text">Default Location (Client Name)</span>
              <input type="hidden" v-model="newJob.client_location_id" value="">
            </div>
            <small class="form-help">
              Optional: Can be auto-filled from QR code. If no custom locations defined, this service request will be associated with your client name.
            </small>
          </div>
        </div>

        <div class="form-group">
          <label for="fault-description">Service Description *</label>
          <textarea id="fault-description" v-model="newJob.fault_description" required
                    rows="4" placeholder="Describe the service request in detail..."></textarea>
        </div>

        <div class="form-group">
          <label for="contact-person">Contact Person</label>
          <input type="text" id="contact-person" v-model="newJob.contact_person"
                 placeholder="Person to contact about this service request">
          <small class="form-help">Optional: Who should the technician contact?</small>
        </div>

        <!-- Image Upload Section -->
        <ImageUpload
          ref="imageUpload"
          :max-images="10"
          :max-file-size="10 * 1024 * 1024"
          @images-changed="$emit('images-changed', $event)"
        />

        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="creatingJob">
            {{ creatingJob ? 'Requesting Service...' : 'Request Service' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import ImageUpload from '@/components/ImageUpload.vue'
import QrScanner from '@/components/QrScanner.vue'

export default {
  name: 'CreateJobModal',
  components: {
    ImageUpload,
    QrScanner
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    newJob: {
      type: Object,
      required: true
    },
    locations: {
      type: Array,
      default: () => []
    },
    creatingJob: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit', 'qr-detected', 'images-changed'],
  methods: {
    handleSubmit() {
      this.$emit('submit')
    },
    getClientId() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.entity_id
        }
      } catch (error) {
        console.error('Failed to get client ID from token:', error)
      }
      return null
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

.large-modal .modal-content {
  max-width: 900px;
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

.job-form {
  padding: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  box-sizing: border-box;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.input-with-button {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.input-with-button input {
  flex: 1;
}

.qr-scanner-inline {
  flex-shrink: 0;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-primary,
.btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

.form-help {
  display: block;
  margin-top: 3px;
  font-size: 0.8em;
  color: #666;
}

.default-location-display {
  padding: 10px;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #666;
  font-style: italic;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .large-modal .modal-content {
    max-width: 95vw;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .input-with-button {
    flex-direction: column;
    gap: 10px;
  }

  .qr-scanner-inline {
    align-self: flex-start;
  }
}
</style>
