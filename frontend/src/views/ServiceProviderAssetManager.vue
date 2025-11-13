<template>
  <div class="service-provider-asset-manager p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
      <div class="mb-4">
        <button @click="$router.push('/service-provider-dashboard')" class="btn-secondary flex items-center gap-2">
          <span class="material-icon-sm">arrow_back</span>
          Back to Dashboard
        </button>
      </div>
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200">
          <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
            <span class="material-icon text-blue-600">inventory_2</span>
            Asset Management (Service Provider)
          </h2>
          <div class="section-header-actions flex items-center gap-4">
            <select v-model="selectedClient" @change="fetchAssets" class="form-select">
              <option :value="null">Select a Client</option>
              <option v-for="client in approvedClients" :key="client.client_id" :value="client.client_id">
                {{ client.client_name }}
              </option>
            </select>
            <button @click="showCsvUploadModal = true" class="btn-outlined flex items-center gap-2" :disabled="!selectedClient">
              <span class="material-icon-sm">upload_file</span>
              Upload CSV
            </button>
            <button @click="openAssetModal()" class="btn-filled flex items-center gap-2" :disabled="!selectedClient">
              <span class="material-icon-sm">add</span>
              Add Asset
            </button>
          </div>
        </div>

        <div v-if="!selectedClient" class="text-center py-10 text-gray-500">
          Please select a client to view and manage assets.
        </div>
        <div v-else class="asset-list">
          <!-- Asset table -->
          <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Asset No</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Item</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Description</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Location</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Manager</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Status</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-4">Loading assets...</td>
              </tr>
              <tr v-else-if="assets.length === 0">
                <td colspan="7" class="text-center py-4">No assets found for this client.</td>
              </tr>
              <tr v-for="asset in assets" :key="asset.id" class="border-b">
                <td class="py-3 px-4">{{ asset.asset_no }}</td>
                <td class="py-3 px-4">{{ asset.item }}</td>
                <td class="py-3 px-4">{{ asset.description }}</td>
                <td class="py-3 px-4">{{ asset.location_name }}</td>
                <td class="py-3 px-4">{{ asset.manager_name }}</td>
                <td class="py-3 px-4">
                  <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusClass(asset.status)]">
                    {{ asset.status }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <button @click="openAssetModal(asset)" class="text-blue-600 hover:text-blue-900">Edit</button>
                  <button @click="deleteAsset(asset)" class="text-red-600 hover:text-red-900 ml-4">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <AssetModal
      v-if="showAssetModal"
      :asset="selectedAsset"
      :service-providers="[]"
      :locations="locations"
      :managers="managers"
      :is-service-provider="true"
      @close="closeAssetModal"
      @save="saveAsset"
    />

    <CsvUploadModal
      v-if="showCsvUploadModal"
      :client-id="selectedClient"
      @close="showCsvUploadModal = false"
      @uploadSuccess="fetchAssets"
    />
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js';
import AssetModal from '@/components/modals/AssetModal.vue';
import CsvUploadModal from '@/components/modals/CsvUploadModal.vue';

export default {
  name: 'ServiceProviderAssetManager',
  components: {
    AssetModal,
    CsvUploadModal,
  },
  data() {
    return {
      assets: [],
      approvedClients: [],
      selectedClient: null,
      locations: [],
      managers: [],
      loading: false,
      showAssetModal: false,
      selectedAsset: null,
      showCsvUploadModal: false,
    };
  },
  async mounted() {
    await this.fetchApprovedClients();
  },
  methods: {
    async fetchApprovedClients() {
      try {
        const response = await apiFetch('/backend/api/service-provider-approved-clients.php');
        if (response.ok) {
          const data = await response.json();
          this.approvedClients = data.approved_clients;
        } else {
          console.error('Failed to fetch approved clients');
          this.approvedClients = [];
        }
      } catch (error) {
        console.error('Error fetching approved clients:', error);
        this.approvedClients = [];
      }
    },
    async fetchAssets() {
      if (!this.selectedClient) {
        this.assets = [];
        return;
      }

      this.loading = true;
      try {
        const payload = this.getJwtPayload();
        if (!payload) {
          this.assets = [];
          this.loading = false;
          return;
        }
        const spId = payload.entity_id; // Service Provider's own ID

        const response = await apiFetch(`/backend/api/assets.php?client_id=${this.selectedClient}`);
        if (response.ok) {
          const data = await response.json();
          // For SP, list_owner_id should be the SP's own ID
          const myList = data.asset_lists.find(list => list.list_owner_id === spId);
          this.assets = myList ? myList.assets : [];
        } else {
          console.error('Failed to fetch assets');
          this.assets = [];
        }
      } catch (error) {
        console.error('Error fetching assets:', error);
        this.assets = [];
      } finally {
        this.loading = false;
      }
    },
    async fetchDropdownData() {
        if (!this.selectedClient) return;
        try {
            const [locationsRes, usersRes] = await Promise.all([
                apiFetch(`/backend/api/client-locations.php?client_id=${this.selectedClient}`),
                apiFetch(`/backend/api/client-users.php?client_id=${this.selectedClient}&role=2`)
            ]);

            if (locationsRes.ok) {
                this.locations = (await locationsRes.json()).locations;
            }
            if (usersRes.ok) {
                this.managers = (await usersRes.json()).users;
            }
        } catch (error) {
            console.error('Error fetching dropdown data:', error);
        }
    },
    openAssetModal(asset = null) {
      this.selectedAsset = asset ? { ...asset } : null;
      this.showAssetModal = true;
      this.fetchDropdownData(); // Fetch dropdown data when modal opens
    },
    closeAssetModal() {
      this.showAssetModal = false;
      this.selectedAsset = null;
    },
    async saveAsset(assetData) {
      if (!this.selectedClient) {
        alert('Please select a client first.');
        return;
      }

      const isNew = !assetData.id;
      const method = isNew ? 'POST' : 'PUT';
      const endpoint = isNew ? '/backend/api/assets.php' : `/backend/api/assets.php?id=${assetData.id}`;
      
      const payload = this.getJwtPayload();
      if (!payload) {
        alert('Authentication error.');
        return;
      }

      // For Service Provider, sp_id is auto-filled with their own entity_id
      assetData.sp_id = payload.entity_id;
      assetData.client_id = this.selectedClient; // Must send client_id for SP POST/PUT

      try {
        const response = await apiFetch(endpoint, {
          method,
          body: JSON.stringify(assetData),
        });

        if (response.ok) {
          await this.fetchAssets();
          this.closeAssetModal();
        } else {
          const error = await response.json();
          alert(`Error: ${error.error}`);
        }
      } catch (error) {
        console.error('Error saving asset:', error);
        alert('An error occurred while saving the asset.');
      }
    },
    async deleteAsset(asset) {
      if (!confirm(`Are you sure you want to delete asset "${asset.item}"?`)) {
        return;
      }

      try {
        const response = await apiFetch(`/backend/api/assets.php?id=${asset.id}`, {
          method: 'DELETE',
        });

        if (response.ok) {
          await this.fetchAssets();
        } else {
          const error = await response.json();
          alert(`Error: ${error.error}`);
        }
      } catch (error) {
        console.error('Error deleting asset:', error);
        alert('An error occurred while deleting the asset.');
      }
    },
    getJwtPayload() {
        const token = localStorage.getItem('token');
        if (token) {
            try {
                return JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
            } catch (e) {
                return null;
            }
        }
        return null;
    },
    getStatusClass(status) {
      const classes = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-yellow-100 text-yellow-800',
        'decommissioned': 'bg-red-100 text-red-800',
        'unavailable': 'bg-gray-100 text-gray-800',
      };
      return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
    }
  },
  watch: {
    selectedClient(newVal, oldVal) {
      if (newVal !== oldVal) {
        this.fetchAssets();
        this.fetchDropdownData();
      }
    }
  }
};
</script>

<style scoped>
.service-provider-asset-manager {
  font-family: 'Inter', sans-serif;
}
</style>
