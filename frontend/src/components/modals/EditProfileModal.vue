<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
          <span class="material-icon text-blue-600">business</span>
          Edit Organization Profile
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-8 overflow-y-auto max-h-[calc(90vh-140px)]">
        <!-- Basic Information -->
        <div class="bg-gray-50 rounded-lg p-6 space-y-6">
          <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2">
            <span class="material-icon-sm text-blue-600">business_center</span>
            Basic Information
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="business-name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">store</span>
                Business Name *
              </label>
              <input
                type="text"
                id="business-name"
                v-model="localProfile.name"
                required
                class="form-input"
                placeholder="Your business name"
              >
            </div>
            <div class="space-y-2">
              <label for="business-website" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">link</span>
                Website
              </label>
              <input
                type="url"
                id="business-website"
                v-model="localProfile.website"
                class="form-input"
                placeholder="https://yourbusiness.com"
              >
            </div>
          </div>

          <div class="space-y-2">
            <label for="business-address" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">location_on</span>
              Business Address
            </label>
            <textarea
              id="business-address"
              v-model="localProfile.address"
              rows="3"
              class="form-input"
              placeholder="Business address"
            ></textarea>
          </div>

          <div class="space-y-2">
            <label for="business-description" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">description</span>
              Business Description
            </label>
            <textarea
              id="business-description"
              v-model="localProfile.description"
              rows="4"
              class="form-input resize-none"
              placeholder="Brief description of your business and services"
            ></textarea>
          </div>
        </div>

        <!-- Manager Contact -->
        <div class="bg-gray-50 rounded-lg p-6 space-y-6">
          <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2">
            <span class="material-icon-sm text-green-600">contact_phone</span>
            Manager Contact Information
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="manager-name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">person</span>
                Manager Name
              </label>
              <input
                type="text"
                id="manager-name"
                v-model="localProfile.manager_name"
                class="form-input"
                placeholder="Primary contact person"
              >
            </div>
            <div class="space-y-2">
              <label for="manager-email" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">email</span>
                Manager Email
              </label>
              <input
                type="email"
                id="manager-email"
                v-model="localProfile.manager_email"
                class="form-input"
                placeholder="manager@yourbusiness.com"
              >
            </div>
          </div>

          <div class="space-y-2">
            <label for="manager-phone" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">phone</span>
              Manager Phone
            </label>
            <input
              type="tel"
              id="manager-phone"
              v-model="localProfile.manager_phone"
              class="form-input"
              placeholder="+27 12 345 6789"
            >
          </div>
        </div>

        <!-- Business Details -->
        <div class="bg-gray-50 rounded-lg p-6 space-y-6">
          <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2">
            <span class="material-icon-sm text-purple-600">assignment</span>
            Business Registration Details
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="vat-number" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">receipt</span>
                VAT Number
              </label>
              <input
                type="text"
                id="vat-number"
                v-model="localProfile.vat_number"
                class="form-input"
                placeholder="VAT registration number"
              >
            </div>
            <div class="space-y-2">
              <label for="business-registration" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">business</span>
                Business Registration Number
              </label>
              <input
                type="text"
                id="business-registration"
                v-model="localProfile.business_registration_number"
                class="form-input"
                placeholder="Company registration number"
              >
            </div>
          </div>

          <div class="space-y-2">
            <label for="business-status" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">toggle_on</span>
              Status
            </label>
            <input
              type="text"
              id="business-status"
              :value="localProfile.is_active ? 'Active' : 'Inactive'"
              class="form-input"
              readonly
            >
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('close')"
            class="btn-filled flex items-center gap-2"
            :disabled="updating"
          >
            <span v-if="updating" class="material-icon-sm animate-spin">refresh</span>
            <span v-else class="material-icon-sm">close</span>
            {{ updating ? 'Canceling...' : 'Cancel' }}
          </button>
          <button
            type="submit"
            class="btn-filled flex items-center gap-2"
            :disabled="updating"
          >
            <span v-if="updating" class="material-icon-sm animate-spin">refresh</span>
            <span v-else class="material-icon-sm">save</span>
            {{ updating ? 'Updating...' : 'Update Profile' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EditProfileModal',
  props: {
    editingProfile: {
      type: Object,
      required: true
    },
    updatingProfile: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit'],
  data() {
    return {
      localProfile: { ...this.editingProfile }
    }
  },
  watch: {
    editingProfile: {
      handler(newProfile) {
        this.localProfile = { ...newProfile }
      },
      deep: true,
      immediate: true
    }
  },
  methods: {
    handleSubmit() {
      this.$emit('submit', this.localProfile)
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
  max-width: 700px;
  max-height: 90vh;
  overflow: hidden;
  padding: 5px 10px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
  background: #f8f9fa;
  border-radius: 8px 8px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
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
  padding: 4px;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f0f0f0;
  color: #333;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
  display: block;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
  background: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.btn-filled {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  background: #f3f4f6;
  color: #374151;
}

.btn-filled:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-filled:nth-child(2) {
  background: #3b82f6;
  color: white;
}

.btn-filled:nth-child(2):hover:not(:disabled) {
  background: #2563eb;
}

.btn-filled:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
  }

  .modal-header {
    padding: 16px;
  }

  .modal-header h3 {
    font-size: 18px;
  }
}
</style>
