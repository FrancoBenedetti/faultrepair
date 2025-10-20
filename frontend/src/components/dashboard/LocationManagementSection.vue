<template>
  <CollapsibleSection
    title="Locations"
    icon="location_on"
    :expanded="expanded"
    @toggle="$emit('toggle')"
  >
    <template #header-actions>
      <!-- View Mode Toggle -->
      <div class="view-mode-toggle flex border border-gray-300 rounded-lg overflow-hidden mr-4">
        <button @click.stop="viewMode = 'cards'"
                :class="{ 'bg-blue-600 text-white': viewMode === 'cards', 'bg-white text-gray-700 hover:bg-gray-50': viewMode !== 'cards' }"
                class="px-4 py-2 text-sm font-medium transition-colors">
          <span class="material-icon-sm mr-1">grid_view</span>
          Cards
        </button>
        <button @click.stop="viewMode = 'table'"
                :class="{ 'bg-blue-600 text-white': viewMode === 'table', 'bg-white text-gray-700 hover:bg-gray-50': viewMode !== 'table' }"
                class="px-4 py-2 text-sm font-medium transition-colors">
          <span class="material-icon-sm mr-1">table_chart</span>
          Table
        </button>
      </div>
      <button v-if="isAdmin" @click.stop="$emit('add-location')" class="btn-filled">
        <span class="material-icon-sm mr-2">add</span>
        Add Location
      </button>
    </template>

    <!-- Loading state -->
    <LoadingState v-if="!locations" message="Loading locations..." />

    <!-- Default location state -->
    <ErrorState
      v-else-if="locations && locations.length === 0"
      title="Default Location"
      message="Using client name as default location. Add specific locations if needed."
      icon="location_on"
    />

    <!-- Locations display -->
    <div v-else class="locations-content">
      <!-- Cards View -->
      <div v-if="viewMode === 'cards'" class="locations-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card
          v-for="location in locations"
          :key="location.id"
          clickable
          @click="$emit('filter-jobs-by-location', location)"
        >
          <template #header>
            <div class="location-icon w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
              <span class="material-icon text-white">location_on</span>
            </div>
            <div class="location-actions flex gap-2" v-if="isAdmin">
              <button @click.stop="$emit('edit-location', location)" class="btn-outlined btn-small">
                <span class="material-icon-sm">edit</span>
              </button>
              <button v-if="location.job_count === 0" @click.stop="$emit('delete-location', location)" class="btn-outlined btn-small text-error-600 border-error-600 hover:bg-error-50">
                <span class="material-icon-sm">delete</span>
              </button>
            </div>
          </template>

          <template #content>
            <h3 class="location-name text-title-medium text-on-surface mb-2">{{ location.name }}</h3>
            <p class="location-address text-body-medium text-on-surface-variant mb-2">{{ location.address || 'No address specified' }}</p>

            <!-- GPS Coordinates -->
            <div v-if="location.coordinates" class="location-coordinates mb-2">
              <a :href="`https://maps.google.com/maps?q=${encodeURIComponent(location.coordinates)}`"
                 target="_blank"
                 class="coordinates-link text-blue-600 hover:text-blue-800 font-medium">
                📍 {{ location.coordinates }}
              </a>
            </div>

            <!-- Access Rules Link -->
            <div v-if="location.access_rules" class="location-access mb-2">
              <a :href="location.access_rules"
                 target="_blank"
                 class="access-link text-green-600 hover:text-green-800 font-medium">
                🔗 Site Information
              </a>
            </div>

            <!-- Access Instructions -->
            <div v-if="location.access_instructions" class="location-instructions mb-3">
              <p class="text-sm text-gray-600 line-clamp-2">
                <span class="font-medium text-gray-700">Instructions:</span> {{ location.access_instructions }}
              </p>
            </div>

            <div class="location-stats">
              <div class="stat-item">
                <span class="stat-number text-2xl font-bold text-on-surface">{{ location.job_count }}</span>
                <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Service Requests</span>
              </div>
            </div>
          </template>

          <template #footer>
            <div class="location-click-hint text-label-medium text-on-surface-variant">
              <span class="material-icon-sm mr-1">filter_list</span>
              Click to filter jobs
            </div>
          </template>
        </Card>
      </div>

      <!-- Table View -->
      <div v-else-if="viewMode === 'table'" class="locations-table-container">
        <div class="locations-table-wrapper">
          <table class="locations-table">
            <thead>
              <tr>
                <th class="col-name">Location Name</th>
                <th class="col-address">Address</th>
                <th class="col-jobs">Service Requests</th>
                <th class="col-actions" v-if="isAdmin">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="location in locations" :key="location.id" class="location-row" @click="$emit('filter-jobs-by-location', location)">
                <td class="col-name">
                  <div class="location-name-cell">
                    <span class="location-icon">📍</span>
                    <span class="location-name-text">{{ location.name }}</span>
                  </div>
                </td>
                <td class="col-address">{{ location.address || 'No address specified' }}</td>
                <td class="col-jobs">
                  <span class="job-count-badge">{{ location.job_count }}</span>
                </td>
                <td class="col-actions" v-if="isAdmin">
                  <div class="table-actions">
                    <button @click.stop="$emit('edit-location', location)" class="btn-secondary btn-small">
                      Edit
                    </button>
                    <button v-if="location.job_count === 0" @click.stop="$emit('delete-location', location)" class="btn-danger btn-small">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </CollapsibleSection>
