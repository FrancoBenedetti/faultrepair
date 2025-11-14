<template>
  <div class="asset-manager p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
      <div class="mb-4">
        <button @click="$router.push('/client-dashboard')" class="btn-secondary flex items-center gap-2">
          <span class="material-icon-sm">arrow_back</span>
          Back to Dashboard
        </button>
      </div>
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200">
          <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
            <span class="material-icon text-blue-600">inventory_2</span>
            Asset Management
          </h2>
          <div class="section-header-actions flex items-center gap-4">
            <button @click="starSelectedAssets(true)" class="btn-secondary flex items-center gap-2" :disabled="selectedAssets.length === 0">
                <span class="material-icon-sm">star</span>
                Star
            </button>
            <button @click="starSelectedAssets(false)" class="btn-secondary flex items-center gap-2" :disabled="selectedAssets.length === 0">
                <span class="material-icon-sm">star_outline</span>
                Unstar
            </button>
            <button @click="generateQrCodes" class="btn-secondary flex items-center gap-2" :disabled="selectedAssets.length === 0">
                <span class="material-icon-sm">qr_code_2</span>
                Generate QR
            </button>
            <button @click="showCsvUploadModal = true" class="btn-outlined flex items-center gap-2">
              <span class="material-icon-sm">upload_file</span>
              Upload CSV
            </button>
            <button @click="openAssetModal()" class="btn-filled flex items-center gap-2">
              <span class="material-icon-sm">add</span>
              Add Asset
            </button>
          </div>
        </div>

        <div class="asset-list">
          <!-- Asset table -->
          <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
              <tr>
                <th class="py-3 px-4">
                  <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" />
                </th>
                <th class="py-3 px-4"></th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Asset No</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Item</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Description</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Location</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Manager</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Service Provider</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Status</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="10" class="text-center py-4">Loading assets...</td>
              </tr>
              <tr v-else-if="assets.length === 0">
                <td colspan="10" class="text-center py-4">No assets found.</td>
              </tr>
              <tr v-for="asset in assets" :key="asset.id" class="border-b">
                <td class="py-3 px-4">
                  <input type="checkbox" v-model="selectedAssets" :value="asset.id" />
                </td>
                <td class="py-3 px-4">
                    <button @click="toggleStar(asset)">
                        <span class="material-icon-sm" :class="asset.star ? 'text-yellow-500' : 'text-gray-400'">
                            {{ asset.star ? 'star' : 'star_outline' }}
                        </span>
                    </button>
                </td>
                <td class="py-3 px-4">{{ asset.asset_no }}</td>
                <td class="py-3 px-4">{{ asset.item }}</td>
                <td class="py-3 px-4">{{ asset.description }}</td>
                <td class="py-3 px-4">{{ asset.location_name }}</td>
                <td class="py-3 px-4">{{ asset.manager_name }}</td>
                <td class="py-3 px-4">{{ asset.sp_name }}</td>
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
      :service-providers="serviceProviders"
      :locations="locations"
      :managers="managers"
      @close="closeAssetModal"
      @save="saveAsset"
    />

    <CsvUploadModal
      v-if="showCsvUploadModal"
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
  name: 'AssetManager',
  components: {
    AssetModal,
    CsvUploadModal,
  },
  data() {
    return {
      assets: [],
      serviceProviders: [],
      locations: [],
      managers: [],
      loading: true,
      showAssetModal: false,
      selectedAsset: null,
      showCsvUploadModal: false, // New data property
      selectedAssets: [],
    };
  },
  computed: {
    isAllSelected() {
      return this.assets.length > 0 && this.selectedAssets.length === this.assets.length;
    }
  },
  async mounted() {
    await this.fetchAssets();
    await this.fetchDropdownData();
  },
  methods: {
    generateQrCodes() {
      if (this.selectedAssets.length === 0) {
        alert('Please select at least one asset to generate QR codes.');
        return;
      }
      const ids = this.selectedAssets.join(',');
      const token = localStorage.getItem('token');
      if (!token) {
        alert('Authentication token not found. Please log in again.');
        return;
      }
      const url = `/backend/api/qr-generator.php?ids=${ids}&token=${encodeURIComponent(token)}`;
      window.open(url, '_blank');
    },
    async starSelectedAssets(star) {
      if (this.selectedAssets.length === 0) return;
      try {
        const response = await apiFetch('/backend/api/star-assets.php', {
          method: 'POST',
          body: JSON.stringify({
            asset_ids: this.selectedAssets,
            star: star,
          }),
        });
        if (response.ok) {
          await this.fetchAssets(); // Refresh the list
          this.selectedAssets = []; // Clear selection
        } else {
          const error = await response.json();
          alert(`Failed to update assets: ${error.error}`);
        }
      } catch (error) {
        console.error('Error starring assets:', error);
        alert('An error occurred while updating assets.');
      }
    },
    async toggleStar(asset) {
        try {
            const response = await apiFetch('/backend/api/star-assets.php', {
                method: 'POST',
                body: JSON.stringify({
                    asset_ids: [asset.id],
                    star: !asset.star,
                }),
            });
            if (response.ok) {
                await this.fetchAssets(); // Refresh
            } else {
                const error = await response.json();
                alert(`Failed to update asset: ${error.error}`);
            }
        } catch (error) {
            console.error('Error toggling star:', error);
            alert('An error occurred while updating the asset.');
        }
    },
    toggleSelectAll(event) {
      if (event.target.checked) {
        this.selectedAssets = this.assets.map(asset => asset.id);
      } else {
        this.selectedAssets = [];
      }
    },
    async fetchAssets() {
      this.loading = true;
      console.log('Fetching assets...'); // Add this line
      try {
        const payload = this.getJwtPayload();
        if (!payload) {
          this.assets = [];
          this.loading = false;
          return;
        }
        const clientId = payload.entity_id;
        const response = await apiFetch(`/backend/api/assets.php?client_id=${clientId}`);
        if (response.ok) {
          const data = await response.json();
          const myList = data.asset_lists.find(list => list.list_owner_id === clientId);
          this.assets = myList ? myList.assets : [];
          console.log('Assets fetched:', this.assets); // Add this line
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
        try {
            const [providersRes, locationsRes, usersRes] = await Promise.all([
                apiFetch('/backend/api/client-approved-providers.php'),
                apiFetch('/backend/api/client-locations.php'),
                apiFetch('/backend/api/client-users.php?role=2')
            ]);

            if (providersRes.ok) {
                this.serviceProviders = (await providersRes.json()).approved_providers;
            }
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
    },
    closeAssetModal() {
      this.showAssetModal = false;
      this.selectedAsset = null;
    },
    async saveAsset(assetData) {
      const isNew = !assetData.id;
      const method = isNew ? 'POST' : 'PUT';
      const endpoint = isNew ? '/backend/api/assets.php' : `/backend/api/assets.php?id=${assetData.id}`;
      
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
};
</script>

<style scoped>
.asset-manager {
  font-family: 'Inter', sans-serif;
}
</style>
