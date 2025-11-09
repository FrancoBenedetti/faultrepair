<template>
  <div class="add-provider-page min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
          <!-- Breadcrumb Navigation -->
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <router-link to="/client-dashboard" class="text-blue-600 hover:text-blue-800">
              Client Dashboard
            </router-link>
            <span class="text-gray-400">></span>
            <span class="text-gray-900 font-medium">
              Add External Provider
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button @click="cancel" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50" :disabled="saving">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
      <!-- Form -->
      <form @submit.prevent="saveProvider" class="space-y-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="section-header mb-6">
          <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon section-icon text-blue-600">add_business</span>
            New External Provider Details
          </h3>
          <p class="text-sm text-gray-600 mt-2">
            Add the details of an external service provider. This provider will be available for job assignments but will not have access to the platform.
          </p>
        </div>

        <div class="section-content">
          <div class="form-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Provider Name -->
            <div class="form-group lg:col-span-2">
              <label for="provider-name" class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon field-icon text-gray-500">business</span>
                Provider Name <span class="text-red-500">*</span>
              </label>
              <input id="provider-name" type="text" v-model="provider.name" class="form-input" placeholder="e.g., John's Plumbing" required />
            </div>

            <!-- Address -->
            <div class="form-group lg:col-span-2">
              <label for="provider-address" class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon field-icon text-gray-500">location_on</span>
                Address
              </label>
              <input id="provider-address" type="text" v-model="provider.address" class="form-input" placeholder="e.g., 123 Main Street, Anytown" />
            </div>

            <!-- Website -->
            <div class="form-group">
              <label for="provider-website" class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon field-icon text-gray-500">language</span>
                Website
              </label>
              <input id="provider-website" type="url" v-model="provider.website" class="form-input" placeholder="e.g., https://www.example.com" />
            </div>

            <!-- Description -->
            <div class="form-group lg:col-span-2">
              <label for="provider-description" class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon field-icon text-gray-500">description</span>
                Description
              </label>
              <textarea id="provider-description" v-model="provider.description" class="form-input" rows="3" placeholder="Brief description of the provider's services..."></textarea>
            </div>

            <!-- Manager Details -->
            <div class="lg:col-span-2 border-t border-gray-200 pt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-4">Contact Person</h4>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="form-group">
                  <label for="manager-name" class="form-label">Full Name</label>
                  <input id="manager-name" type="text" v-model="provider.manager_name" class="form-input" placeholder="e.g., Jane Doe" />
                </div>
                <div class="form-group">
                  <label for="manager-email" class="form-label">Email Address</label>
                  <input id="manager-email" type="email" v-model="provider.manager_email" class="form-input" placeholder="e.g., jane.doe@example.com" />
                </div>
                <div class="form-group">
                  <label for="manager-phone" class="form-label">Phone Number</label>
                  <input id="manager-phone" type="tel" v-model="provider.manager_phone" class="form-input" placeholder="e.g., 082 123 4567" />
                </div>
              </div>
            </div>

            <!-- Business Registration Details -->
            <div class="lg:col-span-2 border-t border-gray-200 pt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-4">Business Details (Optional)</h4>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="form-group">
                  <label for="vat-number" class="form-label">VAT Number</label>
                  <input id="vat-number" type="text" v-model="provider.vat_number" class="form-input" />
                </div>
                <div class="form-group">
                  <label for="reg-number" class="form-label">Business Registration No.</label>
                  <input id="reg-number" type="text" v-model="provider.business_registration_number" class="form-input" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="section-actions flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
          <button type="button" @click="cancel" :disabled="saving" class="btn-outlined">
            Cancel
          </button>
          <button type="submit" :disabled="saving" class="btn-filled">
            <span v-if="saving" class="material-icon-sm animate-spin mr-2">sync</span>
            {{ saving ? 'Saving...' : 'Save Provider' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js';

export default {
  name: 'AddProvider',
  data() {
    return {
      provider: {
        name: '',
        address: '',
        website: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        vat_number: '',
        business_registration_number: '',
      },
      saving: false,
      error: null,
    };
  },
  methods: {
    async saveProvider() {
      if (!this.provider.name) {
        alert('Provider Name is a required field.');
        return;
      }

      this.saving = true;
      this.error = null;

      try {
        const response = await apiFetch('/backend/api/client-xs-providers.php', {
          method: 'POST',
          body: JSON.stringify(this.provider),
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to create provider.');
        }

        const result = await response.json();
        alert(result.message || 'External provider added successfully!');
        this.$router.push('/client-dashboard');

      } catch (error) {
        this.error = error.message;
        alert(`Error: ${this.error}`);
      } finally {
        this.saving = false;
      }
    },
    cancel() {
      if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
        this.$router.push('/client-dashboard');
      }
    },
  },
};
</script>

<style scoped>
.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}
.form-label {
  @apply block text-sm font-medium text-gray-700;
}
.btn-filled {
  @apply bg-blue-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50;
}
.btn-outlined {
  @apply bg-transparent text-gray-600 border border-gray-300 font-medium px-4 py-2.5 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors disabled:opacity-50;
}
.material-icon {
  font-size: 20px;
}
</style>