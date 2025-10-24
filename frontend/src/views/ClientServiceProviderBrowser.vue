<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Navigation Header -->
      <div class="mb-8">
        <button @click="$router.push('/client-dashboard')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 transition-colors">
          <span class="material-icon-sm mr-2">arrow_back</span>
          Back to Dashboard
        </button>
      </div>

      <div class="text-center mb-12">
        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-blue-100 mb-6">
          <span class="material-icon text-blue-600">search</span>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Find Service Providers</h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Discover and connect with qualified service providers in your area
        </p>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="space-y-6">
          <!-- Search Input -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="material-icon-sm text-gray-400">search</span>
            </div>
            <input
              type="text"
              v-model="filters.search"
              placeholder="Search by name, description, or location..."
              @input="debouncedSearch"
              class="form-input pl-10 w-full"
            >
          </div>

          <!-- Filter Controls -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon-sm text-gray-500">build</span>
                Services
              </label>
              <select v-model="filters.selectedService" @change="applyFilters" class="form-input">
                <option value="">All Services</option>
                <option v-for="service in availableServices" :key="service.id" :value="service.id">
                  {{ service.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon-sm text-gray-500">location_on</span>
                Regions
              </label>
              <select v-model="filters.selectedRegion" @change="applyFilters" class="form-input">
                <option value="">All Regions</option>
                <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                  {{ region.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon-sm text-gray-500">sort</span>
                Sort By
              </label>
              <select v-model="filters.sortBy" @change="applyFilters" class="form-input">
                <option value="name">Name</option>
                <option value="created_at">Newest</option>
                <option value="updated_at">Recently Updated</option>
              </select>
            </div>

            <div>
              <label class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon-sm text-gray-500">arrow_upward</span>
                Order
              </label>
              <select v-model="filters.sortOrder" @change="applyFilters" class="form-input">
                <option value="ASC">Ascending</option>
                <option value="DESC">Descending</option>
              </select>
            </div>
          </div>

          <!-- Active Filters -->
          <div v-if="hasActiveFilters" class="pt-4 border-t border-gray-200">
            <span class="text-sm font-medium text-gray-700 mr-3">Active Filters:</span>
            <div class="flex flex-wrap gap-2 mt-2">
              <span v-if="filters.search" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                <span class="material-icon-sm mr-1">search</span>
                "{{ filters.search }}"
                <button @click="clearSearch" class="ml-2 text-blue-600 hover:text-blue-800">
                  <span class="material-icon-sm">close</span>
                </button>
              </span>
              <span v-if="filters.selectedService" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                <span class="material-icon-sm mr-1">build</span>
                {{ getServiceName(filters.selectedService) }}
                <button @click="clearServiceFilter" class="ml-2 text-green-600 hover:text-green-800">
                  <span class="material-icon-sm">close</span>
                </button>
              </span>
              <span v-if="filters.selectedRegion" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800">
                <span class="material-icon-sm mr-1">location_on</span>
                {{ getRegionName(filters.selectedRegion) }}
                <button @click="clearRegionFilter" class="ml-2 text-purple-600 hover:text-purple-800">
                  <span class="material-icon-sm">close</span>
                </button>
              </span>
              <button @click="clearAllFilters" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-100 text-red-800 hover:bg-red-200 transition-colors">
                <span class="material-icon-sm mr-1">clear_all</span>
                Clear All
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Results Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-gray-200">
          <div class="text-sm text-gray-600">
            <span v-if="loading" class="flex items-center gap-2">
              <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent"></div>
              Loading...
            </span>
            <span v-else>{{ totalCount }} service providers found</span>
          </div>
          <div class="flex gap-2">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2',
                viewMode === 'grid'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <span class="material-icon-sm">grid_view</span>
              Grid
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2',
                viewMode === 'list'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <span class="material-icon-sm">list</span>
              List
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-16">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mx-auto mb-4"></div>
          <p class="text-gray-600">Loading service providers...</p>
        </div>

        <!-- No Results -->
        <div v-else-if="providers.length === 0" class="text-center py-16">
          <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
            <span class="material-icon text-gray-400">search_off</span>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No service providers found</h3>
          <p class="text-gray-600 mb-6">Try adjusting your search criteria or filters</p>
          <button @click="clearAllFilters" class="btn-filled flex items-center gap-2 mx-auto">
            <span class="material-icon-sm">refresh</span>
            Clear Filters
          </button>
        </div>

        <!-- Results Grid -->
        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <div v-for="provider in providers" :key="provider.id" class="card hover:shadow-lg transition-shadow">
            <div class="card-header flex justify-between items-center">
              <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="material-icon text-white">{{ provider.name.charAt(0) }}</span>
              </div>
              <div class="flex gap-2">
                <button v-if="!provider.is_approved"
                        @click="addToApproved(provider.id)"
                        class="btn-filled btn-small flex items-center gap-1"
                        :disabled="approvingProvider === provider.id">
                  <span v-if="approvingProvider === provider.id" class="material-icon-sm animate-spin">refresh</span>
                  <span v-else class="material-icon-sm">add</span>
                  {{ approvingProvider === provider.id ? 'Adding...' : 'Add' }}
                </button>
                <button v-else
                        @click="removeFromApproved(provider.id)"
                        class="btn-outlined btn-small flex items-center gap-1"
                        :disabled="removingProvider === provider.id">
                  <span v-if="removingProvider === provider.id" class="material-icon-sm animate-spin">refresh</span>
                  <span v-else class="material-icon-sm">check</span>
                  {{ removingProvider === provider.id ? 'Removing...' : 'Added' }}
                </button>
              </div>
            </div>

            <div class="card-content">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ provider.name }}</h3>
              <p class="text-gray-600 text-sm mb-3">{{ provider.address }}</p>
              <p v-if="provider.description" class="text-gray-700 text-sm mb-4 line-clamp-2">
                {{ truncateText(provider.description, 100) }}
              </p>

              <div class="flex gap-4 text-sm text-gray-500 mb-4">
                <div class="flex items-center gap-1">
                  <span class="material-icon-sm">build</span>
                  {{ provider.services_count }} services
                </div>
                <div class="flex items-center gap-1">
                  <span class="material-icon-sm">location_on</span>
                  {{ provider.regions_count }} regions
                </div>
              </div>
            </div>

            <div class="card-footer flex justify-between items-center">
              <button @click="showProviderDetails(provider)" class="btn-text btn-small flex items-center gap-1">
                <span class="material-icon-sm">visibility</span>
                View Details
              </button>
              <div class="text-xs text-gray-500">
                Updated {{ formatDate(provider.updated_at) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Results List -->
        <div v-else class="providers-list">
        <div v-for="provider in providers" :key="provider.id" class="provider-list-item">
          <div class="provider-list-content">
            <div class="provider-list-header">
              <h3>{{ provider.name }}</h3>
              <div class="provider-list-actions">
                <button v-if="!provider.is_approved"
                        @click="addToApproved(provider.id)"
                        class="approve-btn-small"
                        :disabled="approvingProvider === provider.id">
                  {{ approvingProvider === provider.id ? 'Adding...' : '+ Add' }}
                </button>
                <button v-else
                        @click="removeFromApproved(provider.id)"
                        class="remove-btn-small"
                        :disabled="removingProvider === provider.id">
                  {{ removingProvider === provider.id ? 'Removing...' : '✓ Added' }}
                </button>
                <button @click="showProviderDetails(provider)" class="details-btn-small">
                  Details
                </button>
              </div>
            </div>
            <p class="provider-address">{{ provider.address }}</p>
            <div class="provider-meta">
              <span>{{ provider.services_count }} services</span> •
              <span>{{ provider.regions_count }} regions</span> •
              <span>Updated {{ formatDate(provider.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="page-btn">
          Previous
        </button>

        <span v-for="page in visiblePages" :key="page" class="page-numbers">
          <button v-if="page === '...'" class="page-ellipsis">{{ page }}</button>
          <button v-else
                  @click="goToPage(page)"
                  :class="['page-btn', { active: page === currentPage }]">
            {{ page }}
          </button>
        </span>

        <button @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="page-btn">
          Next
        </button>
      </div>
    </div>

    <!-- Provider Details Modal -->
    <div v-if="selectedProvider" class="modal-overlay" @click="closeProviderDetails">
      <div class="modal-content provider-details-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedProvider.name }}</h3>
          <button @click="closeProviderDetails" class="close-btn">&times;</button>
        </div>

        <div class="provider-details-content">
          <div class="provider-details-main">
            <div class="provider-info-section">
              <h4>Company Information</h4>
              <div class="info-grid">
                <div class="info-item">
                  <strong>Address:</strong> {{ selectedProvider.address }}
                </div>
                <div v-if="selectedProvider.website" class="info-item">
                  <strong>Website:</strong>
                  <a :href="selectedProvider.website" target="_blank">{{ selectedProvider.website }}</a>
                </div>
                <div v-if="selectedProvider.description" class="info-item full-width">
                  <strong>Description:</strong> {{ selectedProvider.description }}
                </div>
              </div>
            </div>

            <div class="provider-services-section">
              <h4>Services Offered</h4>
              <div class="services-tags">
                <span v-for="service in selectedProvider.services" :key="service.id"
                      :class="['service-tag', { primary: service.is_primary }]">
                  {{ service.name }}
                  <span v-if="service.is_primary" class="primary-indicator">★</span>
                </span>
              </div>
            </div>

            <div class="provider-regions-section">
              <h4>Service Regions</h4>
              <div class="regions-tags">
                <span v-for="region in selectedProvider.regions" :key="region.id" class="region-tag">
                  {{ region.name }}
                </span>
              </div>
            </div>

            <div class="provider-statistics-section">
              <h4>Performance Statistics</h4>
              <div class="statistics-grid">
                <div class="stat-item">
                  <div class="stat-value">{{ selectedProvider.statistics?.jobs_completed || 0 }}</div>
                  <div class="stat-label">Jobs Completed</div>
                </div>
                <div class="stat-item">
                  <div class="stat-value">{{ selectedProvider.statistics?.response_time_avg || 'N/A' }}</div>
                  <div class="stat-label">Avg Response Time</div>
                </div>
                <div class="stat-item">
                  <div class="stat-value">{{ selectedProvider.statistics?.completion_rate || 0 }}%</div>
                  <div class="stat-label">Completion Rate</div>
                </div>
                <div class="stat-item">
                  <div class="stat-value">{{ selectedProvider.statistics?.customer_rating || 'N/A' }}/5</div>
                  <div class="stat-label">Customer Rating</div>
                </div>
              </div>
            </div>
          </div>

          <div class="provider-details-actions">
            <button v-if="!selectedProvider.is_approved"
                    @click="addToApproved(selectedProvider.id)"
                    class="approve-btn-large"
                    :disabled="approvingProvider === selectedProvider.id">
              {{ approvingProvider === selectedProvider.id ? 'Adding to Approved...' : '+ Add to Approved List' }}
            </button>
            <button v-else
                    @click="removeFromApproved(selectedProvider.id)"
                    class="remove-btn-large"
                    :disabled="removingProvider === selectedProvider.id">
              {{ removingProvider === selectedProvider.id ? 'Removing...' : '✓ Remove from Approved List' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'ClientServiceProviderBrowser',
  data() {
    return {
      providers: [],
      availableServices: [],
      availableRegions: [],
      loading: false,
      currentPage: 1,
      totalPages: 1,
      totalCount: 0,
      viewMode: 'grid',
      selectedProvider: null,
      approvingProvider: null,
      removingProvider: null,
      filters: {
        search: '',
        selectedService: '',
        selectedRegion: '',
        sortBy: 'name',
        sortOrder: 'ASC'
      },
      searchTimeout: null
    }
  },
  computed: {
    hasActiveFilters() {
      return this.filters.search ||
             this.filters.selectedService ||
             this.filters.selectedRegion
    },
    visiblePages() {
      const pages = []
      const total = this.totalPages
      const current = this.currentPage

      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          pages.push(1, 2, 3, 4, 5, '...', total)
        } else if (current >= total - 3) {
          pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total)
        } else {
          pages.push(1, '...', current - 1, current, current + 1, '...', total)
        }
      }

      return pages
    }
  },
  mounted() {
    this.loadProviders()
  },
  methods: {
    async loadProviders() {
      this.loading = true
      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          limit: 12,
          search: this.filters.search,
          services: this.filters.selectedService,
          regions: this.filters.selectedRegion,
          sort: this.filters.sortBy,
          order: this.filters.sortOrder
        })

        const response = await apiFetch(`/backend/api/service-providers.php?${params}`)

        if (response.ok) {
          const data = await response.json()
          this.providers = data.providers
          this.availableServices = data.filters.services
          this.availableRegions = data.filters.regions
          this.totalCount = data.pagination.total_count
          this.totalPages = data.pagination.total_pages
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load service providers')
      } finally {
        this.loading = false
      }
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1
        this.loadProviders()
      }, 500)
    },

    applyFilters() {
      this.currentPage = 1
      this.loadProviders()
    },

    clearSearch() {
      this.filters.search = ''
      this.applyFilters()
    },

    clearServiceFilter() {
      this.filters.selectedService = ''
      this.applyFilters()
    },

    clearRegionFilter() {
      this.filters.selectedRegion = ''
      this.applyFilters()
    },

    clearAllFilters() {
      this.filters.search = ''
      this.filters.selectedService = ''
      this.filters.selectedRegion = ''
      this.filters.sortBy = 'name'
      this.filters.sortOrder = 'ASC'
      this.currentPage = 1
      this.loadProviders()
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
        this.loadProviders()
      }
    },

    async addToApproved(providerId) {
      this.approvingProvider = providerId
      try {
        const response = await apiFetch('/backend/api/client-approved-providers.php', {
          method: 'POST',
          body: JSON.stringify({ service_provider_id: providerId })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)

          // Update the provider's approval status
          const provider = this.providers.find(p => p.id === providerId)
          if (provider) {
            provider.is_approved = true
            provider.approval_details = data
          }
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to add provider to approved list')
      } finally {
        this.approvingProvider = null
      }
    },

    async removeFromApproved(providerId) {
      this.removingProvider = providerId
      try {
        const response = await apiFetch(`/backend/api/client-approved-providers.php?provider_id=${providerId}`, {
          method: 'DELETE'
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)

          // Update the provider's approval status
          const provider = this.providers.find(p => p.id === providerId)
          if (provider) {
            provider.is_approved = false
            provider.approval_details = null
          }
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to remove provider from approved list')
      } finally {
        this.removingProvider = null
      }
    },

    showProviderDetails(provider) {
      this.selectedProvider = { ...provider }
    },

    closeProviderDetails() {
      this.selectedProvider = null
    },

    getServiceName(serviceId) {
      const service = this.availableServices.find(s => s.id === serviceId)
      return service ? service.name : 'Unknown Service'
    },

    getRegionName(regionId) {
      const region = this.availableRegions.find(r => r.id === regionId)
      return region ? region.name : 'Unknown Region'
    },

    truncateText(text, maxLength) {
      if (text.length <= maxLength) return text
      return text.substring(0, maxLength) + '...'
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },

    handleError(error) {
      if (error.error) {
        alert(error.error)
      } else {
        alert('An error occurred')
      }
    }
  }
}
</script>

<style scoped>
.browser {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.browser-header {
  text-align: center;
  margin-bottom: 40px;
}

.browser-header h1 {
  color: #333;
  margin-bottom: 10px;
}

.browser-header p {
  color: #666;
  font-size: 1.1em;
}

/* Filters Section */
.filters-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filters-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.search-filter {
  width: 100%;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #007bff;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  font-weight: 500;
  margin-bottom: 5px;
  color: #333;
}

.filter-select {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  background: white;
}

.active-filters {
  padding-top: 15px;
  border-top: 1px solid #e0e0e0;
}

.filters-label {
  font-weight: 500;
  color: #666;
  margin-right: 10px;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.filter-tag {
  display: inline-flex;
  align-items: center;
  background: #e3f2fd;
  color: #1565c0;
  padding: 4px 8px;
  border-radius: 16px;
  font-size: 0.9em;
}

.remove-filter {
  background: none;
  border: none;
  color: #1565c0;
  cursor: pointer;
  margin-left: 4px;
  font-size: 1.2em;
  line-height: 1;
}

.clear-all-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
}

/* Results Section */
.results-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.results-count {
  font-weight: 500;
  color: #666;
}

.view-toggle {
  display: flex;
  gap: 5px;
}

.view-btn {
  padding: 8px 12px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s;
}

.view-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

/* Loading and No Results States */
.loading-state, .no-results {
  text-align: center;
  padding: 60px 20px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-results-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.no-results h3 {
  color: #666;
  margin-bottom: 10px;
}

.retry-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 15px;
}

/* Providers Grid */
.providers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.provider-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
}

.provider-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.provider-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.provider-logo {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #007bff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.2em;
}

.logo-placeholder {
  font-size: 1.5em;
}

.provider-actions {
  display: flex;
  gap: 8px;
}

.approve-btn, .remove-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  font-weight: 500;
  transition: all 0.2s;
}

.approve-btn {
  background: #28a745;
  color: white;
}

.approve-btn:hover:not(:disabled) {
  background: #218838;
}

.remove-btn {
  background: #dc3545;
  color: white;
}

.remove-btn:hover:not(:disabled) {
  background: #c82333;
}

.approve-btn:disabled, .remove-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.provider-content {
  padding: 15px;
}

.provider-name {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 1.1em;
}

.provider-address {
  color: #666;
  margin-bottom: 8px;
  font-size: 0.9em;
}

.provider-description {
  color: #555;
  margin-bottom: 12px;
  line-height: 1.4;
}

.provider-meta {
  display: flex;
  gap: 15px;
  font-size: 0.9em;
  color: #666;
}

.meta-icon {
  margin-right: 3px;
}

.provider-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.details-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.2s;
}

