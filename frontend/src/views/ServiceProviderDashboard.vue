<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h1>Service Provider Dashboard</h1>
      <div class="profile-completeness">
        <div class="completeness-bar">
          <div class="completeness-fill" :style="{ width: profileCompleteness + '%' }"></div>
        </div>
        <span class="completeness-text">{{ profileCompleteness }}% Complete</span>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Profile Overview -->
      <div class="profile-section">
        <h2>Profile Overview</h2>
        <div class="profile-card">
          <div class="profile-info">
            <h3>{{ profile.name }}</h3>
            <p class="address">{{ profile.address }}</p>
            <p class="description" v-if="profile.description">{{ profile.description }}</p>
            <div class="contact-info" v-if="profile.manager_name || profile.website">
              <p v-if="profile.manager_name"><strong>Manager:</strong> {{ profile.manager_name }}</p>
              <p v-if="profile.website"><strong>Website:</strong>
                <a :href="profile.website" target="_blank">{{ profile.website }}</a>
              </p>
            </div>
          </div>
          <div class="profile-actions">
            <button @click="showProfileModal = true" class="btn-primary">Edit Profile</button>
          </div>
        </div>
      </div>

      <!-- Services Section -->
      <div class="services-section">
        <h2>Services Offered</h2>
        <div class="services-grid">
          <div v-for="service in services" :key="service.id" class="service-item">
            <span class="service-name">{{ service.name }}</span>
            <span v-if="service.is_primary" class="primary-badge">Primary</span>
          </div>
          <button @click="showServicesModal = true" class="add-service-btn">
            <i class="icon-plus">+</i> Add Service
          </button>
        </div>
      </div>

      <!-- Regions Section -->
      <div class="regions-section">
        <h2>Service Regions</h2>
        <div class="regions-grid">
          <div v-for="region in regions" :key="region.id" class="region-item">
            <span class="region-name">{{ region.name }}</span>
            <span class="region-code">{{ region.code }}</span>
          </div>
          <button @click="showRegionsModal = true" class="add-region-btn">
            <i class="icon-plus">+</i> Add Region
          </button>
        </div>
      </div>
    </div>

    <!-- Profile Edit Modal -->
    <div v-if="showProfileModal" class="modal-overlay" @click="showProfileModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Edit Profile</h3>
          <button @click="showProfileModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="updateProfile" class="profile-form">
          <div class="form-row">
            <div class="form-group">
              <label for="name">Company Name *</label>
              <input type="text" id="name" v-model="editForm.name" required>
            </div>
            <div class="form-group">
              <label for="website">Website</label>
              <input type="url" id="website" v-model="editForm.website" placeholder="https://">
            </div>
          </div>

          <div class="form-group">
            <label for="address">Address *</label>
            <textarea id="address" v-model="editForm.address" required></textarea>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" v-model="editForm.description" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="manager_name">Manager Name</label>
              <input type="text" id="manager_name" v-model="editForm.manager_name">
            </div>
            <div class="form-group">
              <label for="manager_email">Manager Email</label>
              <input type="email" id="manager_email" v-model="editForm.manager_email">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="manager_phone">Manager Phone</label>
              <input type="tel" id="manager_phone" v-model="editForm.manager_phone">
            </div>
            <div class="form-group">
              <label for="vat_number">VAT Number</label>
              <input type="text" id="vat_number" v-model="editForm.vat_number">
            </div>
          </div>

          <div class="form-group">
            <label for="business_registration_number">Business Registration Number</label>
            <input type="text" id="business_registration_number" v-model="editForm.business_registration_number">
          </div>

          <div class="form-actions">
            <button type="button" @click="showProfileModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Services Modal -->
    <div v-if="showServicesModal" class="modal-overlay" @click="showServicesModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Manage Services</h3>
          <button @click="showServicesModal = false" class="close-btn">&times;</button>
        </div>
        <div class="services-management">
          <div class="available-services">
            <h4>Available Services</h4>
            <div class="services-list">
              <div v-for="service in availableServices" :key="service.id" class="service-option">
                <label class="checkbox-label">
                  <input type="checkbox"
                         :value="service.id"
                         v-model="selectedServices">
                  <span class="checkmark"></span>
                  <span class="service-label">{{ service.name }}</span>
                  <span class="service-category">{{ service.category }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="selected-services">
            <h4>Selected Services</h4>
            <div v-if="selectedServices.length === 0" class="no-services">
              No services selected
            </div>
            <div v-else class="selected-list">
              <div v-for="serviceId in selectedServices" :key="serviceId" class="selected-service">
                <span>{{ getServiceName(serviceId) }}</span>
                <button type="button" @click="setPrimaryService(serviceId)"
                        :class="['primary-btn', { active: isPrimaryService(serviceId) }]">
                  Primary
                </button>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="showServicesModal = false" class="btn-secondary">Cancel</button>
            <button type="button" @click="updateServices" class="btn-primary" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save Services' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Regions Modal -->
    <div v-if="showRegionsModal" class="modal-overlay" @click="showRegionsModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Manage Regions</h3>
          <button @click="showRegionsModal = false" class="close-btn">&times;</button>
        </div>
        <div class="regions-management">
          <div class="available-regions">
            <h4>Available Regions</h4>
            <div class="regions-list">
              <div v-for="region in availableRegions" :key="region.id" class="region-option">
                <label class="checkbox-label">
                  <input type="checkbox"
                         :value="region.id"
                         v-model="selectedRegions">
                  <span class="checkmark"></span>
                  <span class="region-label">{{ region.name }}</span>
                  <span class="region-code">{{ region.code }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="selected-regions">
            <h4>Selected Regions</h4>
            <div v-if="selectedRegions.length === 0" class="no-regions">
              No regions selected
            </div>
            <div v-else class="selected-list">
              <div v-for="regionId in selectedRegions" :key="regionId" class="selected-region">
                <span>{{ getRegionName(regionId) }}</span>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="showRegionsModal = false" class="btn-secondary">Cancel</button>
            <button type="button" @click="updateRegions" class="btn-primary" :disabled="loading">
              {{ loading ? 'Saving...' : 'Save Regions' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ServiceProviderDashboard',
  data() {
    return {
      profile: {},
      services: [],
      regions: [],
      profileCompleteness: 0,
      availableServices: [],
      availableRegions: [],
      selectedServices: [],
      selectedRegions: [],
      primaryServiceId: null,
      showProfileModal: false,
      showServicesModal: false,
      showRegionsModal: false,
      loading: false,
      editForm: {
        name: '',
        address: '',
        website: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        vat_number: '',
        business_registration_number: ''
      }
    }
  },
  mounted() {
    this.loadProfile()
    this.loadAvailableOptions()
  },
  methods: {
    async loadProfile() {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/service-provider-profile.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          this.profile = data.profile
          this.services = data.services
          this.regions = data.regions
          this.profileCompleteness = data.profile_completeness

          // Initialize form data
          Object.keys(this.editForm).forEach(key => {
            if (this.profile[key] !== undefined) {
              this.editForm[key] = this.profile[key]
            }
          })

          // Initialize selected services and regions
          this.selectedServices = this.services.map(s => s.id)
          this.selectedRegions = this.regions.map(r => r.id)
          this.primaryServiceId = this.services.find(s => s.is_primary)?.id || null
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load profile')
      }
    },

    async loadAvailableOptions() {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/service-providers.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          this.availableServices = data.filters.services
          this.availableRegions = data.filters.regions
        }
      } catch (error) {
        console.error('Failed to load available options')
      }
    },

    async updateProfile() {
      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/service-provider-profile.php', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.editForm)
        })

        if (response.ok) {
          const data = await response.json()
          this.profile = data.profile
          this.services = data.services
          this.regions = data.regions
          this.profileCompleteness = data.profile_completeness
          this.showProfileModal = false
          alert('Profile updated successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update profile')
      } finally {
        this.loading = false
      }
    },

    async updateServices() {
      this.loading = true
      try {
        const servicesData = this.selectedServices.map(serviceId => ({
          id: serviceId,
          is_primary: serviceId === this.primaryServiceId
        }))

        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/service-provider-profile.php', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            services: servicesData
          })
        })

        if (response.ok) {
          const data = await response.json()
          this.services = data.services
          this.showServicesModal = false
          alert('Services updated successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update services')
      } finally {
        this.loading = false
      }
    },

    async updateRegions() {
      this.loading = true
      try {
        const regionsData = this.selectedRegions.map(regionId => ({
          id: regionId
        }))

        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/service-provider-profile.php', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            regions: regionsData
          })
        })

        if (response.ok) {
          const data = await response.json()
          this.regions = data.regions
          this.showRegionsModal = false
          alert('Regions updated successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update regions')
      } finally {
        this.loading = false
      }
    },

    setPrimaryService(serviceId) {
      this.primaryServiceId = serviceId
    },

    isPrimaryService(serviceId) {
      return this.primaryServiceId === serviceId
    },

    getServiceName(serviceId) {
      const service = this.availableServices.find(s => s.id === serviceId)
      return service ? service.name : 'Unknown Service'
    },

    getRegionName(regionId) {
      const region = this.availableRegions.find(r => r.id === regionId)
      return region ? region.name : 'Unknown Region'
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
.dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.profile-completeness {
  display: flex;
  align-items: center;
  gap: 15px;
}

.completeness-bar {
  width: 200px;
  height: 8px;
  background-color: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.completeness-fill {
  height: 100%;
  background-color: #28a745;
  transition: width 0.3s ease;
}

.completeness-text {
  font-weight: 500;
  color: #666;
}

.dashboard-content {
  display: grid;
  gap: 30px;
}

.profile-section, .services-section, .regions-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.profile-info h3 {
  margin: 0 0 10px 0;
  color: #333;
}

.address {
  color: #666;
  margin: 5px 0;
}

.description {
  margin: 10px 0;
  color: #555;
}

.contact-info p {
  margin: 5px 0;
}

.services-grid, .regions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 15px;
}

.service-item, .region-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.primary-badge {
  background: #007bff;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.8em;
}

.add-service-btn, .add-region-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  background: #e9ecef;
  border: 2px dashed #dee2e6;
  border-radius: 6px;
  color: #6c757d;
  cursor: pointer;
  transition: all 0.2s;
}

