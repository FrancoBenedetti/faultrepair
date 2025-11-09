<template>
  <div class="provider-details-page min-h-screen bg-gray-50">
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
              {{ provider && provider.name ? provider.name : 'Provider Details' }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button @click="goBack" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
              Back to Dashboard
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16">
      <div class="loading-spinner w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
      <p class="text-gray-600">Loading provider details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-16">
      <div class="error-icon mx-auto w-16 h-16 flex items-center justify-center bg-red-100 rounded-full mb-4">
        <span class="material-icon text-red-600">error</span>
      </div>
      <h3 class="text-xl font-semibold text-gray-900 mb-2">Error Loading Provider</h3>
      <p class="text-gray-600 mb-6">{{ error }}</p>
      <button @click="goBack" class="btn-filled">Return to Dashboard</button>
    </div>

    <div v-else-if="provider" class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
      <!-- EDITABLE FORM for XS Providers -->
      <form v-if="isEditable" @submit.prevent="saveProvider" class="space-y-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="section-header mb-6">
          <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon section-icon text-orange-600">edit</span>
            Edit External Provider
          </h3>
          <p class="text-sm text-gray-600 mt-2">
            You are editing an external provider. Changes made here will be reflected in your provider list.
          </p>
        </div>

        <div class="section-content">
          <div class="form-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Provider Name -->
            <div class="form-group lg:col-span-2">
              <label for="provider-name" class="form-label">Provider Name <span class="text-red-500">*</span></label>
              <input id="provider-name" type="text" v-model="editableProvider.name" class="form-input" required />
            </div>

            <!-- Address -->
            <div class="form-group lg:col-span-2">
              <label for="provider-address" class="form-label">Address</label>
              <input id="provider-address" type="text" v-model="editableProvider.address" class="form-input" />
            </div>

            <!-- Website -->
            <div class="form-group">
              <label for="provider-website" class="form-label">Website</label>
              <input id="provider-website" type="url" v-model="editableProvider.website" class="form-input" />
            </div>

            <!-- Description -->
            <div class="form-group lg:col-span-2">
              <label for="provider-description" class="form-label">Description</label>
              <textarea id="provider-description" v-model="editableProvider.description" class="form-input" rows="3"></textarea>
            </div>

            <!-- Manager Details -->
            <div class="lg:col-span-2 border-t border-gray-200 pt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-4">Contact Person</h4>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="form-group">
                  <label for="manager-name" class="form-label">Full Name</label>
                  <input id="manager-name" type="text" v-model="editableProvider.manager_name" class="form-input" />
                </div>
                <div class="form-group">
                  <label for="manager-email" class="form-label">Email Address</label>
                  <input id="manager-email" type="email" v-model="editableProvider.manager_email" class="form-input" />
                </div>
                <div class="form-group">
                  <label for="manager-phone" class="form-label">Phone Number</label>
                  <input id="manager-phone" type="tel" v-model="editableProvider.manager_phone" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Business Registration Details -->
            <div class="lg:col-span-2 border-t border-gray-200 pt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-4">Business Details (Optional)</h4>
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="form-group">
                  <label for="vat-number" class="form-label">VAT Number</label>
                  <input id="vat-number" type="text" v-model="editableProvider.vat_number" class="form-input" />
                </div>
                <div class="form-group">
                  <label for="reg-number" class="form-label">Business Registration No.</label>
                  <input id="reg-number" type="text" v-model="editableProvider.business_registration_number" class="form-input" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="section-actions flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
          <button type="button" @click="deleteProvider" :disabled="saving" class="btn-danger">
            <span class="material-icon-sm mr-2">delete</span>
            Delete Provider
          </button>
          <div class="flex gap-3">
            <button type="button" @click="goBack" :disabled="saving" class="btn-outlined">
              Cancel
            </button>
            <button type="submit" :disabled="saving" class="btn-filled">
              <span v-if="saving" class="material-icon-sm animate-spin mr-2">sync</span>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </form>

      <!-- READ-ONLY VIEW for S Providers -->
      <div v-else class="space-y-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="section-header mb-6">
          <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon section-icon text-blue-600">business</span>
            {{ provider.name }}
            <span class="provider-type-badge text-xs font-medium px-2 py-1 rounded-full uppercase tracking-wide bg-blue-100 text-blue-800">
              Platform Provider
            </span>
          </h3>
          <p class="text-sm text-gray-600 mt-2">
            This is a platform service provider. Their profile is managed by them and is read-only.
          </p>
        </div>

        <div class="info-grid grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
          <div class="info-item"><strong class="block text-gray-500">Address</strong> {{ provider.address || 'N/A' }}</div>
          <div class="info-item"><strong class="block text-gray-500">Website</strong>
            <a v-if="provider.website" :href="provider.website" target="_blank" class="text-blue-600 hover:underline">{{ provider.website }}</a>
            <span v-else>N/A</span>
          </div>
          <div class="info-item md:col-span-2"><strong class="block text-gray-500">Description</strong> {{ provider.description || 'N/A' }}</div>
        </div>

        <div class="border-t border-gray-200 pt-6">
          <h4 class="text-lg font-medium text-gray-800 mb-4">Contact Person</h4>
          <div class="info-grid grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div class="info-item"><strong class="block text-gray-500">Full Name</strong> {{ provider.manager_name || 'N/A' }}</div>
            <div class="info-item"><strong class="block text-gray-500">Email Address</strong>
              <a v-if="provider.manager_email" :href="'mailto:' + provider.manager_email" class="text-blue-600 hover:underline">{{ provider.manager_email }}</a>
              <span v-else>N/A</span>
            </div>
            <div class="info-item"><strong class="block text-gray-500">Phone Number</strong> {{ provider.manager_phone || 'N/A' }}</div>
          </div>
        </div>

        <div class="border-t border-gray-200 pt-6">
          <h4 class="text-lg font-medium text-gray-800 mb-4">Business Details</h4>
          <div class="info-grid grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div class="info-item"><strong class="block text-gray-500">VAT Number</strong> {{ provider.vat_number || 'N/A' }}</div>
            <div class="info-item"><strong class="block text-gray-500">Business Registration No.</strong> {{ provider.business_registration_number || 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js';

export default {
  name: 'ProviderDetails',
  props: {
    id: {
      type: [String, Number],
      required: true,
    },
  },
  data() {
    return {
      provider: null,
      editableProvider: {},
      loading: true,
      saving: false,
      error: null,
      userRole: null,
    };
  },
  computed: {
    isEditable() {
      return this.provider && this.provider.provider_type === 'XS' && this.userRole === 2;
    },
  },
  async mounted() {
    this.checkUserRole();
    await this.loadProvider();
  },
  methods: {
    checkUserRole() {
      const token = localStorage.getItem('token');
      if (token) {
        const base64Url = token.split('.')[1];
        const payload = JSON.parse(atob(base64Url.replace(/-/g, '+').replace(/_/g, '/')));
        this.userRole = payload.role_id;
      }
    },
    async loadProvider() {
      this.loading = true;
      this.error = null;
      try {
        // Fetch a single provider's details using the enhanced endpoint
        const response = await apiFetch(`/backend/api/client-approved-providers.php?provider_id=${this.id}`);
        const contentType = response.headers.get('content-type');

        if (!response.ok) {
          let errorData = { error: `Server returned an error (Status: ${response.status}). Please check server logs.` }; // Default error
          if (contentType && contentType.includes('application/json')) {
            try {
              errorData = await response.json();
            } catch (e) {
              // Ignore if JSON parsing fails on an error response, use default.
            }
          }
          throw new Error(errorData.error || 'Provider not found or you do not have permission to view it.');
        }

        if (!contentType || !contentType.includes('application/json')) {
          throw new Error('Received non-JSON response from server. Please check server configuration and API endpoint.');
        }

        const data = await response.json();
        this.provider = data.provider;
        this.editableProvider = { ...data.provider }; // Create a copy for editing
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },
    async saveProvider() {
      if (!this.editableProvider.name) {
        alert('Provider Name is a required field.');
        return;
      }
      this.saving = true;
      this.error = null;
      try {
        // Ensure provider_id is in the body, as expected by the backend
        const payload = {
          ...this.editableProvider,
          provider_id: this.id
        };

        const response = await apiFetch('/backend/api/client-xs-providers.php', {
          method: 'PUT',
          body: JSON.stringify(payload),
        });
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to update provider.');
        }
        const result = await response.json();
        alert(result.message || 'Provider updated successfully!');
        this.$router.push('/client-dashboard');
      } catch (error) {
        this.error = error.message;
        alert(`Error: ${this.error}`);
      } finally {
        this.saving = false;
      }
    },
    async deleteProvider() {
      if (!confirm(`Are you sure you want to delete "${this.provider.name}"? This action cannot be undone.`)) {
        return;
      }
      this.saving = true;
      this.error = null;
      try {
        // The backend expects the ID in the URL path for DELETE
        const response = await apiFetch(`/backend/api/client-xs-providers.php/${this.id}`, {
          method: 'DELETE',
        });
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to delete provider.');
        }
        const result = await response.json();
        alert(result.message || 'Provider deleted successfully!');
        this.$router.push('/client-dashboard');
      } catch (error) {
        this.error = error.message;
        alert(`Error: ${this.error}`);
      } finally {
        this.saving = false;
      }
    },
    goBack() {
      this.$router.push('/client-dashboard');
    },
  },
};
</script>

<style scoped>
.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}
.form-label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}
.btn-filled {
  @apply bg-blue-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50;
}
.btn-outlined {
  @apply bg-transparent text-gray-600 border border-gray-300 font-medium px-4 py-2.5 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors disabled:opacity-50;
}
.btn-danger {
  @apply bg-red-600 text-white font-medium px-4 py-2.5 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:opacity-50;
}
.material-icon, .material-icon-sm {
  font-size: 20px;
  vertical-align: middle;
}
.loading-spinner {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>