.details-btn:hover {
  background: #0056b3;
}

.provider-date {
  color: #666;
  font-size: 0.8em;
}

/* Providers List View */
.providers-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 30px;
}

.provider-list-item {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.provider-list-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.provider-list-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.provider-list-header {
  flex: 1;
  margin-right: 20px;
}

.provider-list-header h3 {
  margin: 0 0 8px 0;
  color: #333;
}

.provider-list-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.approve-btn-small, .remove-btn-small, .details-btn-small {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85em;
  font-weight: 500;
  transition: all 0.2s;
}

.approve-btn-small {
  background: #28a745;
  color: white;
}

.approve-btn-small:hover:not(:disabled) {
  background: #218838;
}

.remove-btn-small {
  background: #dc3545;
  color: white;
}

.remove-btn-small:hover:not(:disabled) {
  background: #c82333;
}

.details-btn-small {
  background: #6c757d;
  color: white;
}

.details-btn-small:hover {
  background: #545b62;
}

.approve-btn-small:disabled, .remove-btn-small:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 30px;
}

.page-btn {
  padding: 8px 12px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: #f8f9fa;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.page-ellipsis {
  border: none;
  background: none;
  cursor: default;
  color: #666;
}

.page-numbers {
  display: flex;
  gap: 5px;
}

