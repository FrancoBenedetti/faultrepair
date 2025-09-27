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
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('profile')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.profile }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">business</span>
              Profile Overview
            </h2>
          </div>
          <button v-if="userRole === 3" @click.stop="showProfileModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">edit</span>
            Edit Profile
          </button>
        </div>

        <div v-show="sectionsExpanded.profile" class="section-content transition-all duration-300 ease-in-out">
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
      </div>

      <!-- Services Section -->
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('services')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.services }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">build</span>
              Services Offered
            </h2>
          </div>
          <button v-if="userRole === 3" @click.stop="showServicesModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">add</span>
            Manage Services
          </button>
        </div>

        <div v-show="sectionsExpanded.services" class="section-content transition-all duration-300 ease-in-out">
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
      </div>

      <!-- Regions Section -->
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('regions')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.regions }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">location_on</span>
              Service Regions
            </h2>
          </div>
          <button v-if="userRole === 3" @click.stop="showRegionsModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">add</span>
            Manage Regions
          </button>
        </div>

        <div v-show="sectionsExpanded.regions" class="section-content transition-all duration-300 ease-in-out">
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
      </div>

      <!-- Technicians Section -->
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('technicians')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.technicians }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">engineering</span>
              Technicians
            </h2>
          </div>
          <button v-if="userRole === 3" @click.stop="openAddTechnicianModal" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">person_add</span>
            Add Technician
          </button>
        </div>

        <div v-show="sectionsExpanded.technicians" class="section-content transition-all duration-300 ease-in-out">
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
      <div v-if="userRole === 3" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('clients')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.clients }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">group</span>
              Approved Clients
            </h2>
          </div>
        </div>

        <div v-show="sectionsExpanded.clients" class="section-content transition-all duration-300 ease-in-out">
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

      <!-- Jobs Section -->
      <div class="jobs-section card p-6">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('jobs')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.jobs }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0">Job Management</h2>
          </div>
        </div>

        <div v-show="sectionsExpanded.jobs" class="section-content transition-all duration-300 ease-in-out">
          <!-- Job Filters -->
          <div class="job-filters flex flex-wrap gap-4 mb-6 p-4 bg-neutral-50 rounded-lg">
            <div class="filter-group min-w-40">
              <label for="status-filter" class="form-label mb-1">Status:</label>
              <select id="status-filter" v-model="jobFilters.status" @change="loadJobs" class="form-input">
                <option value="">All Statuses</option>
                <option value="Reported">Reported</option>
                <option value="Assigned">Assigned</option>
                <option value="Quote Requested">Quote Requested</option>
                <option value="Quote Accepted">Quote Accepted</option>
                <option value="Completion Confirmed">Completion Confirmed</option>
                <option value="Declined">Declined</option>
                <option value="In Progress">In Progress</option>
                <option value="Quote Provided">Quote Provided</option>
                <option value="Repaired">Repaired</option>
                <option value="Not repairable">Not repairable</option>
                <option value="Payment Requested">Payment Requested</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
            <div v-if="userRole === 3" class="filter-group min-w-40">
              <label for="client-filter" class="form-label mb-1">Client:</label>
              <select id="client-filter" v-model="jobFilters.client_id" @change="loadJobs" class="form-input">
                <option value="">All Clients</option>
                <option v-for="client in approvedClients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
            </div>
            <div v-if="userRole === 3" class="filter-group min-w-40">
              <label for="technician-filter" class="form-label mb-1">Technician:</label>
              <select id="technician-filter" v-model="jobFilters.technician_id" @change="loadJobs" class="form-input">
                <option value="">All Technicians</option>
                <option v-for="technician in technicians" :key="technician.id" :value="technician.id">
                  {{ technician.full_name }}
                </option>
              </select>
            </div>
          </div>

          <div class="jobs-grid grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Loading state -->
            <div v-if="jobs === null" class="loading-state col-span-full text-center py-16">
              <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
              <p class="text-body-large text-on-surface-variant">Loading jobs...</p>
            </div>

            <!-- Job cards -->
            <div v-else-if="jobs && jobs.length > 0" v-for="job in jobs" :key="job.id" class="job-card card overflow-hidden transition-all duration-200 hover:shadow-elevation-3">
              <div class="job-header card-header flex justify-between items-center">
                <div class="job-status">
                  <span class="status-badge" :class="getStatusClass(job.job_status)">
                    {{ job.job_status }}
                  </span>
                </div>
                <div class="job-actions flex gap-2">
                  <button v-if="userRole === 3 || (userRole === 4 && job.job_status !== 'In Progress')" @click="viewJobDetails(job)" class="btn-outlined btn-small">
                    <span class="material-icon-sm">visibility</span>
                  </button>
                  <button v-if="userRole === 3 || (userRole === 4 && job.job_status === 'In Progress')" @click="editJob(job)" class="btn-outlined btn-small" :title="userRole === 3 ? 'Edit/Allocate Job' : 'Update Job Status'">
                    <span class="material-icon-sm">edit</span>
                  </button>
                </div>
              </div>

              <div class="job-content card-content">
                <h3 class="job-title text-title-medium text-on-surface mb-2">{{ job.item_identifier || 'No Item ID' }}</h3>
                <p class="job-description text-body-medium text-on-surface-variant mb-2 line-clamp-2">{{ job.fault_description }}</p>
                <p class="job-location text-body-small text-on-surface-variant mb-4">
                  <span class="material-icon-sm mr-1">location_on</span>
                  {{ job.location_name }}
                </p>

                <div class="job-meta grid grid-cols-2 gap-3 text-sm">
                  <div class="meta-item">
                    <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Client:</span>
                    <span class="meta-value text-body-small text-on-surface font-medium">{{ job.client_name }}</span>
                  </div>
                  <div class="meta-item">
                    <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Date:</span>
                    <span class="meta-value text-body-small text-on-surface font-medium">{{ formatDate(job.created_at) }}</span>
                  </div>
                  <div class="meta-item">
                    <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Provider:</span>
                    <span class="meta-value text-body-small text-on-surface font-medium">{{ job.assigned_technician || 'Not assigned' }}</span>
                  </div>
                  <div class="meta-item">
                    <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Images:</span>
                    <span class="meta-value text-body-small text-on-surface font-medium">{{ job.image_count }}</span>
                  </div>
                </div>
              </div>

              <div class="job-footer card-footer">
                <div class="job-date text-label-medium text-on-surface-variant">
                  <span class="material-icon-sm mr-1">schedule</span>
                  Last updated {{ formatDate(job.updated_at) }}
                </div>
              </div>
            </div>

            <!-- No jobs state -->
            <div v-else-if="jobs && jobs.length === 0" class="no-jobs col-span-full text-center py-16">
              <div class="no-jobs-icon material-icon-xl text-neutral-400 mb-4">build</div>
              <h3 class="text-title-large text-on-surface mb-2">No jobs found</h3>
              <p class="text-body-large text-on-surface-variant">No jobs match your current filters.</p>
            </div>

            <!-- Error state -->
            <div v-else class="error-state col-span-full text-center py-16">
              <div class="error-icon material-icon-xl text-error-400 mb-4">error</div>
              <h3 class="text-title-large text-on-surface mb-2">Failed to load jobs</h3>
              <p class="text-body-large text-on-surface-variant">Please try refreshing the page.</p>
            </div>
          </div>
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

          <!-- Phone Row -->
          <div class="grid grid-cols-1 gap-4">
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

    <!-- Job Details Modal -->
    <div v-if="showJobDetailsModal" class="modal-overlay" @click="showJobDetailsModal = false">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3 class="flex items-center gap-3">
            <span class="material-icon text-blue-600">work</span>
            Job Details: {{ selectedJob?.item_identifier || 'No Item ID' }}
          </h3>
          <button @click="showJobDetailsModal = false" class="close-btn">&times;</button>
        </div>

        <div v-if="selectedJob" class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">
          <!-- Job Information -->
          <div class="job-info-section">
            <div class="info-grid">
              <div class="info-item">
                <label>Status:</label>
                <span class="status-badge" :class="getStatusClass(selectedJob.job_status)">
                  {{ selectedJob.job_status }}
                </span>
              </div>
              <div class="info-item">
                <label>Location:</label>
                <span>{{ selectedJob.location_name }}</span>
              </div>
              <div class="info-item">
                <label>Reported By:</label>
                <span>{{ selectedJob.reporting_user }}</span>
              </div>
              <div class="info-item">
                <label>Date Reported:</label>
                <span>{{ formatDate(selectedJob.created_at) }}</span>
              </div>
              <div v-if="selectedJob.assigned_provider_name" class="info-item">
                <label>Assigned Provider:</label>
                <span>{{ selectedJob.assigned_provider_name }}</span>
              </div>
              <div class="info-item">
                <label>Last Updated:</label>
                <span>{{ formatDate(selectedJob.updated_at) }}</span>
              </div>
            </div>

            <div class="description-section">
              <label>Fault Description:</label>
              <p class="fault-description">{{ selectedJob.fault_description }}</p>
            </div>

            <div v-if="selectedJob.contact_person" class="contact-section">
              <label>Contact Person:</label>
              <span>{{ selectedJob.contact_person }}</span>
            </div>
          </div>

          <!-- Technician Notes (visible to service providers only) -->
          <div v-if="(userRole === 3 || userRole === 4) && selectedJob.technician_notes" class="technician-notes-section">
            <h4>Technician Notes</h4>
            <div class="technician-notes-content">
              <p>{{ selectedJob.technician_notes }}</p>
            </div>
          </div>

          <!-- Images Gallery -->
          <div class="images-section">
            <h4>Attached Images ({{ selectedJob.images?.length || 0 }})</h4>

            <div v-if="!selectedJob.images || selectedJob.images.length === 0" class="no-images">
              <div class="no-images-icon">📷</div>
              <p>No images attached to this job</p>
            </div>

            <div v-else class="images-gallery">
              <div class="gallery-grid">
                <div v-for="image in selectedJob.images" :key="image.id" class="gallery-item">
                  <img :src="`/backend/uploads/job_images/${image.filename}`"
                       :alt="image.original_filename"
                       class="gallery-image"
                       @click="openImageModal(image)">
                  <div class="image-overlay">
                    <span class="image-filename">{{ image.original_filename }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Job Modal -->
    <div v-if="showEditJobModal" class="modal-overlay" @click="showEditJobModal = false">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3 class="flex items-center gap-3">
            <span class="material-icon text-blue-600">edit</span>
            Edit Job: {{ editingJob?.item_identifier || 'No Item ID' }}
          </h3>
          <button @click="showEditJobModal = false" class="close-btn">&times;</button>
        </div>

        <form v-if="editingJob" @submit.prevent="updateJob" class="job-form">
          <!-- Status and Provider (provider assignment restricted to budget controllers) -->
          <div class="form-row">
            <div class="form-group">
              <label for="edit-status">Status *</label>
              <select id="edit-status" v-model="editingJob.job_status" required>
                <option v-for="status in getAvailableStatuses(editingJob, userRole)" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
            </div>
          </div>

          <!-- Technician Selection (for In Progress status or when changing technicians) - Only for Admins -->
          <div v-if="(editingJob.job_status === 'In Progress' || (originalJobStatus === 'In Progress' && editingJob.job_status === 'In Progress')) && userRole === 3" class="form-group">
            <label for="edit-technician" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">engineering</span>
              Assign Technician *
            </label>
            <select id="edit-technician" v-model="selectedTechnicianId" class="form-input">
              <option value="">Select a technician...</option>
              <option v-for="technician in technicians" :key="technician.id" :value="technician.id">
                {{ technician.full_name }} ({{ technician.username }})
              </option>
            </select>
            <small class="form-help">A technician must be assigned for jobs in "In Progress" status</small>
          </div>

          <!-- Provider assignment only for budget controllers -->
          <div class="form-group" v-if="userRole === 2">
            <label for="edit-provider">Assigned Provider</label>
            <select id="edit-provider" v-model="editingJob.assigned_provider_id">
              <option value="">No Provider Assigned</option>
              <option v-for="provider in approvedProviders" :key="provider.service_provider_id" :value="provider.service_provider_id">
                {{ provider.name }}
              </option>
            </select>
          </div>

          <!-- Show current provider for reporting employees (read-only) -->
          <div class="form-group" v-else-if="editingJob.assigned_provider_name">
            <label>Assigned Provider</label>
            <div class="readonly-field">{{ editingJob.assigned_provider_name }}</div>
            <small class="form-help">Only Budget Controllers can change provider assignments</small>
          </div>

          <!-- Full details (only editable when status is 'Reported') -->
          <div v-if="canEditJobDetails(editingJob)" class="edit-details-section">
            <h4>Job Details</h4>

            <div class="form-row">
              <div class="form-group">
                <label for="edit-item-identifier">Item Identifier</label>
                <input type="text" id="edit-item-identifier" v-model="editingJob.item_identifier"
                       placeholder="e.g., Computer-001, Printer-ABC">
                <small class="form-help">Optional: Unique identifier for the item</small>
              </div>
              <div class="form-group">
                <label for="edit-contact-person">Contact Person</label>
                <input type="text" id="edit-contact-person" v-model="editingJob.contact_person"
                       placeholder="Person to contact about this fault">
                <small class="form-help">Optional: Who should the technician contact?</small>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-fault-description">Fault Description *</label>
              <textarea id="edit-fault-description" v-model="editingJob.fault_description" required
                        rows="4" placeholder="Describe the fault in detail..."></textarea>
            </div>

            <!-- Image Upload Section for editing -->
            <div class="form-group">
              <label>Attach Additional Images</label>
              <ImageUpload
                ref="editImageUpload"
                :max-images="10"
                :max-file-size="10 * 1024 * 1024"
                @images-changed="handleEditImagesChanged"
              />
            </div>
          </div>

          <!-- Technician Notes (visible to service providers only) -->
          <div v-if="userRole === 3 || userRole === 4" class="technician-notes-section">
            <h4>Technician Notes</h4>
            <div class="form-group">
              <label for="edit-technician-notes">Technician Notes</label>
              <textarea id="edit-technician-notes" v-model="editingJob.technician_notes"
                        rows="4" placeholder="Internal notes for technicians and service providers..."></textarea>
              <small class="form-help">These notes are only visible to service providers and technicians</small>
            </div>
          </div>

          <!-- Read-only details when not fully editable -->
          <div v-else class="readonly-details-section">
            <h4>Job Details (Read Only)</h4>

            <div class="readonly-info">
              <div class="info-row">
                <label>Item Identifier:</label>
                <span>{{ editingJob.item_identifier || 'Not specified' }}</span>
              </div>
              <div class="info-row">
                <label>Contact Person:</label>
                <span>{{ editingJob.contact_person || 'Not specified' }}</span>
              </div>
              <div class="info-row">
                <label>Fault Description:</label>
                <span>{{ editingJob.fault_description }}</span>
              </div>
            </div>
          </div>

          <!-- Existing Images Gallery -->
          <div class="images-section">
            <h4>Attached Images ({{ editingJob.images?.length || 0 }})</h4>

            <div v-if="!editingJob.images || editingJob.images.length === 0" class="no-images">
              <div class="no-images-icon">📷</div>
              <p>No images attached to this job</p>
            </div>

            <div v-else class="images-gallery">
              <div class="gallery-grid">
                <div v-for="image in editingJob.images" :key="image.id" class="gallery-item">
                  <img :src="`/backend/uploads/job_images/${image.filename}`"
                       :alt="image.original_filename"
                       class="gallery-image"
                       @click="openImageModal(image)">
                  <div class="image-overlay">
                    <span class="image-filename">{{ image.original_filename }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="showEditJobModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary">
              Update Job
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Modal for Full Size View -->
    <div v-if="selectedImage" class="modal-overlay" @click="selectedImage = null">
      <div class="image-modal-content" @click.stop>
        <div class="image-modal-header">
          <h3>{{ selectedImage.original_filename }}</h3>
          <button @click="selectedImage = null" class="close-btn">&times;</button>
        </div>
        <div class="image-modal-body">
          <img :src="`/backend/uploads/job_images/${selectedImage.filename}`"
               :alt="selectedImage.original_filename"
               class="full-size-image">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'ServiceProviderDashboard',
  data() {
    return {
      profile: {},
      services: [],
      regions: [],
      approvedClients: [],
      technicians: [],
      jobs: null, // Start as null to show loading state
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
      showJobDetailsModal: false,
      showEditJobModal: false,
      loading: false,
      editingTechnician: null,
      selectedJob: null,
      selectedImage: null,
      editingJob: null,
      originalJobStatus: null,
      originalProviderId: null,
      editingImages: [], // Array to store additional images for editing
      userRole: null, // Store user role for UI restrictions
      selectedTechnicianId: null, // For technician assignment when setting status to "In Progress"
      jobFilters: {
        status: '',
        client_id: '',
        technician_id: ''
      },
      // Section collapse/expand state
      sectionsExpanded: {
        profile: false, // Profile overview collapsed by default
        services: false, // Services collapsed by default
        regions: false, // Regions collapsed by default
        technicians: false, // Technicians collapsed by default
        clients: false, // Clients collapsed by default
        jobs: true // Jobs section expanded by default
      },
      technicianForm: {
        username: '',
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
    this.loadJobs()
    if (this.userRole === 3) {
      this.loadTechnicians()
    }
  },
  methods: {
    async loadProfile() {
      try {
        const response = await apiFetch('/backend/api/service-provider-profile.php')

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
        const response = await apiFetch('/backend/api/service-providers.php')

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
        const response = await apiFetch('/backend/api/service-provider-profile.php', {
          method: 'PUT',
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

        const response = await apiFetch('/backend/api/service-provider-profile.php', {
          method: 'PUT',
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

        const response = await apiFetch('/backend/api/service-provider-profile.php', {
          method: 'PUT',
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
        const response = await apiFetch('/backend/api/service-provider-approved-clients.php')

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
        const response = await apiFetch('/backend/api/technicians.php')

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
        email: '',
        first_name: '',
        last_name: '',
        phone: ''
      }
    },

    async createTechnician() {
      if (!this.technicianForm.username || !this.technicianForm.email ||
          !this.technicianForm.first_name || !this.technicianForm.last_name) {
        alert('Please fill in all required fields')
        return
      }

      this.loading = true
      try {
        const response = await apiFetch('/backend/api/technicians.php', {
          method: 'POST',
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
        const response = await apiFetch('/backend/api/technicians.php', {
          method: 'PUT',
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
        const response = await apiFetch(`/backend/api/technicians.php?id=${technician.id}`, {
          method: 'DELETE'
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
    },

    // Job Management Methods
    async loadJobs() {
      try {
        const token = localStorage.getItem('token')
        const params = new URLSearchParams()

        // For technicians, always filter by their own ID
        if (this.userRole === 4) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          params.append('technician_id', payload.user_id)
        } else {
          // For admins, use the filter selections
          if (this.jobFilters.status) params.append('status', this.jobFilters.status)
          if (this.jobFilters.client_id) params.append('client_id', this.jobFilters.client_id)
          if (this.jobFilters.technician_id) params.append('technician_id', this.jobFilters.technician_id)
        }

        const response = await apiFetch(`/backend/api/service-provider-jobs.php?${params}`)

        if (response.ok) {
          const data = await response.json()
          this.jobs = data.jobs
        } else {
          console.error('Failed to load jobs')
          this.jobs = []
        }
      } catch (error) {
        console.error('Failed to load jobs:', error)
        this.jobs = []
      }
    },

    getStatusClass(status) {
      const statusClasses = {
        'Reported': 'reported',
        'Assigned': 'assigned',
        'In Progress': 'in-progress',
        'Completed': 'completed'
      }
      return statusClasses[status] || 'reported'
    },

    async viewJobDetails(job) {
      this.selectedJob = job
      this.showJobDetailsModal = true

      // Load job images if not already loaded
      if (!job.images) {
        try {
          const response = await apiFetch(`/backend/api/job-images.php?job_id=${job.id}`)

          if (response.ok) {
            const data = await response.json()
            this.selectedJob.images = data.images || []
          }
        } catch (error) {
          console.error('Failed to load job images:', error)
          this.selectedJob.images = []
        }
      }
    },

    editJob(job) {
      // Set the job being edited and store original values for comparison
      this.editingJob = { ...job }
      this.originalJobStatus = job.job_status
      this.originalProviderId = job.assigned_provider_id

      // Initialize technician selection with current assigned technician
      this.selectedTechnicianId = job.assigned_technician_id || null

      // Load job images if not already loaded
      if (!job.images) {
        try {
          const response = apiFetch(`/backend/api/job-images.php?job_id=${job.id}`)

          response.then(res => {
            if (res.ok) {
              return res.json()
            }
          }).then(data => {
            this.editingJob.images = data.images || []
          }).catch(error => {
            console.error('Failed to load job images:', error)
            this.editingJob.images = []
          })
        } catch (error) {
          console.error('Failed to load job images:', error)
          this.editingJob.images = []
        }
      } else {
        this.editingJob.images = job.images
      }

      // Refresh approved providers list to ensure it's current
      this.loadApprovedClients().then(() => {
        // Ensure the current assigned provider is still approved
        const isProviderStillApproved = this.approvedClients.some(client => client.id == this.editingJob.assigned_provider_id)
        if (this.editingJob.assigned_provider_id && !isProviderStillApproved) {
          // Provider is no longer approved, clear the assignment
          this.editingJob.assigned_provider_id = null
        }
        this.showEditJobModal = true
      })
    },

    async updateJob() {
      // Validate technician selection for "In Progress" status - only for admins
      if (this.userRole === 3 && this.editingJob.job_status === 'In Progress' && !this.selectedTechnicianId) {
        alert('Please select a technician before setting the job to "In Progress"')
        return
      }

      // Validate technician selection when changing technicians on existing "In Progress" jobs - only for admins
      if (this.userRole === 3 && this.originalJobStatus === 'In Progress' && this.editingJob.job_status === 'In Progress' && !this.selectedTechnicianId) {
        alert('A technician must be assigned for jobs in "In Progress" status')
        return
      }

      try {
        const token = localStorage.getItem('token')
        const updateData = {
          job_id: this.editingJob.id,
          status: this.editingJob.job_status
        }

        // Only include fields that can be edited based on status and role
        if (this.canEditJobDetails(this.editingJob)) {
          // Full edit when status is 'Reported'
          updateData.item_identifier = this.editingJob.item_identifier || null
          updateData.fault_description = this.editingJob.fault_description
          updateData.contact_person = this.editingJob.contact_person || null
        }

        // Include technician notes if they have access (service providers only)
        if (this.userRole === 3 || this.userRole === 4) {
          updateData.technician_notes = this.editingJob.technician_notes || null
        }

        // Status and provider can always be edited (when allowed)
        if (this.editingJob.job_status !== this.originalJobStatus) {
          updateData.job_status = this.editingJob.job_status
        }

        if (this.editingJob.assigned_provider_id !== this.originalProviderId) {
          updateData.assigned_provider_id = this.editingJob.assigned_provider_id || null
        }

        // Include technician assignment if setting to "In Progress" or changing technician on existing "In Progress" jobs
        if (this.editingJob.job_status === 'In Progress' && this.selectedTechnicianId) {
          updateData.technician_id = this.selectedTechnicianId
        } else if (this.originalJobStatus === 'In Progress' && this.selectedTechnicianId && this.selectedTechnicianId !== this.editingJob.assigned_technician_id) {
          // Allow changing technician on jobs that are already "In Progress"
          updateData.technician_id = this.selectedTechnicianId
        }

        // Only proceed if there are changes or new images to upload
        const hasChanges = Object.keys(updateData).length > 1
        const hasNewImages = this.editingImages && this.editingImages.length > 0

        if (!hasChanges && !hasNewImages) {
          alert('No changes to save')
          return
        }

        // Update job details if there are changes
        if (hasChanges) {
          const response = await apiFetch('/backend/api/job-status-update.php', {
            method: 'PUT',
            body: JSON.stringify(updateData)
          })

          if (!response.ok) {
            const errorData = await response.json()
            this.handleError(errorData)
            return
          }
        }

        // Upload additional images if any were selected
        if (hasNewImages) {
          await this.uploadEditJobImages(this.editingJob.id)
        }

        alert('Job updated successfully!')
        this.showEditJobModal = false
        this.loadJobs()
      } catch (error) {
        alert('Failed to update job')
      }
    },

    canEditJobDetails(job) {
      // Only allow editing full details when status is 'Reported'
      return job.job_status === 'Reported'
    },

    handleEditImagesChanged(images) {
      this.editingImages = images
    },

    async uploadEditJobImages(jobId) {
      for (const image of this.editingImages) {
        const formData = new FormData()
        formData.append('job_id', jobId)
        formData.append('image', image.file)

        try {
          const response = await apiFetch('/backend/api/upload-job-image.php', {
            method: 'POST',
            body: formData
          })

          if (response.ok) {
            console.log('Successfully uploaded additional image:', image.name)
          } else {
            const errorData = await response.json()
            console.error('Failed to upload additional image:', image.name, errorData.error)
          }
        } catch (error) {
          console.error('Error uploading additional image:', image.name, error)
        }
      }
    },

    openImageModal(image) {
      this.selectedImage = image
    },

    // Section collapse/expand functionality
    toggleSection(sectionName) {
      this.sectionsExpanded[sectionName] = !this.sectionsExpanded[sectionName]
    },

    // Get available status options based on role and current job status
    getAvailableStatuses(job, userRole) {
      const availableStatuses = []

      if (userRole === 3) { // Service Provider Admin
        // For Quote Requested status, only allow Quote Provided and Declined
        if (job.job_status === 'Quote Requested') {
          availableStatuses.push('Quote Provided', 'Declined')
        } else {
          // For other statuses, show all admin options
          availableStatuses.push('In Progress', 'Quote Provided', 'Repaired', 'Payment Requested', 'Completed')
          // Also allow Declined for jobs that are Assigned
          if (job.job_status === 'Assigned') {
            availableStatuses.unshift('Declined') // Add at beginning
          }
        }
      } else if (userRole === 4) { // Technician
        // For technicians, always show completion options when editing jobs they're assigned to
        // Use originalJobStatus to determine if they can update status
        if (this.originalJobStatus === 'In Progress') {
          availableStatuses.push('Repaired', 'Not repairable')
        }
      } else if (userRole === 2) { // Budget Controller
        availableStatuses.push('Assigned', 'Quote Requested', 'Quote Accepted', 'Completion Confirmed')
      }

      return availableStatuses
    }
  }
}
</script>

<style scoped>
/* Section Headers */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
  cursor: pointer;
}

.section-header h2 {
  margin: 0;
  color: #333;
}

/* Expand Button */
.expand-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  transition: transform 0.3s ease, background-color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.expand-btn:hover {
  background-color: #f0f0f0;
}

.expand-btn.expanded {
  transform: rotate(180deg);
}

.expand-btn .material-icon-sm {
  font-size: 20px;
  color: #666;
}

/* Section Content */
.section-content {
  overflow: hidden;
  transition: all 0.3s ease-in-out;
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
  padding: 5px 10px;
}

.large-modal .modal-content {
  max-width: 900px;
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

/* Form Styles */
.job-form {
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
.form-group select,
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

.form-help {
  display: block;
  margin-top: 3px;
  font-size: 0.8em;
  color: #666;
}

.readonly-field {
  padding: 10px;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #666;
  font-style: italic;
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

/* Job Details Modal Styles */
.job-details-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

.job-info-section {
  margin-bottom: 30px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item span {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.description-section {
  margin-bottom: 20px;
}

.description-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.fault-description {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
}

.contact-section {
  margin-bottom: 20px;
}

.contact-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

/* Images Gallery */
.images-section h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 16px;
}

.no-images {
  text-align: center;
  padding: 40px 20px;
  background: #f8f9fa;
  border-radius: 8px;
  color: #666;
}

.no-images-icon {
  font-size: 3em;
  margin-bottom: 10px;
}

.images-gallery {
  margin-top: 15px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.gallery-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.gallery-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.gallery-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 8px;
  font-size: 11px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.image-filename {
  font-weight: 500;
}

/* Image Modal */
.image-modal-content {
  background: white;
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.image-modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.image-modal-body {
  padding: 20px;
  text-align: center;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.full-size-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 4px;
}

/* Edit Job Modal Styles */
.edit-details-section {
  margin-bottom: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.edit-details-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 16px;
}

.readonly-details-section {
  margin-bottom: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.readonly-details-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 16px;
}

.readonly-info {
  display: grid;
  gap: 10px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row label {
  font-weight: 600;
  color: #666;
  font-size: 14px;
}

.info-row span {
  color: #333;
  font-size: 14px;
}

/* Technician Notes Section */
.technician-notes-section {
  margin-bottom: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.technician-notes-section h4 {
  margin: 0 0 15px 0;
  color: #333;
  font-size: 16px;
}

.technician-notes-content {
  background: white;
  padding: 15px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
  border: 1px solid #e0e0e0;
  white-space: pre-wrap;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .large-modal .modal-content {
    max-width: 95vw;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>

<style scoped>
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

.large-modal .modal-content {
  max-width: 900px;
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

/* Form Styles */
.job-form {
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
.form-group select,
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

.form-help {
  display: block;
  margin-top: 3px;
  font-size: 0.8em;
  color: #666;
}

.readonly-field {
  padding: 10px;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #666;
  font-style: italic;
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

/* Job Details Modal Styles */
.job-details-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

.job-info-section {
  margin-bottom: 30px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item span {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.description-section {
  margin-bottom: 20px;
}

.description-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.fault-description {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
}

.contact-section {
  margin-bottom: 20px;
}

.contact-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

/* Images Gallery */
.images-section h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 16px;
}

.no-images {
  text-align: center;
  padding: 40px 20px;
  background: #f8f9fa;
  border-radius: 8px;
  color: #666;
}

.no-images-icon {
  font-size: 3em;
  margin-bottom: 10px;
}

.images-gallery {
  margin-top: 15px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.gallery-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.gallery-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.gallery-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 8px;
  font-size: 11px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.image-filename {
  font-weight: 500;
}

/* Image Modal */
.image-modal-content {
  background: white;
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.image-modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.image-modal-body {
  padding: 20px;
  text-align: center;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.full-size-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 4px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .large-modal .modal-content {
    max-width: 95vw;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
