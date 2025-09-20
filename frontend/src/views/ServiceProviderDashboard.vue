<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Dashboard Header -->
      <div class="dashboard-header flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6 mb-8 p-6 bg-white rounded-xl border border-gray-200">
        <div class="header-left">
          <h1 class="text-3xl font-bold text-gray-900 mb-4">Service Provider Dashboard</h1>
          <div class="profile-completeness flex items-center gap-4">
            <div class="completeness-bar w-64 h-3 bg-gray-200 rounded-full overflow-hidden">
              <div class="completeness-fill h-full bg-blue-600 transition-all duration-500 ease-out" :style="{ width: profileCompleteness + '%' }"></div>
            </div>
            <span class="completeness-text text-lg font-medium text-gray-900">{{ profileCompleteness }}% Complete</span>
          </div>
        </div>
        <div class="header-right">
          <button @click="signOut" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">logout</span>
            Sign Out
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Profile Overview -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">business</span>
            Profile Overview
          </h2>
          <button v-if="userRole === 3" @click="showProfileModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">edit</span>
            Edit Profile
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="material-icon text-white">{{ profile.name?.charAt(0) || 'B' }}</span>
              </div>
              <div>
                <h3 class="text-xl font-semibold text-gray-900">{{ profile.name }}</h3>
                <p class="text-gray-600 flex items-center gap-2">
                  <span class="material-icon-sm">location_on</span>
                  {{ profile.address }}
                </p>
              </div>
            </div>

            <div v-if="profile.description" class="bg-gray-50 rounded-lg p-4">
              <p class="text-gray-700">{{ profile.description }}</p>
            </div>
          </div>

          <div class="space-y-4">
            <div v-if="profile.manager_name || profile.website" class="bg-blue-50 rounded-lg p-4">
              <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <span class="material-icon-sm text-blue-600">contact_mail</span>
                Contact Information
              </h4>
              <div class="space-y-2">
                <p v-if="profile.manager_name" class="text-gray-700 flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">person</span>
                  <strong>Manager:</strong> {{ profile.manager_name }}
                </p>
                <p v-if="profile.website" class="text-gray-700 flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">link</span>
                  <strong>Website:</strong>
                  <a :href="profile.website" target="_blank" class="text-blue-600 hover:text-blue-800 underline">{{ profile.website }}</a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Services Section -->
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">build</span>
            Services Offered
          </h2>
          <button @click="showServicesModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">add</span>
            Manage Services
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="service in services" :key="service.id" class="relative bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                  <span class="material-icon-sm text-white">build</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ service.name }}</h3>
                  <p class="text-sm text-gray-600">{{ service.category }}</p>
                </div>
              </div>
              <span v-if="service.is_primary" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                Primary
              </span>
            </div>
          </div>

          <!-- Add Service Button -->
          <button @click="showServicesModal = true" class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-blue-400 hover:bg-blue-50 transition-colors flex flex-col items-center justify-center gap-2 text-gray-500 hover:text-blue-600">
            <span class="material-icon text-2xl">add_circle</span>
            <span class="font-medium">Add Service</span>
          </button>
        </div>
      </div>

      <!-- Regions Section -->
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">location_on</span>
            Service Regions
          </h2>
          <button @click="showRegionsModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">add</span>
            Manage Regions
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="region in regions" :key="region.id" class="relative bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                <span class="material-icon-sm text-white">location_on</span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">{{ region.name }}</h3>
                <p class="text-sm text-gray-600">{{ region.code }}</p>
              </div>
            </div>
          </div>

          <!-- Add Region Button -->
          <button @click="showRegionsModal = true" class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-green-400 hover:bg-green-50 transition-colors flex flex-col items-center justify-center gap-2 text-gray-500 hover:text-green-600">
            <span class="material-icon text-2xl">add_location</span>
            <span class="font-medium">Add Region</span>
          </button>
        </div>
      </div>

      <!-- Technicians Section -->
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">engineering</span>
            Technicians
          </h2>
          <button @click="openAddTechnicianModal" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">person_add</span>
            Add Technician
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="technician in technicians" :key="technician.id" class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center p-4 bg-gray-50 border-b border-gray-200">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center">
                  <span class="material-icon text-white">{{ technician.full_name.charAt(0) }}</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ technician.full_name }}</h3>
                  <span :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    technician.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  ]">
                    {{ technician.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
              <div class="flex gap-2">
                <button @click="viewTechnicianJobs(technician)" class="btn-round" title="View Jobs">
                  <span class="material-icon-sm">work</span>
                </button>
                <button @click="editTechnician(technician)" class="btn-round" title="Edit Technician">
                  <span class="material-icon-sm">edit</span>
                </button>
                <button @click="deleteTechnician(technician)" class="btn-round-filled" title="Delete Technician">
                  <span class="material-icon-sm">delete</span>
                </button>
              </div>
            </div>

            <div class="p-4">
              <p class="text-sm text-gray-600 flex items-center gap-1 mb-2">
                <span class="material-icon-sm">email</span>
                {{ technician.email }}
              </p>
              <p v-if="technician.phone" class="text-sm text-gray-600 flex items-center gap-1">
                <span class="material-icon-sm">phone</span>
                {{ technician.phone }}
              </p>
            </div>

            <div class="px-4 pb-4">
              <p class="text-xs text-gray-500">
                Added {{ formatDate(technician.created_at) }}
              </p>
            </div>
          </div>

          <!-- Add Technician Card -->
          <button @click="openAddTechnicianModal" class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-purple-400 hover:bg-purple-50 transition-colors flex flex-col items-center justify-center gap-3 text-gray-500 hover:text-purple-600">
            <span class="material-icon text-3xl">person_add</span>
            <div class="text-center">
              <div class="font-medium">Add Technician</div>
              <div class="text-sm">Create a new technician account</div>
            </div>
          </button>
        </div>

        <!-- Empty State -->
        <div v-if="technicians.length === 0" class="text-center py-16">
          <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
            <span class="material-icon text-gray-400">engineering</span>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No Technicians Yet</h3>
          <p class="text-gray-600 mb-6">Add technicians to help manage your jobs and assignments.</p>
          <button @click="openAddTechnicianModal" class="btn-filled flex items-center gap-2 mx-auto">
            <span class="material-icon-sm">person_add</span>
            Add Your First Technician
          </button>
        </div>
      </div>

      <!-- Approved Clients Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">group</span>
            Approved Clients
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="client in approvedClients" :key="client.id" class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-600 rounded-full flex items-center justify-center">
                  <span class="material-icon text-white">{{ client.name.charAt(0) }}</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ client.name }}</h3>
                  <p class="text-sm text-gray-600 flex items-center gap-1">
                    <span class="material-icon-sm">location_on</span>
                    {{ client.address }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Job Statistics -->
            <div class="grid grid-cols-3 gap-2 mb-4">
              <div class="text-center">
                <div class="text-lg font-bold text-blue-600">{{ client.total_jobs }}</div>
                <div class="text-xs text-gray-600">Total Jobs</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-green-600">{{ client.active_jobs }}</div>
                <div class="text-xs text-gray-600">Active</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-gray-600">{{ client.completed_jobs }}</div>
                <div class="text-xs text-gray-600">Completed</div>
              </div>
            </div>

            <div class="flex gap-2">
              <button @click="viewClientJobs(client.id)" class="btn-filled btn-small flex items-center gap-1 flex-1">
                <span class="material-icon-sm">work</span>
                View Jobs
              </button>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200">
              <p class="text-xs text-gray-500 flex items-center gap-1">
                <span class="material-icon-sm">event_available</span>
                Approved {{ formatDate(client.approved_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="approvedClients.length === 0" class="text-center py-16">
          <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
            <span class="material-icon text-gray-400">group</span>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No Approved Clients Yet</h3>
          <p class="text-gray-600">Clients will appear here once they approve your services.</p>
        </div>
      </div>
    </div>

    <!-- Profile Edit Modal -->
    <div v-if="showProfileModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="showProfileModal = false"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">edit</span>
            Edit Profile
          </h3>
          <button @click="showProfileModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Form -->
        <form @submit.prevent="updateProfile" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <!-- Company Name and Website Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">business</span>
                Company Name *
              </label>
              <input
                type="text"
                id="name"
                v-model="editForm.name"
                required
                class="form-input"
                placeholder="Enter company name"
              >
            </div>
            <div>
              <label for="website" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">link</span>
                Website
              </label>
              <input
                type="url"
                id="website"
                v-model="editForm.website"
                class="form-input"
                placeholder="https://example.com"
              >
            </div>
          </div>

          <!-- Address -->
          <div>
            <label for="address" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">location_on</span>
              Address *
            </label>
            <textarea
              id="address"
              v-model="editForm.address"
              required
              rows="3"
              class="form-input resize-none"
              placeholder="Enter company address"
            ></textarea>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">description</span>
              Description
            </label>
            <textarea
              id="description"
              v-model="editForm.description"
              rows="3"
              class="form-input resize-none"
              placeholder="Describe your company and services"
            ></textarea>
          </div>

          <!-- Manager Name and Email Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="manager_name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">person</span>
                Manager Name
              </label>
              <input
                type="text"
                id="manager_name"
                v-model="editForm.manager_name"
                class="form-input"
                placeholder="Enter manager name"
              >
            </div>
            <div>
              <label for="manager_email" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">email</span>
                Manager Email
              </label>
              <input
                type="email"
                id="manager_email"
                v-model="editForm.manager_email"
                class="form-input"
                placeholder="Enter manager email"
              >
            </div>
          </div>

          <!-- Manager Phone and VAT Number Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="manager_phone" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">phone</span>
                Manager Phone
              </label>
              <input
                type="tel"
                id="manager_phone"
                v-model="editForm.manager_phone"
                class="form-input"
                placeholder="Enter manager phone"
              >
            </div>
            <div>
              <label for="vat_number" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">receipt</span>
                VAT Number
              </label>
              <input
                type="text"
                id="vat_number"
                v-model="editForm.vat_number"
                class="form-input"
                placeholder="Enter VAT number"
              >
            </div>
          </div>

          <!-- Business Registration Number -->
          <div>
            <label for="business_registration_number" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">badge</span>
              Business Registration Number
            </label>
            <input
              type="text"
              id="business_registration_number"
              v-model="editForm.business_registration_number"
              class="form-input"
              placeholder="Enter business registration number"
            >
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="showProfileModal = false"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Saving...' : 'Cancel' }}
            </button>
            <button
              type="submit"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Services Modal -->
    <div v-if="showServicesModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="showServicesModal = false"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">build</span>
            Manage Services
          </h3>
          <button @click="showServicesModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Available Services -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="material-icon-sm text-blue-600">list</span>
                Available Services
              </h4>
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="service in availableServices" :key="service.id" class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                  <input
                    type="checkbox"
                    :value="service.id"
                    v-model="selectedServices"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                  >
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ service.name }}</div>
                    <div class="text-sm text-gray-600">{{ service.category }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Selected Services -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="material-icon-sm text-green-600">check_circle</span>
                Selected Services
              </h4>
              <div v-if="selectedServices.length === 0" class="text-center py-8 text-gray-500">
                <span class="material-icon text-4xl text-gray-300">inventory_2</span>
                <p class="mt-2">No services selected</p>
              </div>
              <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="serviceId in selectedServices" :key="serviceId" class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ getServiceName(serviceId) }}</div>
                  </div>
                  <button
                    type="button"
                    @click="setPrimaryService(serviceId)"
                    :class="[
                      'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                      isPrimaryService(serviceId)
                        ? 'bg-yellow-100 text-yellow-800 border border-yellow-300'
                        : 'bg-gray-200 text-gray-700 hover:bg-yellow-100 hover:text-yellow-800'
                    ]"
                  >
                    {{ isPrimaryService(serviceId) ? 'Primary' : 'Set Primary' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 mt-6">
            <button
              type="button"
              @click="showServicesModal = false"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Saving...' : 'Cancel' }}
            </button>
            <button
              type="button"
              @click="updateServices"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Saving...' : 'Save Services' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Regions Modal -->
    <div v-if="showRegionsModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="showRegionsModal = false"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-green-600">location_on</span>
            Manage Regions
          </h3>
          <button @click="showRegionsModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Available Regions -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="material-icon-sm text-green-600">list</span>
                Available Regions
              </h4>
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="region in availableRegions" :key="region.id" class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                  <input
                    type="checkbox"
                    :value="region.id"
                    v-model="selectedRegions"
                    class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                  >
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ region.name }}</div>
                    <div class="text-sm text-gray-600">{{ region.code }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Selected Regions -->
            <div class="space-y-4">
              <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="material-icon-sm text-blue-600">check_circle</span>
                Selected Regions
              </h4>
              <div v-if="selectedRegions.length === 0" class="text-center py-8 text-gray-500">
                <span class="material-icon text-4xl text-gray-300">location_off</span>
                <p class="mt-2">No regions selected</p>
              </div>
              <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="regionId in selectedRegions" :key="regionId" class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                  <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                    <span class="material-icon-sm text-white">location_on</span>
                  </div>
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ getRegionName(regionId) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 mt-6">
            <button
              type="button"
              @click="showRegionsModal = false"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Saving...' : 'Cancel' }}
            </button>
            <button
              type="button"
              @click="updateRegions"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Saving...' : 'Save Regions' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Technician Modal -->
    <div v-if="showAddTechnicianModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="closeAddTechnicianModal"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-purple-600">person_add</span>
            Add New Technician
          </h3>
          <button @click="closeAddTechnicianModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Form -->
        <form @submit.prevent="createTechnician" class="p-6 space-y-6">
          <!-- Username and Email Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="tech_username" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">person</span>
                Username *
              </label>
              <input
                type="text"
                id="tech_username"
                v-model="technicianForm.username"
                required
                class="form-input"
                placeholder="Enter username"
              >
            </div>
            <div>
              <label for="tech_email" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">email</span>
                Email Address *
              </label>
              <input
                type="email"
                id="tech_email"
                v-model="technicianForm.email"
                required
                class="form-input"
                placeholder="Enter email address"
              >
            </div>
          </div>

          <!-- First Name and Last Name Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="tech_first_name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">badge</span>
                First Name *
              </label>
              <input
                type="text"
                id="tech_first_name"
                v-model="technicianForm.first_name"
                required
                class="form-input"
                placeholder="Enter first name"
              >
            </div>
            <div>
              <label for="tech_last_name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">badge</span>
                Last Name *
              </label>
              <input
                type="text"
                id="tech_last_name"
                v-model="technicianForm.last_name"
                required
                class="form-input"
                placeholder="Enter last name"
              >
            </div>
          </div>

          <!-- Password and Phone Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="tech_password" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">lock</span>
                Password *
              </label>
              <input
                type="password"
                id="tech_password"
                v-model="technicianForm.password"
                required
                class="form-input"
                placeholder="Create a secure password"
              >
            </div>
            <div>
              <label for="tech_phone" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">phone</span>
                Phone Number
              </label>
              <input
                type="tel"
                id="tech_phone"
                v-model="technicianForm.phone"
                class="form-input"
                placeholder="Enter phone number"
              >
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="closeAddTechnicianModal"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Creating...' : 'Cancel' }}
            </button>
            <button
              type="submit"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">person_add</span>
              {{ loading ? 'Creating...' : 'Create Technician' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Technician Modal -->
    <div v-if="showEditTechnicianModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="closeEditTechnicianModal"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-purple-600">edit</span>
            Edit Technician
          </h3>
          <button @click="closeEditTechnicianModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Form -->
        <form @submit.prevent="updateTechnician" class="p-6 space-y-6">
          <!-- Username and Email Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="edit_username" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">person</span>
                Username
              </label>
              <input
                type="text"
                id="edit_username"
                v-model="technicianForm.username"
                readonly
                class="form-input bg-gray-50 cursor-not-allowed"
                placeholder="Username cannot be changed"
              >
            </div>
            <div>
              <label for="edit_email" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">email</span>
                Email Address
              </label>
              <input
                type="email"
                id="edit_email"
                v-model="technicianForm.email"
                class="form-input"
                placeholder="Enter email address"
              >
            </div>
          </div>

          <!-- First Name and Last Name Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="edit_first_name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">badge</span>
                First Name
              </label>
              <input
                type="text"
                id="edit_first_name"
                v-model="technicianForm.first_name"
                class="form-input"
                placeholder="Enter first name"
              >
            </div>
            <div>
              <label for="edit_last_name" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">badge</span>
                Last Name
              </label>
              <input
                type="text"
                id="edit_last_name"
                v-model="technicianForm.last_name"
                class="form-input"
                placeholder="Enter last name"
              >
            </div>
          </div>

          <!-- Phone and Status Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="edit_phone" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">phone</span>
                Phone Number
              </label>
              <input
                type="tel"
                id="edit_phone"
                v-model="technicianForm.phone"
                class="form-input"
                placeholder="Enter phone number"
              >
            </div>
            <div>
              <label for="edit_status" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">toggle_on</span>
                Status
              </label>
              <select
                id="edit_status"
                v-model="technicianForm.is_active"
                class="form-input"
              >
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="closeEditTechnicianModal"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Updating...' : 'Cancel' }}
            </button>
            <button
              type="submit"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Updating...' : 'Update Technician' }}
            </button>
          </div>
        </form>
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
      approvedClients: [],
      technicians: [],
      profileCompleteness: 0,
      availableServices: [],
      availableRegions: [],
      selectedServices: [],
      selectedRegions: [],
      primaryServiceId: null,
      showProfileModal: false,
      showServicesModal: false,
      showRegionsModal: false,
      showAddTechnicianModal: false,
      showEditTechnicianModal: false,
      loading: false,
      editingTechnician: null,
      userRole: null, // Store user role for UI restrictions
      technicianForm: {
        username: '',
        password: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: ''
      },
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
    this.getUserRole()
    this.loadProfile()
    this.loadAvailableOptions()
    this.loadApprovedClients()
    if (this.userRole === 3) {
      this.loadTechnicians()
    }
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

    async loadApprovedClients() {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/service-provider-approved-clients.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          this.approvedClients = data.clients
        } else {
          console.error('Failed to load approved clients')
        }
      } catch (error) {
        console.error('Failed to load approved clients')
      }
    },

    viewClientJobs(clientId) {
      this.$router.push(`/service-provider/client/${clientId}/jobs`)
    },

    viewTechnicianJobs(technician) {
      this.$router.push(`/service-provider/technician/${technician.id}/jobs`)
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },

    getRegionName(regionId) {
      const region = this.availableRegions.find(r => r.id === regionId)
      return region ? region.name : 'Unknown Region'
    },

    signOut() {
      localStorage.removeItem('token')
      this.$router.push('/')
    },

    getUserRole() {
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          this.userRole = payload.role_id
        } catch (error) {
          console.error('Error parsing token:', error)
          this.userRole = null
        }
      }
    },

    handleError(error) {
      if (error.error) {
        alert(error.error)
      } else {
        alert('An error occurred')
      }
    },

    // Technician Management Methods
    async loadTechnicians() {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/technicians.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          this.technicians = data.technicians
        } else {
          console.error('Failed to load technicians')
        }
      } catch (error) {
        console.error('Failed to load technicians')
      }
    },

    openAddTechnicianModal() {
      this.technicianForm = {
        username: '',
        password: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: ''
      }
      this.editingTechnician = null
      this.showAddTechnicianModal = true
    },

    closeAddTechnicianModal() {
      this.showAddTechnicianModal = false
      this.technicianForm = {
        username: '',
        password: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: ''
      }
    },

    async createTechnician() {
      if (!this.technicianForm.username || !this.technicianForm.password ||
          !this.technicianForm.email || !this.technicianForm.first_name || !this.technicianForm.last_name) {
        alert('Please fill in all required fields')
        return
      }

      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/technicians.php', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.technicianForm)
        })

        if (response.ok) {
          const data = await response.json()
          this.closeAddTechnicianModal()
          this.loadTechnicians() // Reload technicians list
          alert('Technician created successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to create technician')
      } finally {
        this.loading = false
      }
    },

    editTechnician(technician) {
      this.editingTechnician = technician
      this.technicianForm = {
        username: technician.username,
        email: technician.email,
        first_name: technician.first_name,
        last_name: technician.last_name,
        phone: technician.phone || '',
        is_active: technician.is_active
      }
      this.showEditTechnicianModal = true
    },

    closeEditTechnicianModal() {
      this.showEditTechnicianModal = false
      this.editingTechnician = null
      this.technicianForm = {
        username: '',
        password: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: ''
      }
    },

    async updateTechnician() {
      if (!this.editingTechnician) return

      this.loading = true
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/backend/api/technicians.php', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            technician_id: this.editingTechnician.id,
            ...this.technicianForm
          })
        })

        if (response.ok) {
          this.closeEditTechnicianModal()
          this.loadTechnicians() // Reload technicians list
          alert('Technician updated successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update technician')
      } finally {
        this.loading = false
      }
    },

    async deleteTechnician(technician) {
      if (!confirm(`Are you sure you want to delete ${technician.full_name}?`)) {
        return
      }

      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`/backend/api/technicians.php?id=${technician.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          this.loadTechnicians() // Reload technicians list
          alert('Technician deleted successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to delete technician')
      }
    }
  }
}
</script>