/* Modal Styles */
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
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
}

.provider-details-modal {
  max-width: 800px;
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

.provider-details-content {
  padding: 20px;
}

.provider-details-main {
  margin-bottom: 30px;
}

.provider-info-section, .provider-services-section, .provider-regions-section {
  margin-bottom: 25px;
}

.provider-info-section h4, .provider-services-section h4, .provider-regions-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 1.1em;
}

.info-grid {
  display: grid;
  gap: 10px;
}

.info-item {
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item a {
  color: #007bff;
  text-decoration: none;
}

.info-item a:hover {
  text-decoration: underline;
}

.services-tags, .regions-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.service-tag, .region-tag {
  display: inline-flex;
  align-items: center;
  background: #e3f2fd;
  color: #1565c0;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 0.9em;
}

.service-tag.primary {
  background: #fff3cd;
  color: #856404;
}

.primary-indicator {
  margin-left: 4px;
  color: #856404;
}

.provider-statistics-section {
  margin-bottom: 25px;
}

.provider-statistics-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 1.1em;
}

.statistics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.stat-item {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 15px;
  text-align: center;
}

.stat-value {
  font-size: 1.5em;
  font-weight: bold;
  color: #007bff;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 0.9em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.provider-details-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.approve-btn-large, .remove-btn-large {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1em;
  font-weight: 500;
  transition: all 0.2s;
}

.approve-btn-large {
  background: #28a745;
  color: white;
}

.approve-btn-large:hover:not(:disabled) {
  background: #218838;
}

.remove-btn-large {
  background: #dc3545;
  color: white;
}

.remove-btn-large:hover:not(:disabled) {
  background: #c82333;
}

.approve-btn-large:disabled, .remove-btn-large:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .browser {
    padding: 10px;
  }

  .filters-section, .results-section {
    padding: 15px;
  }

  .filter-row {
    grid-template-columns: 1fr;
  }

  .results-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .providers-grid {
    grid-template-columns: 1fr;
  }

  .provider-list-content {
    flex-direction: column;
    gap: 15px;
  }

  .provider-list-actions {
    align-self: flex-start;
  }

  .pagination {
    flex-wrap: wrap;
  }

  .provider-details-actions {
    flex-direction: column;
  }

  .modal-content {
    width: 95%;
    margin: 10px;
  }
}
</style>
