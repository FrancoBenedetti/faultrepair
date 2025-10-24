<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Add External Service Provider</h2>
        <button class="modal-close" @click="$emit('close')">
          <span class="material-icon">close</span>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="submitForm" class="xs-provider-form">
          <div class="form-section">
            <h3 class="section-title">Provider Information</h3>

            <div class="form-row">
              <div class="form-group">
                <label for="provider-name" class="form-label">Provider Name *</label>
                <input
                  id="provider-name"
                  type="text"
                  v-model="form.name"
                  class="form-input"
                  placeholder="e.g. ABC Maintenance Services"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="provider-address" class="form-label">Address</label>
                <input
                  id="provider-address"
                  type="text"
                  v-model="form.address"
                  class="form-input"
                  placeholder="Street address, city, postal code"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="provider-website" class="form-label">Website</label>
                <input
                  id="provider-website"
                  type="url"
                  v-model="form.website"
                  class="form-input"
                  placeholder="https://www.example.com"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="provider-description" class="form-label">Description</label>
                <textarea
                  id="provider-description"
                  v-model="form.description"
                  class="form-textarea"
                  placeholder="Brief description of the service provider"
                  rows="3"
                ></textarea>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="section-title">Contact Person</h3>

            <div class="form-row">
              <div class="form-group">
                <label for="contact-name" class="form-label">Contact Name *</label>
                <input
                  id="contact-name"
                  type="text"
                  v-model="form.manager_name"
                  class="form-input"
                  placeholder="Full name of contact person"
                  required
                />
              </div>

              <div class="form-group">
                <label for="contact-email" class="form-label">Contact Email *</label>
                <input
                  id="contact-email"
                  type="email"
                  v-model="form.manager_email"
                  class="form-input"
                  placeholder="contact@company.com"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="contact-phone" class="form-label">Contact Phone</label>
                <input
                  id="contact-phone"
                  type="tel"
                  v-model="form.manager_phone"
                  class="form-input"
                  placeholder="+27 12 345 6789"
                />
              </div>

              <div class="form-group">
                <label for="logo-url" class="form-label">Logo URL</label>
                <input
                  id="logo-url"
                  type="url"
                  v-model="form.logo_url"
                  class="form-input"
                  placeholder="https://example.com/logo.png"
                />
              </div>
            </div>
          </div>

          <div class="info-section">
            <div class="info-banner">
              <div class="info-icon">ℹ️</div>
              <div class="info-content">
                <h4>What happens when you add an external provider?</h4>
                <ul>
                  <li>The provider will be automatically approved for your organization</li>
                  <li>You can assign jobs to this provider manually</li>
                  <li>You will manage all job states as a proxy for this external provider</li>
                  <li>No notifications are sent - communication is handled externally</li>
                  <li>You can edit or remove this provider at any time</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="submitting" class="btn-primary">
              {{ submitting ? 'Adding Provider...' : 'Add External Provider' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'AddXSProviderModal',
  emits: ['close', 'provider-added'],
  data() {
    return {
      submitting: false,
      form: {
        name: '',
        address: '',
        website: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        logo_url: ''
      }
    }
  },
  methods: {
    async submitForm() {
      // Validate required fields
      if (!this.form.name.trim() || !this.form.manager_name.trim() || !this.form.manager_email.trim()) {
        alert('Please fill in all required fields marked with *')
        return
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(this.form.manager_email)) {
        alert('Please enter a valid email address')
        return
      }

      this.submitting = true

      try {
        const response = await apiFetch('/backend/api/client-xs-providers.php', {
          method: 'POST',
          body: JSON.stringify({
            name: this.form.name.trim(),
            address: this.form.address ? this.form.address.trim() : null,
            website: this.form.website ? this.form.website.trim() : null,
            description: this.form.description ? this.form.description.trim() : null,
            manager_name: this.form.manager_name.trim(),
            manager_email: this.form.manager_email.trim(),
            manager_phone: this.form.manager_phone ? this.form.manager_phone.trim() : null,
            logo_url: this.form.logo_url ? this.form.logo_url.trim() : null
          })
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to add external provider')
        }

        const result = await response.json()
        alert('External service provider added successfully!')

        // Reset form
        this.resetForm()

        // Emit success event
        this.$emit('provider-added', result)

        // Close modal
        this.$emit('close')

      } catch (error) {
        console.error('Error adding XS provider:', error)
        alert(error.message || 'Failed to add external provider. Please try again.')
      } finally {
        this.submitting = false
      }
    },

    resetForm() {
      this.form = {
        name: '',
        address: '',
        website: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        logo_url: ''
      }
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
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
  color: #333;
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

.xs-provider-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 1.2em;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #007bff;
  padding-bottom: 8px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 6px;
}

.form-input,
.form-textarea {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.info-section {
  margin: 20px 0;
}

.info-banner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 16px;
  background: #e3f2fd;
  border: 1px solid #2196f3;
  border-radius: 8px;
}

.info-icon {
  font-size: 20px;
  color: #1976d2;
  flex-shrink: 0;
}

.info-content h4 {
  margin: 0 0 8px 0;
  color: #1976d2;
  font-size: 16px;
  font-weight: 600;
}

.info-content ul {
  margin: 0;
  padding-left: 20px;
  color: #333;
}

.info-content li {
  margin-bottom: 4px;
  line-height: 1.4;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-secondary {
  padding: 10px 20px;
  border: 1px solid #6c757d;
  background: white;
  color: #6c757d;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #6c757d;
  color: white;
}

.btn-primary {
  padding: 10px 20px;
  border: none;
  background: #007bff;
  color: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .modal-content {
    width: 95%;
    margin: 10px;
  }

  .modal-header,
  .modal-body {
    padding: 16px;
  }

  .form-section {
    padding: 16px;
  }
}
</style>