.add-service-btn:hover, .add-region-btn:hover {
  background: #dee2e6;
  border-color: #adb5bd;
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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

/* Form Styles */
.profile-form {
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
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-primary, .btn-secondary {
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

/* Services and Regions Management */
.services-management, .regions-management {
  padding: 20px;
}

.services-management {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

.regions-management {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

.services-list, .regions-list {
  max-height: 300px;
  overflow-y: auto;
}

.service-option, .region-option {
  margin-bottom: 10px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.checkbox-label:hover {
  background: #f8f9fa;
}

.checkbox-label input[type="checkbox"] {
  margin-right: 10px;
}

.service-label, .region-label {
  flex: 1;
  font-weight: 500;
}

.service-category, .region-code {
  color: #666;
  font-size: 0.9em;
}

.selected-list {
  max-height: 300px;
  overflow-y: auto;
}

.selected-service, .selected-region {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  margin-bottom: 8px;
  background: #f8f9fa;
  border-radius: 4px;
}

.primary-btn {
  padding: 4px 8px;
  border: 1px solid #007bff;
  background: white;
  color: #007bff;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8em;
  transition: all 0.2s;
}

.primary-btn:hover {
  background: #007bff;
  color: white;
}

.primary-btn.active {
  background: #007bff;
  color: white;
}

.no-services, .no-regions {
  text-align: center;
  color: #666;
  padding: 40px;
  font-style: italic;
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .services-management, .regions-management {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .profile-card {
    flex-direction: column;
    gap: 15px;
  }
}
</style>