</template>

<script>
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'

export default {
  name: 'LocationManagementSection',
  components: {
    CollapsibleSection,
    Card,
    LoadingState,
    ErrorState
  },
  props: {
    expanded: {
      type: Boolean,
      default: false
    },
    locations: {
      type: Array,
      default: null
    },
    isAdmin: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      viewMode: 'cards' // 'cards' or 'table'
    }
  }
}
</script>

<style scoped>
.view-mode-toggle {
  display: flex;
  border: 1px solid #ddd;
  border-radius: 6px;
  overflow: hidden;
}

.locations-content {
  margin-top: 15px;
}

.locations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.location-icon {
  font-size: 20px;
  color: #007bff;
  margin-right: 8px;
}

.location-name {
  margin: 0 0 8px 0;
}

.location-address {
  margin: 0 0 8px 0;
}

.location-coordinates,
.location-access {
  margin-bottom: 8px;
}

.coordinates-link,
.access-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 14px;
}

.coordinates-link:hover,
.access-link:hover {
  text-decoration: underline;
}

.location-instructions {
  margin-bottom: 16px;
}

.location-stats {
  display: flex;
  align-items: center;
}

.stat-number {
  display: block;
  font-size: 1.8em;
  font-weight: bold;
  color: #333;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 0.8em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.location-click-hint {
  text-align: left;
  font-size: 0.85em;
}

.btn-outlined {
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 0.9em;
  color: #666;
  transition: all 0.2s ease;
}

.btn-outlined:hover {
  background: #f0f0f0;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.9em;
}

/* Table View Styles */
.locations-table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.locations-table-wrapper {
  min-width: 600px;
}

.locations-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.locations-table th {
  background: #f8f9fa;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #e0e0e0;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.5px;
}

.locations-table td {
  padding: 15px;
  border-bottom: 1px solid #e0e0e0;
  vertical-align: middle;
}

.location-row {
  transition: background-color 0.2s;
  cursor: pointer;
}

.location-row:hover {
  background: #f8f9fa;
}

.location-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.location-name-text {
  font-weight: 500;
  color: #333;
}

.col-name {
  width: 30%;
}

.col-address {
  width: 40%;
}

.col-jobs {
  width: 15%;
  text-align: center;
}

.col-actions {
  width: 15%;
  text-align: center;
}

.job-count-badge {
  display: inline-block;
  background: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  min-width: 20px;
  text-align: center;
}

.table-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

@media (max-width: 768px) {
  .locations-grid {
    grid-template-columns: 1fr;
  }

  .view-mode-toggle {
    display: none;
  }

  /* Force cards view on mobile */
  .locations-table-container {
    display: none;
  }

  .location-instructions p {
    font-size: 0.8em;
  }
}
</style>
