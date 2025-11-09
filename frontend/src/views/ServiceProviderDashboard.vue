<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-8">
      <!-- Unified Dashboard Header -->
      <UnifiedDashboardHeader
        :org-name="getOrganizationName()"
        :user-name="getCurrentUserName()"
        :profile-completeness="profileCompleteness"
        :user-role="userRole"
        :role-display-names="roleDisplayNames"
        dashboard-type="service-provider"
        :show-invite-users="userRole === 3"
        @navigate="handleNavigate"
      />
    </div>

    <!-- Administrator Settings Section - Role 3 Only -->
    <div class="admin-settings-container bg-white rounded-xl shadow-lg border border-gray-200 p-0 mb-8" v-if="userRole === 3">
      <div 
        class="admin-section-header rounded-t-xl bg-white rounded-b-none p-6 pb-4"
      >
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-0 pb-0 border-b border-neutral-200" @click="toggleSection('administrator-settings')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded['administrator-settings'] }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-purple-600">admin_panel_settings</span>
              Administrator Settings
            </h2>
          </div>
          <!-- Admin-specific actions: Upgrade and Subscription Plan -->
          <div class="admin-header-actions flex items-center gap-4">
            <span class="text-sm text-gray-600">Current Plan: Basic</span>
            <button @click.stop="handleUpgradeClick" class="btn-filled flex items-center gap-2" v-if="userRole === 3">
              <span class="material-icon-sm">workspace_premium</span>
              Upgrade
            </button>
          </div>
        </div>
      </div>

      <div v-show="sectionsExpanded['administrator-settings']" class="admin-subsections bg-gray-100 rounded-b-xl border-t border-gray-200 p-4 space-y-2">

        <!-- Business Profile Sub-Section -->
        <div class="subsection-container">
          <div @click="toggleSection('profile')" class="subsection-header">
            <div class="subsection-title">
              <span class="material-icon text-blue-600">business</span>
              <h4>Business Profile</h4>
              <span class="completeness-badge">{{ technicianProfileCompleteness }}% Complete</span>
            </div>
            <div class="actions">
              <button @click.stop="showProfileModal = true" class="btn-outlined btn-small">
                <span class="material-icon-sm">edit</span>
                Edit
              </button>
              <button class="expand-btn small" :class="{ expanded: sectionsExpanded.profile }">
                <span class="material-icon-sm">expand_more</span>
              </button>
            </div>
          </div>
          <div v-show="sectionsExpanded.profile" class="subsection-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Basic Info -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-4">
                  <span class="material-icon text-blue-600">business_center</span>
                  <h4 class="font-semibold text-gray-900">Basic Information</h4>
                </div>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-gray-600">Business Name</label>
                    <p class="text-gray-900">{{ profile.name || 'Not set' }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Website</label>
                    <p class="text-gray-900">{{ profile.website || 'Not set' }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Status</label>
                    <span :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      profile.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ profile.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Contact Info -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-4">
                  <span class="material-icon text-green-600">contact_phone</span>
                  <h4 class="font-semibold text-gray-900">Contact Information</h4>
                </div>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-gray-600">Manager</label>
                    <p class="text-gray-900">{{ profile.manager_name || 'Not set' }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Email</label>
                    <p class="text-gray-900">{{ profile.manager_email || 'Not set' }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Phone</label>
                    <p class="text-gray-900">{{ profile.manager_phone || 'Not set' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Address and Description -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg p-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <span class="material-icon text-gray-600">location_on</span>
                    <label class="font-semibold text-gray-900">Business Address</label>
                  </div>
                  <p class="text-gray-700 whitespace-pre-wrap">{{ profile.address || 'No address set' }}</p>
                </div>
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <span class="material-icon text-gray-600">description</span>
                    <label class="font-semibold text-gray-900">Business Description</label>
                  </div>
                  <p class="text-gray-700 line-clamp-3">{{ profile.description || 'No description set' }}</p>
                  </div>
                </div>
              </div>
            </div>

        <!-- Services Offered Sub-Section -->
        <div class="subsection-container">
          <div @click="toggleSection('services')" class="subsection-header">
            <div class="subsection-title">
              <span class="material-icon text-green-600">build</span>
              <h4>Services Offered</h4>
            </div>
            <div class="actions">
              <button @click.stop="showServicesModal = true" class="btn-outlined btn-small">
                <span class="material-icon-sm">settings</span>
                Configure
              </button>
              <button class="expand-btn small" :class="{ expanded: sectionsExpanded.services }">
                <span class="material-icon-sm">expand_more</span>
              </button>
            </div>
          </div>
          <div v-show="sectionsExpanded.services" class="subsection-content">
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
              <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <h4 class="font-semibold text-gray-900">Configured Services ({{ services.length }})</h4>
                  <span class="text-sm text-gray-600">Click "Configure" to manage services</span>
                </div>
              </div>

              <div v-if="services.length === 0" class="p-8 text-center">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-4">
                  <span class="material-icon text-gray-400">build</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Services Configured</h3>
                <p class="text-gray-600 mb-4">Configure your services to start accepting jobs.</p>
                <button @click="showServicesModal = true" class="btn-filled">
                  Configure Services
                </button>
              </div>

              <div v-else class="divide-y divide-gray-200">
                <div v-for="service in services" :key="service.id" class="p-4 hover:bg-gray-50">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: getCategoryColor(service.category) }">
                        <span class="material-icon-sm text-white">{{ getCategoryIcon(service.category) }}</span>
                      </div>
                      <div>
                        <div class="font-medium text-gray-900">{{ service.name }}</div>
                        <div class="text-sm text-gray-600">{{ service.category }}</div>
                      </div>
                    </div>
                    <div class="flex items-center gap-3">
                      <span v-if="service.is_primary" class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                        Primary
                      </span>
                      <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                        Active
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Service Regions Sub-Section -->
        <div class="subsection-container">
          <div @click="toggleSection('regions')" class="subsection-header">
            <div class="subsection-title">
              <span class="material-icon text-orange-600">location_on</span>
              <h4>Service Regions</h4>
            </div>
            <div class="actions">
              <button @click.stop="showRegionsModal = true" class="btn-outlined btn-small">
                <span class="material-icon-sm">settings</span>
                Configure
              </button>
              <button class="expand-btn small" :class="{ expanded: sectionsExpanded.regions }">
                <span class="material-icon-sm">expand_more</span>
              </button>
            </div>
          </div>
          <div v-show="sectionsExpanded.regions" class="subsection-content">
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
              <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <h4 class="font-semibold text-gray-900">Active Regions ({{ regions.length }})</h4>
                  <span class="text-sm text-gray-600">Click "Configure" to manage regions</span>
                </div>
              </div>

              <div v-if="regions.length === 0" class="p-8 text-center">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-4">
                  <span class="material-icon text-gray-400">location_off</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Regions Configured</h3>
                <p class="text-gray-600 mb-4">Configure your service regions to define where you operate.</p>
                <button @click="showRegionsModal = true" class="btn-filled">
                  Configure Regions
                </button>
              </div>

              <div v-else class="divide-y divide-gray-200">
                <div v-for="region in regions" :key="region.id" class="p-4 hover:bg-gray-50">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-orange-500">
                        <span class="material-icon-sm text-white">location_on</span>
                      </div>
                      <div>
                        <div class="font-medium text-gray-900">{{ region.name }}</div>
                        <div class="text-sm text-gray-600">{{ region.code }}</div>
                      </div>
                    </div>
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                      Active
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Sub-Section -->
        <div class="subsection-container">
          <div @click="toggleSection('technicians')" class="subsection-header">
            <div class="subsection-title">
              <span class="material-icon text-blue-600">group</span>
              <h4>Users</h4>
            </div>
            <div class="actions">
              <button @click.stop="openAddTechnicianModal" class="btn-outlined btn-small">
                <span class="material-icon-sm">person_add</span>
                Add User
              </button>
              <button class="expand-btn small" :class="{ expanded: sectionsExpanded.technicians }">
                <span class="material-icon-sm">expand_more</span>
              </button>
            </div>
          </div>
          <div v-show="sectionsExpanded.technicians" class="subsection-content">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="technician in technicians" :key="technician.id" class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                <div class="flex justify-between items-center p-4 bg-gray-50 border-b border-gray-200">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="technician.role_id === 3 ? 'bg-blue-600' : 'bg-purple-600'">
                      <span class="material-icon text-white">{{
                        technician && technician.full_name ?
                          technician.full_name.charAt(0).toUpperCase() :
                          technician && technician.username ?
                          technician.username.charAt(0).toUpperCase() :
                          'T'
                      }}</span>
                    </div>
                    <div>
                      <h3 class="font-semibold text-gray-900">{{ technician.full_name || 'Unnamed User' }}</h3>
                      <div class="flex gap-2">
                        <span :class="[
                          'px-2 py-1 rounded-full text-xs font-medium',
                          technician.role_id === 3 ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                        ]">
                          {{ roleDisplayNames && roleDisplayNames[technician.role_id] ? roleDisplayNames[technician.role_id] : getFallbackRoleName(technician.role_id) }}
                        </span>
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
        </div>
      </div>
    </div>

      <!-- Jobs Management Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="sectionsExpanded.jobs = !sectionsExpanded.jobs" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.jobs }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">work</span>
              Job Management
              <span v-if="jobs?.length" class="text-sm font-normal text-blue-600">({{ jobs.length }})</span>
            </h2>
          </div>
          <button @click.stop="loadJobs()" class="btn-outlined flex items-center gap-2">
            <span class="material-icon-sm">refresh</span>
            Refresh
          </button>
        </div>

        <div v-show="sectionsExpanded.jobs" class="section-content transition-all duration-300 ease-in-out">
          <JobManagementSectionSP
            :jobs="jobs"
            :job-filters="jobFilters"
            :approved-clients="approvedClients"
            :technicians="technicians"
            :user-role="userRole"
            @update-job-filters="jobFilters = $event; loadJobs()"
            @refresh-jobs="loadJobs"
            @view-job-details="selectedJob = $event; showJobDetailsModal = true"
            @edit-job="handleEditJob"
            @toggle-archive-job="toggleArchiveJob"
          />
        </div>
      </div>

      <!-- Quote Management Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="sectionsExpanded.quotes = !sectionsExpanded.quotes" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.quotes }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-green-600">request_quote</span>
              Quote Management
            </h2>
          </div>
          <button @click.stop="loadQuotes" class="btn-outlined btn-small flex items-center gap-2">
            <span class="material-icon-sm">refresh</span>
            Refresh Quotes
          </button>
        </div>

        <div v-show="sectionsExpanded.quotes" class="section-content transition-all duration-300 ease-in-out">
          <!-- Quote Filters -->
          <div class="quote-filters flex flex-wrap gap-4 mb-6 p-4 bg-green-50 rounded-lg">
            <div class="filter-group min-w-40">
              <label for="quote-status-filter" class="form-label mb-1">Quote Status:</label>
              <select id="quote-status-filter" v-model="quoteFilters.status" @change="loadQuotes" class="form-input">
                <option value="">All Quotes</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <div class="filter-group min-w-40">
              <label for="quote-job-filter" class="form-label mb-1">Job:</label>
              <select id="quote-job-filter" v-model="quoteFilters.job_id" @change="loadQuotes" class="form-input">
                <option value="">All Jobs</option>
                <option v-for="job in quoteJobs" :key="job.id" :value="job.id">
                  {{ job.item_identifier || 'Job #' + job.id }}
                </option>
              </select>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="quotes === null" class="loading-state text-center py-16">
            <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-green-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-body-large text-on-surface-variant">Loading quotes...</p>
          </div>

          <!-- Quotes Grid -->
          <div v-else-if="quotes && quotes.length > 0" class="quotes-grid grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="quote in quotes" :key="quote.id" class="quote-card card overflow-hidden">
              <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="quote-status uppercase">
                  <span class="status-badge" :class="getQuoteStatusClass(quote.status)">
                    {{ quote.status }}
                  </span>
                </div>

                <div class="quote-actions flex gap-2">
                  <button @click="viewQuoteDetails(quote)" class="btn-outlined btn-small">
                    <span class="material-icon-sm">visibility</span>
                    View
                  </button>
                  <button v-if="quote.status === 'draft'" @click="editQuote(quote)" class="btn-outlined btn-small">
                    <span class="material-icon-sm">edit</span>
                    Edit
                  </button>
                  <button v-if="quote.status === 'draft'" @click="submitQuote(quote)" class="btn-filled btn-small">
                    <span class="material-icon-sm">send</span>
                    Submit
                  </button>
                </div>
              </div>

              <div class="quote-content card-content">
                <h3 class="quote-title text-title-medium text-on-surface mb-2">
                  {{ quote.item_identifier || 'Job #' + quote.job_id }}
                </h3>
                <p class="quote-description text-body-medium text-on-surface-variant mb-2 line-clamp-2">
                  {{ quote.fault_description }}
                </p>

                <div class="quote-amount text-lg font-bold text-green-600 mb-2">
                  {{ formatCurrency(quote.quotation_amount) }}
                </div>

                <div class="quote-meta space-y-1 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Client:</span>
                    <span class="font-medium">{{ quote.client_name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Location:</span>
                    <span class="font-medium">{{ quote.location_name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Valid Until:</span>
                    <span class="font-medium">{{ formatDate(quote.valid_until) }}</span>
                  </div>
                  <div v-if="quote.submitted_at" class="flex justify-between">
                    <span class="text-gray-600">Submitted:</span>
                    <span class="font-medium">{{ formatDate(quote.submitted_at) }}</span>
                  </div>
                </div>
              </div>

              <div class="quote-footer card-footer">
                <div class="quote-description-preview text-sm text-gray-600 italic">
                  "{{ quote.quotation_description?.substring(0, 100) }}{{ quote.quotation_description?.length > 100 ? '...' : '' }}"
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-16">
            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
              <span class="material-icon text-gray-400">request_quote</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Quotes Yet</h3>
            <p class="text-gray-600">Quotes you create for jobs will appear here.</p>
          </div>
        </div>
      </div>

      <!-- Approved Clients Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
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
    </div>

    <!-- Edit Business Profile Modal -->
    <div v-if="showProfileModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="showProfileModal = false"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-blue-600">business</span>
            Edit Business Profile
          </h3>
          <button @click="showProfileModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Form -->
        <form @submit.prevent="updateProfile" class="p-6 space-y-8 overflow-y-auto max-h-[calc(90vh-140px)]">
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
                  v-model="editForm.name"
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
                  v-model="editForm.website"
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
                v-model="editForm.address"
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
                v-model="editForm.description"
                rows="4"
                class="form-input"
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
                  v-model="editForm.manager_name"
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
                  v-model="editForm.manager_email"
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
                v-model="editForm.manager_phone"
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
                  v-model="editForm.vat_number"
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
                  v-model="editForm.business_registration_number"
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
                :value="editForm.is_active ? 'Active' : 'Inactive'"
                class="form-input"
                readonly
              >
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="showProfileModal = false"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Canceling...' : 'Cancel' }}
            </button>
            <button
              type="submit"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Updating...' : 'Update Profile' }}
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
            <span class="material-icon text-blue-600">settings</span>
            Configure Services
          </h3>
          <button @click="showServicesModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">
          <!-- Search and Filter -->
          <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <div class="flex-1">
              <input
                type="text"
                v-model="searchTerm"
                placeholder="Search services..."
                class="form-input"
                @input="debouncedSearch"
              >
            </div>
            <div class="flex gap-2">
              <button @click="expandAllCategories" class="btn-outlined btn-small">
                <span class="material-icon-sm">expand_more</span>
                Expand All
              </button>
              <button @click="collapseAllCategories" class="btn-outlined btn-small">
                <span class="material-icon-sm">expand_less</span>
                Collapse All
              </button>
            </div>
          </div>

          <!-- Services by Categories -->
          <div v-for="category in getFilteredCategories()" :key="category" class="bg-gray-50 rounded-lg mb-4 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer" @click="toggleCategoryExpansion(category)">
              <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-3">
                <button class="small-category-btn" :class="{ expanded: expandedCategories[category] }">
                  <span class="material-icon-sm">expand_more</span>
                </button>
                <span>{{ category }}</span>
                <span class="text-sm text-gray-600 bg-gray-200 px-2 py-1 rounded-full">
                  {{ getServicesByCategory(category).length }}
                </span>
              </h4>
              <div class="flex items-center gap-3">
                <span v-if="isCategoryFullySelected(category)" class="text-sm text-green-600 flex items-center gap-1">
                  <span class="material-icon-sm">check_circle</span>
                  All Selected
                </span>
                <span v-else-if="isCategoryPartiallySelected(category)" class="text-sm text-orange-600 flex items-center gap-1">
                  <span class="material-icon-sm">radio_button_partial</span>
                  Some Selected
                </span>
                <span v-else class="text-sm text-gray-600 flex items-center gap-1">
                  <span class="material-icon-sm">radio_button_unchecked</span>
                  None Selected
                </span>
                <div class="flex gap-2">
                  <button @click.stop="selectAllInCategory(category)" class="btn-outlined btn-very-small">Select All</button>
                  <button @click.stop="deselectAllInCategory(category)" class="btn-outlined btn-very-small">Deselect All</button>
                </div>
              </div>
            </div>

            <div v-show="expandedCategories[category]" class="p-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="service in getServicesByCategory(category)"
                  :key="service.id"
                  class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                  @click="selectedServices.includes(service.id) ? selectedServices.splice(selectedServices.indexOf(service.id), 1) : selectedServices.push(service.id)"
                >
                  <input
                    type="checkbox"
                    :checked="selectedServices.includes(service.id)"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                    @click.stop
                    @change="selectedServices.includes(service.id) ? selectedServices.splice(selectedServices.indexOf(service.id), 1) : selectedServices.push(service.id)"
                  >
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: getCategoryColor(service.category) }">
                    <span class="material-icon-sm text-white">{{ getCategoryIcon(service.category) }}</span>
                  </div>
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ service.name }}</div>
                    <div class="text-sm text-gray-600">{{ service.category }}</div>
                  </div>
                  <div v-if="primaryServiceId === service.id" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-medium">
                    Primary
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Primary Service Selection -->
          <div class="bg-blue-50 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2 mb-4">
              <span class="material-icon-sm text-blue-600">star</span>
              Set Primary Service
            </h4>
            <p class="text-sm text-gray-700 mb-4">
              Your primary service will be highlighted to clients. You can change this later.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              <button
                v-for="serviceId in selectedServices"
                :key="serviceId"
                @click="setPrimaryService(serviceId)"
                :class="[
                  'p-3 rounded-lg border text-left transition-all',
                  primaryServiceId === serviceId
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white border-gray-300 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: primaryServiceId === serviceId ? '#ffffff33' : getCategoryColor(getServiceName(serviceId).split(' - ')[1] || 'Other') }">
                    <span class="material-icon-sm" :class="primaryServiceId === serviceId ? 'text-white' : 'text-white'">
                      {{ getCategoryIcon(getServiceName(serviceId).split(' - ')[1] || 'Other') }}
                    </span>
                  </div>
                  <div>
                    <div class="font-medium" :class="primaryServiceId === serviceId ? 'text-white' : 'text-gray-900'">
                      {{ getServiceName(serviceId) }}
                    </div>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
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
            <span class="material-icon text-orange-600">location_on</span>
            Configure Service Regions
          </h3>
          <button @click="showRegionsModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">
          <!-- Search Input -->
          <div class="mb-6">
            <input
              type="text"
              v-model="regionSearchTerm"
              placeholder="Search regions..."
              class="form-input"
            >
          </div>

          <!-- Regions List -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="region in filteredRegions"
              :key="region.id"
              class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
              @click="toggleRegionSelection(region.id)"
            >
              <input
                type="checkbox"
                :checked="selectedRegions.includes(region.id)"
                class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500"
                @click.stop
                @change="toggleRegionSelection(region.id)"
              >
              <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-orange-500">
                <span class="material-icon-sm text-white">location_on</span>
              </div>
              <div class="flex-1">
                <div class="font-medium text-gray-900">{{ region.name }}</div>
                <div class="text-sm text-gray-600">{{ region.code }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 p-6">
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

      <!-- Email Row -->
      <div class="grid grid-cols-1 gap-4">
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

          <!-- Email AND Phone Row -->
          <div class="grid grid-cols-2 gap-4">
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
          </div>

          <!-- Role and Status Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="userRole === 3">
              <label for="edit_role" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">admin_panel_settings</span>
                Role
              </label>
              <select
                id="edit_role"
                v-model="technicianForm.role_id"
                class="form-input"
                :disabled="editingTechnician && editingTechnician.id === currentUserId"
              >
                <option
                  v-for="(name, id) in roleDisplayNames"
                  :key="id"
                  :value="parseInt(id)"
                  :disabled="editingTechnician && editingTechnician.id === currentUserId"
                >
                  {{ name }}
                </option>
              </select>
              <small v-if="editingTechnician && editingTechnician.id === currentUserId" class="form-help text-sm text-gray-500 mt-1">
                You cannot change your own role
              </small>
            </div>
            <div v-if="userRole === 3">
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
                <a :href="`https://maps.google.com/maps?q=${encodeURIComponent(selectedJob.location_coordinates || selectedJob.location_name)}`"
                   target="_blank"
                   class="location-link text-blue-600 hover:text-blue-800 underline">
                  {{ selectedJob.location_name }}
                </a>
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
              <label>Service Description:</label>
              <p class="fault-description">{{ selectedJob.fault_description }}</p>
            </div>

            <div v-if="selectedJob.contact_person" class="contact-section">
              <label>Contact Person:</label>
              <span>{{ selectedJob.contact_person }}</span>
            </div>

            <!-- Site Information -->
            <div v-if="selectedJob.location_access_rules || selectedJob.location_access_instructions" class="access-section">
              <!-- Site Information Link -->
              <div v-if="selectedJob.location_access_rules" class="info-item">
                <label>Site Information:</label>
                <a :href="selectedJob.location_access_rules"
                   target="_blank"
                   class="access-link text-green-600 hover:text-green-800 underline">
                  📋 Open Site Information
                </a>
              </div>

              <!-- Access Instructions -->
              <div v-if="selectedJob.location_access_instructions" class="info-item">
                <label>Access Instructions:</label>
                <div class="access-instructions-content">{{ selectedJob.location_access_instructions }}</div>
              </div>
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
                    <img :src="generateImageUrl(image)"
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



    <!-- Edit Quote Modal -->
    <div v-if="showEditQuoteModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="closeEditQuoteModal"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-green-600">edit</span>
            Edit Quote: {{ editingQuote?.item_identifier || 'Job #' + editingQuote?.job_id }}
          </h3>
          <button @click="closeEditQuoteModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Form -->
        <form @submit.prevent="updateQuote" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <!-- Quote Details -->
          <div class="bg-gray-50 rounded-lg p-6 space-y-6">
            <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2">
              <span class="material-icon-sm text-green-600">request_quote</span>
              Quote Information
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label for="quote-amount" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">attach_money</span>
                  Quote Amount (R) *
                </label>
                <input
                  type="number"
                  id="quote-amount"
                  v-model="quoteForm.quotation_amount"
                  required
                  step="0.01"
                  min="0"
                  class="form-input"
                  placeholder="0.00"
                >
              </div>
              <div class="space-y-2">
                <label for="quote-valid-until" class="form-label flex items-center gap-2">
                  <span class="material-icon-sm text-gray-500">event_available</span>
                  Valid Until
                </label>
                <input
                  type="date"
                  id="quote-valid-until"
                  v-model="quoteForm.valid_until"
                  class="form-input"
                >
              </div>
            </div>

            <div class="space-y-2">
              <label for="quote-description" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">description</span>
                Quote Description *
              </label>
              <textarea
                id="quote-description"
                v-model="quoteForm.quotation_description"
                required
                rows="4"
                class="form-input resize-none"
                placeholder="Detailed description of work to be performed and quote breakdown..."
              ></textarea>
            </div>

            <div class="space-y-2">
              <label for="quote-document-url" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">link</span>
                Quote Document URL
              </label>
              <input
                type="url"
                id="quote-document-url"
                v-model="quoteForm.quotation_document_url"
                class="form-input"
                placeholder="https://example.com/quote-document.pdf"
              >
              <small class="form-help text-sm text-gray-600">Optional: Link to detailed quote document (PDF, etc.)</small>
            </div>
          </div>

          <!-- PDF Document Upload Section -->
          <div class="bg-green-50 rounded-lg p-6 space-y-6">
            <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2">
              <span class="material-icon-sm text-green-600">upload_file</span>
              Upload Quote PDF Document
            </h4>

            <div class="space-y-2">
              <label for="quote-pdf-upload" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">picture_as_pdf</span>
                PDF Document Upload
              </label>
              <div class="flex items-center gap-3">
                <input
                  type="file"
                  id="quote-pdf-upload"
                  ref="pdfUploadInput"
                  accept=".pdf"
                  @change="handlePdfUpload"
                  class="form-input flex-1"
                  :disabled="uploadingPdf"
                >
                <button
                  type="button"
                  @click="uploadPdfDocument"
                  :disabled="uploadingPdf || !pdfToUpload"
                  class="btn-primary flex items-center gap-2"
                >
                  <span v-if="uploadingPdf" class="material-icon-sm animate-spin">refresh</span>
                  <span v-else class="material-icon-sm">upload</span>
                  {{ uploadingPdf ? 'Uploading...' : 'Upload PDF' }}
                </button>
              </div>

              <!-- Upload Progress -->
              <div v-if="uploadingPdf" class="space-y-2">
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-green-600 h-2 rounded-full transition-all" :style="{ width: '50%' }"></div>
                </div>
                <p class="text-sm text-gray-600">Uploading PDF document...</p>
              </div>

              <!-- Uploaded Document Display -->
              <div v-if="uploadedPdfPath" class="space-y-2">
                <div class="flex items-center gap-2 p-3 bg-green-100 border border-green-300 rounded-lg">
                  <span class="material-icon-sm text-green-600">check_circle</span>
                  <div class="flex-1">
                    <p class="font-medium text-green-800">{{ pdfDisplayName }}</p>
                    <p class="text-sm text-green-600">PDF uploaded successfully</p>
                  </div>
                  <a
                    :href="getPdfDownloadUrl(uploadedPdfPath)"
                    target="_blank"
                    class="btn-outlined btn-small flex items-center gap-1"
                  >
                    <span class="material-icon-sm">download</span>
                    View PDF
                  </a>
                </div>
              </div>

              <small class="form-help text-sm text-gray-600">
                Upload a PDF quote document (max 5MB). Alternatively, provide a URL above.
                Both options support quote document delivery. PDF uploads are stored securely and served by our system.
              </small>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="closeEditQuoteModal"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">close</span>
              {{ loading ? 'Canceling...' : 'Cancel' }}
            </button>
            <button
              type="submit"
              class="btn-filled flex items-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Updating...' : 'Update Quote' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Job Modal -->
    <EditJobModal
      v-if="editingJobForModal"
      :job="editingJobForModal"
      :user-role="userRole"
      :technicians="technicians"
      :current-user-id="currentUserId"
      @close="editingJobForModal = null"
      @job-updated="handleJobUpdated"
    />

    <!-- Quote Details Modal -->
    <div v-if="showQuoteDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="showQuoteDetailsModal = false"></div>

      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
            <span class="material-icon text-green-600">request_quote</span>
            Quote Details: {{ selectedQuote?.item_identifier || 'Job #' + selectedQuote?.job_id }}
          </h3>
          <button @click="showQuoteDetailsModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>

        <!-- Content -->
        <div v-if="selectedQuote" class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">
          <!-- Quote Status -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="status-badge" :class="getQuoteStatusClass(selectedQuote.status)">
                {{ selectedQuote.status }}
              </span>
              <span class="text-sm text-gray-600">
                Created {{ formatDate(selectedQuote.created_at) }}
              </span>
            </div>
            <button @click="showQuoteDetailsModal = false" class="btn-filled">
              Close
            </button>
          </div>

          <!-- Quote Information Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
              <!-- Amount and Validity -->
              <div class="bg-gray-50 rounded-lg p-6">
                <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2 mb-4">
                  <span class="material-icon-sm text-green-600">attach_money</span>
                  Quote Amount
                </h4>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-gray-600">Amount:</label>
                    <p class="text-2xl font-bold text-green-600">{{ formatCurrency(selectedQuote.quotation_amount) }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Valid Until:</label>
                    <p class="text-base text-gray-900">{{ formatDate(selectedQuote.valid_until) }}</p>
                  </div>
                  <div v-if="selectedQuote.submitted_at">
                    <label class="text-sm font-medium text-gray-600">Submitted:</label>
                    <p class="text-base text-gray-900">{{ formatDate(selectedQuote.submitted_at) }}</p>
                  </div>
                </div>
              </div>

              <!-- Client Information -->
              <div class="bg-blue-50 rounded-lg p-6">
                <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2 mb-4">
                  <span class="material-icon-sm text-blue-600">business</span>
                  Client Information
                </h4>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-gray-600">Client:</label>
                    <p class="text-base text-gray-900">{{ selectedQuote.client_name }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Location:</label>
                    <p class="text-base text-gray-900">{{ selectedQuote.location_name }}</p>
                  </div>
                  <div v-if="selectedQuote.client_email">
                    <label class="text-sm font-medium text-gray-600">Email:</label>
                    <p class="text-base text-gray-900">{{ selectedQuote.client_email }}</p>
                  </div>
                  <div v-if="selectedQuote.client_phone">
                    <label class="text-sm font-medium text-gray-600">Phone:</label>
                    <p class="text-base text-gray-900">{{ selectedQuote.client_phone }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
              <!-- Job Information -->
              <div class="bg-purple-50 rounded-lg p-6">
                <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2 mb-4">
                  <span class="material-icon-sm text-purple-600">work</span>
                  Job Information
                </h4>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-gray-600">Job:</label>
                    <p class="text-base text-gray-900">{{ selectedQuote.item_identifier || 'Job #' + selectedQuote.job_id }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-600">Description:</label>
                    <p class="text-base text-gray-900">{{ selectedQuote.fault_description }}</p>
                  </div>
                  <div class="pt-3 border-t border-purple-200">
                    <a :href="`/service-provider/jobs/${selectedQuote.job_id}`" class="btn-filled btn-small inline-flex items-center gap-2">
                      <span class="material-icon-sm">arrow_forward</span>
                      View Full Job Details
                    </a>
                  </div>
                </div>
              </div>

              <!-- Document Links -->
              <div class="bg-green-50 rounded-lg p-6">
                <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2 mb-4">
                  <span class="material-icon-sm text-green-600">description</span>
                  Documents & Links
                </h4>
                <div class="space-y-3">
                  <div v-if="selectedQuote.quotation_document_url">
                    <label class="text-sm font-medium text-gray-600">Quote Document:</label>
                    <a :href="getPdfDownloadUrl(selectedQuote.quotation_document_url)" target="_blank" class="flex items-center gap-2 text-blue-600 hover:text-blue-800 underline">
                      <span class="material-icon-sm">picture_as_pdf</span>
                      Open PDF Document
                    </a>
                  </div>
                  <div v-else>
                    <p class="text-sm text-gray-600">No PDF document attached</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quote Description -->
          <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2 mb-4">
              <span class="material-icon-sm text-orange-600">description</span>
              Quote Description & Breakdown
            </h4>
            <div class="bg-white rounded-lg p-4 border border-gray-200">
              <p class="text-gray-900 whitespace-pre-wrap">{{ selectedQuote.quotation_description || 'No description provided' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image Modal for Full Size View -->
    <div v-if="selectedImage" class="modal-overlay" @click="selectedImage = null">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedImage.original_filename }}</h3>
          <button @click="selectedImage = null" class="close-btn">&times;</button>
        </div>
        <div class="image-modal-body">
          <img :src="generateImageUrl(selectedImage)"
               :alt="selectedImage.original_filename"
               class="full-size-image">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch, loadRoleSettings } from '@/utils/api.js'
import UserIdentityBar from '@/components/shared/UserIdentityBar.vue'
import JobManagementSectionSP from '@/components/dashboard/JobManagementSectionSP.vue'
import BusinessProfileSectionSP from '@/components/dashboard/BusinessProfileSectionSP.vue'
import TechnicianManagementSection from '@/components/dashboard/TechnicianManagementSection.vue'
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import EditJobModal from '@/components/modals/EditJobModal.vue'
import UnifiedDashboardHeader from '@/components/dashboard/UnifiedDashboardHeader.vue'

export default {
  name: 'ServiceProviderDashboard',
  components: {
    UserIdentityBar,
    JobManagementSectionSP,
    BusinessProfileSectionSP,
    TechnicianManagementSection,
    CollapsibleSection,
    LoadingState,
    StatusBadge,
    EditJobModal,
    UnifiedDashboardHeader
  },
  computed: {
    currentTechnician() {
      if (this.userRole !== 4 || !this.technicians || !this.currentUserId) return null
      return this.technicians.find(t => t.id === this.currentUserId) || null
    },

    technicianProfileCompleteness() {
      if (!this.currentTechnician) return 0

      let completeness = 0
      const fields = ['first_name', 'last_name', 'email', 'phone']

      fields.forEach(field => {
        if (this.currentTechnician[field] && this.currentTechnician[field].trim()) {
          completeness += 25
        }
      })

      return Math.min(completeness, 100)
    },

    currentUserId() {
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.user_id
        } catch (error) {
          console.error('Error parsing token:', error)
          return null
        }
      }
      return null
    },
    filteredRegions() {
      if (!this.availableRegions) return [];
      if (!this.regionSearchTerm) return this.availableRegions;
      const searchTerm = this.regionSearchTerm.toLowerCase();
      return this.availableRegions.filter(region =>
        region.name.toLowerCase().includes(searchTerm) ||
        region.code.toLowerCase().includes(searchTerm)
      );
    }
  },
  data() {
    return {
      roleDisplayNames: {}, // Add this for role settings
      profile: {},
      subscription: null,
      pricing: null,
      limits: null,
      currentUsage: null,
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
      showTechnicianProfileModal: false,
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
      editingJobForModal: null, // For the new EditJobModal component
      // Services modal additional data
      searchTerm: '',
    selectedCategoryFilter: '',
    expandedCategories: {},
    searchTimeout: null, // For debounced search
    regionSearchTerm: '',
    jobFilters: {
      status: '',
      archive_status: 'active', // Default to active jobs for service providers
      client_id: '',
      technician_id: ''
    },
    // Section collapse/expand state
    sectionsExpanded: {
      'administrator-settings': false, // Admin settings container collapsed by default
      profile: false, // Profile overview collapsed by default
      services: false, // Services collapsed by default
      regions: false, // Regions collapsed by default
      technicians: false, // Technicians collapsed by default
      clients: false, // Clients collapsed by default
      jobs: true, // Jobs section expanded by default
      quotes: false // Quote management collapsed by default
    },
    // Quote Management Data
    quotes: [],
    quoteJobs: [],
    quoteFilters: {
      status: '',
      job_id: ''
    },
    editingQuote: null,
    showEditQuoteModal: false,
    showCreateQuoteModal: false,
    showQuoteDetailsModal: false,
    selectedQuote: null,
    quoteForm: {
      job_id: '',
      quotation_amount: '',
      quotation_description: '',
      quotation_document_url: '',
      valid_until: ''
    },
    // PDF Upload Data
    pdfToUpload: null,
    uploadingPdf: false,
    uploadedPdfPath: null,
    pdfDisplayName: '',
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

  async mounted() {
    console.log('🎯 mounted: Starting dashboard initialization')
    await this.getUserRole()
    console.log('🎯 mounted: getUserRole completed, userRole =', this.userRole)

    await this.loadRoleSettings()
    await this.loadProfile()
    await this.loadAvailableOptions()
    await this.loadApprovedClients()
    await this.loadSubscription()
    await this.loadJobs()

    // Admin users (role 3) need to load technicians to assign jobs, and profile visibility
    // Technicians (role 4) need to load technicians only for their own profile
    if (this.userRole === 3 || this.userRole === 4) {
      console.log('🎯 mounted: Loading technicians for role', this.userRole)
      await this.loadTechnicians()
    }

    // Load quote jobs and quotes for service provider admins
    if (this.userRole === 3) {
      console.log('🎯 mounted: Loading quotes for service provider admin (role 3)')
      await Promise.all([
        this.loadQuoteJobs(),
        this.loadQuotes()
      ])
      console.log('🎯 mounted: Finished loading quotes and quote jobs')
    } else {
      console.log('🎯 mounted: Not loading quotes, userRole is', this.userRole)
    }
    console.log('🎯 mounted: Dashboard initialization complete')
  },
  methods: {
    async loadRoleSettings() {
      try {
        const settings = await loadRoleSettings()
        this.roleDisplayNames = settings || {}
      } catch (error) {
        console.warn('Failed to load role settings:', error)
        this.roleDisplayNames = {}
      }
    },

    // Generate authenticated image URL
    generateImageUrl(image) {
      const token = localStorage.getItem('token')
      if (!token) {
        console.warn('No JWT token found for image access')
        return ''
      }
      return `/backend/api/serve-image.php?filename=${image.filename}&token=${encodeURIComponent(token)}`
    },

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

    toggleSection(sectionName) {
      // In Vue 3, direct assignment works for reactive object properties
      const wasExpanded = this.sectionsExpanded[sectionName]
      this.sectionsExpanded[sectionName] = !wasExpanded

      // Load quotes when the quotes section is expanded
      if (sectionName === 'quotes' && !wasExpanded && this.userRole === 3) {
        this.loadQuotes()
      }
    },

    async loadSubscription() {
      try {
        // TODO: Implement subscription loading if needed
        // For now, this is a placeholder to prevent console errors
        // The component has subscription/usage data but may not need immediate loading
        console.log('Subscription loading placeholder - implement if needed')
      } catch (error) {
        console.warn('Failed to load subscription data:', error)
        // Don't alert user, just silently fail as this might not be critical
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

    openImageModal(image) {
      this.selectedImage = image
    },

    getOrganizationName() {
      return this.profile?.name || 'Service Provider'
    },

    getFallbackRoleName(roleId) {
      switch (roleId) {
        case 3:
          return 'Service Provider Admin'
        case 4:
          return 'Service Provider Technician'
        default:
          return `Role ${roleId}`
      }
    },



    getRoleBadgeClass(role) {
      return role === 3 ? 'role-admin' : 'role-technician'
    },

getCurrentUserName() {
  // Get current user name from localStorage, technicians array, or user data
  try {
    // Get current user ID from token
    const currentUserId = JSON.parse(atob(localStorage.getItem('token').split('.')[1].replace(/-/g, '+').replace(/_/g, '/'))).user_id

    // First check if current user is in the technicians array (which now includes admins and technicians)
    if (this.technicians) {
      const currentUser = this.technicians.find(t => t.id == currentUserId)
      if (currentUser) {
        if (currentUser.first_name && currentUser.last_name) {
          return `${currentUser.first_name} ${currentUser.last_name}`
        }
        return currentUser.full_name || currentUser.username.replace(/@.*/, '') || 'User'
      }
    }

    // For service provider admins, try to get name from profile data (business profile)
    if (this.userRole === 3 && this.profile) {
      if (this.profile.manager_name) {
        return this.profile.manager_name
      }
    }

    // Fallback: try to get from user data cache in localStorage
    const userData = localStorage.getItem('user')
    if (userData) {
      const user = JSON.parse(userData)
      // If we have first_name and last_name
      if (user.first_name && user.last_name) {
        return `${user.first_name} ${user.last_name}`
      }
      // If it's a client user or other, return username
      return user.username || 'User'
    }

    // Last fallback
    return this.userRole === 3 ? 'Administrator' : this.userRole === 4 ? 'Technician' : 'User'
  } catch (error) {
    console.error('Error getting current user name:', error)
    return 'User'
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

    toggleRegionSelection(regionId) {
      const index = this.selectedRegions.indexOf(regionId);
      if (index > -1) {
        this.selectedRegions.splice(index, 1);
      } else {
        this.selectedRegions.push(regionId);
      }
    },

    signOut() {
      localStorage.removeItem('token')
      this.$router.push('/')
    },

    getUserRole() {
      console.log('🎯 getUserRole: Starting role extraction')
      const token = localStorage.getItem('token')
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          this.userRole = payload.role_id
          console.log('🎯 getUserRole: extracted role_id =', payload.role_id)
        } catch (error) {
          console.error('🎯 getUserRole: Error parsing token:', error)
          this.userRole = null
        }
      } else {
        console.log('🎯 getUserRole: No token found')
        this.userRole = null
      }
      console.log('🎯 getUserRole: final userRole =', this.userRole)
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
      if (!this.technicianForm.email ||
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
        is_active: technician.is_active,
        role_id: technician.role_id || 4 // Default to technician role
      }
      this.showEditTechnicianModal = true
    },

    closeEditTechnicianModal() {
      this.showEditTechnicianModal = false
      this.editingTechnician = null
      this.technicianForm = {
        username: '',
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
      console.log('🏗️ loadJobs: Starting job loading process')

      try {
        const token = localStorage.getItem('token')
        const params = new URLSearchParams()

        // For technicians, always filter by their own ID
        if (this.userRole === 4) {
          console.log('🏗️ loadJobs: Loading as technician (role 4)')
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          params.append('technician_id', payload.user_id)
          console.log('🏗️ loadJobs: Technician ID:', payload.user_id)
        } else {
          console.log('🏗️ loadJobs: Loading as service provider admin (role 3)')
        // For admins, use the filter selections
        if (this.jobFilters.status) params.append('status', this.jobFilters.status)
        if (this.jobFilters.archive_status) params.append('archive_status', this.jobFilters.archive_status)
        if (this.jobFilters.client_id) params.append('client_id', this.jobFilters.client_id)
        if (this.jobFilters.technician_id) params.append('technician_id', this.jobFilters.technician_id)
        }

        const apiUrl = `/backend/api/service-provider-jobs.php?${params}`
        console.log('🏗️ loadJobs: Fetching from:', apiUrl)
        console.log('🏗️ loadJobs: Current filters:', this.jobFilters)

        const response = await apiFetch(apiUrl)

        console.log('🏗️ loadJobs: Response received, ok:', response.ok)
        console.log('🏗️ loadJobs: Response status:', response.status)

        if (response.ok) {
          const data = await response.json()
          console.log('🏗️ loadJobs: Response data:', data)
          console.log('🏗️ loadJobs: Jobs found:', data.jobs?.length || 0)

          if (data.jobs && Array.isArray(data.jobs)) {
            this.jobs = data.jobs
            console.log('🏗️ loadJobs: Successfully loaded', this.jobs.length, 'jobs')
          } else {
            console.warn('🏗️ loadJobs: No jobs array in response, setting jobs to empty array')
            this.jobs = []
          }
        } else {
          const errorText = await response.text()
          console.error('🏗️ loadJobs: API call failed with status:', response.status)
          console.error('🏗️ loadJobs: Error response:', errorText)
          alert(`Failed to load jobs: ${response.status} - ${errorText}`)
          this.jobs = []
        }
      } catch (error) {
        console.error('🏗️ loadJobs: Exception occurred:', error)
        console.error('🏗️ loadJobs: Full error details:', error.stack)
        alert(`Failed to load jobs: ${error.message}`)
        this.jobs = []
      }
    },

    getStatusClass(status) {
      const statusClasses = {
        'Reported': 'reported',
        'Assigned': 'assigned',
        'In Progress': 'in-progress',
        'Completed': 'completed',
        'Confirmed': 'confirmed',
        'Incomplete': 'incomplete',
        'Cannot repair': 'cannot-repair',
        'Declined': 'declined',
        'Quote Requested': 'quote-requested',
        'Quote Provided': 'quote-provided'
      }
      return statusClasses[status] || 'reported'
    },

    getQuoteStatusClass(status) {
      const statusClasses = {
        'draft': 'quote-draft',
        'submitted': 'quote-submitted',
        'accepted': 'quote-accepted',
        'rejected': 'quote-rejected'
      }
      return statusClasses[status] || 'quote-draft'
    },

    isCategoryFullySelected(category) {
      const categoryServices = this.getServicesByCategory(category)
      const selectedInCategory = categoryServices.filter(service => this.selectedServices.includes(service.id))
      return categoryServices.length > 0 && selectedInCategory.length === categoryServices.length
    },

    isCategoryPartiallySelected(category) {
      const categoryServices = this.getServicesByCategory(category)
      const selectedInCategory = categoryServices.filter(service => this.selectedServices.includes(service.id))
      return selectedInCategory.length > 0 && selectedInCategory.length < categoryServices.length
    },

    selectAllInCategory(category) {
      const categoryServices = this.getServicesByCategory(category)
      const serviceIdsToAdd = categoryServices
        .map(s => s.id)
        .filter(id => !this.selectedServices.includes(id))

      this.selectedServices.push(...serviceIdsToAdd)
    },

    deselectAllInCategory(category) {
      const categoryServices = this.getServicesByCategory(category)
      const categoryServiceIds = categoryServices.map(s => s.id)

      this.selectedServices = this.selectedServices.filter(id => !categoryServiceIds.includes(id))
    },

    toggleCategoryExpansion(category) {
      this.expandedCategories[category] = !this.expandedCategories[category]
    },

    expandAllCategories() {
      const categories = this.getFilteredCategories()
      categories.forEach(cat => {
        this.expandedCategories[cat] = true
      })
    },

    collapseAllCategories() {
      const categories = this.getFilteredCategories()
      categories.forEach(cat => {
        this.expandedCategories[cat] = false
      })
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        // Search is handled reactively, but we can clear the timeout
        this.searchTimeout = null
      }, 300)
    },

    async debugQuoteLoading() {
      console.log('🔧 Debug: Checking quote management section')
      console.log('User role:', this.userRole)
      console.log('Quotes data:', this.quotes)
      console.log('Quote filters:', this.quoteFilters)
      console.log('Quotes section expanded:', this.sectionsExpanded.quotes)

      // Also test the API call directly
      try {
        console.log('🔧 Debug: Testing API call...')
        const response = await apiFetch('/backend/api/job-quotations.php')
        if (response.ok) {
          const data = await response.json()
          console.log('🔧 Debug: API response:', data)
        } else {
          console.log('🔧 Debug: API error:', response.status, response.statusText)
        }
      } catch (error) {
        console.log('🔧 Debug: API exception:', error)
      }
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
    },

    // Determine if job details are editable based on status and user permissions
    canEditJobDetails(job) {
      if (!job) return false

      // Allow editing if status is 'Reported' (not progressed) or job is assigned to current user
      if (job.job_status === 'Reported') {
        return true
      }

      // For other statuses, allow if the job is assigned to the current user
      if (job.assigned_technician_user_id === this.currentUserId) {
        return true
      }

      // Service provider admins can edit most jobs
      if (this.userRole === 3) {
        return true
      }

      return false
    },

    // Quote Management Methods
    async loadQuotes() {
      console.log('🔍 loadQuotes: Starting quote loading...')
      try {
        const params = new URLSearchParams()
        if (this.quoteFilters.status) params.append('status', this.quoteFilters.status)
        if (this.quoteFilters.job_id) params.append('job_id', this.quoteFilters.job_id)

        const url = `/backend/api/job-quotations.php?${params}`
        console.log('🔍 loadQuotes: Calling API:', url)

        const response = await apiFetch(url)
        console.log('🔍 loadQuotes: API response status:', response.status)

        if (response.ok) {
          const data = await response.json()
          console.log('🔍 loadQuotes: API response data:', data)
          console.log('🔍 loadQuotes: Setting quotes to:', data.quotes || [])
          this.quotes = data.quotes || []
          console.log('🔍 loadQuotes: Quotes data set to:', this.quotes)
        } else {
          const errorText = await response.text()
          console.error('🔍 loadQuotes: Failed to load quotes - Status:', response.status, '- Error:', errorText)
          this.quotes = []
        }
      } catch (error) {
        console.error('🔍 loadQuotes: Exception loading quotes:', error)
        console.error('🔍 loadQuotes: Error stack:', error.stack)
        this.quotes = []
      }
      console.log('🔍 loadQuotes: Final quotes state:', this.quotes)
    },

    async loadQuoteJobs() {
      // Load jobs that can have quotes (Quote Requested or Assigned status)
      try {
        const response = await apiFetch('/backend/api/service-provider-jobs.php?status=Quote%20Requested,Assigned')

        if (response.ok) {
          const data = await response.json()
          this.quoteJobs = data.jobs || []
        } else {
          console.error('Failed to load quote jobs')
          this.quoteJobs = []
        }
      } catch (error) {
        console.error('Failed to load quote jobs:', error)
        this.quoteJobs = []
      }
    },

    editQuote(quote) {
      this.editingQuote = quote
      this.quoteForm = {
        job_id: quote.job_id,
        quotation_amount: quote.quotation_amount,
        quotation_description: quote.quotation_description,
        quotation_document_url: quote.quotation_document_url,
        valid_until: quote.valid_until
      }
      this.showEditQuoteModal = true
    },

    async updateQuote() {
      // Defensive check with proper error handling
      if (!this.editingQuote) {
        console.error('🔧 updateQuote: editingQuote is undefined or null')
        alert('Error: No quote data available. Please try opening the edit modal again.')
        this.closeEditQuoteModal()
        return
      }

      this.loading = true
      try {
        // Check if this is actually a new quote (id is null, undefined, or doesn't exist)
        const isNewQuote = !this.editingQuote.id

        console.log('🔧 updateQuote: Is new quote?', isNewQuote)
        console.log('🔧 updateQuote: editingQuote:', this.editingQuote)
        console.log('🔧 updateQuote: editingQuote.job_id:', this.editingQuote.job_id)

        let quoteId = this.editingQuote.id
        let documentUrl = this.quoteForm.quotation_document_url

        // If it's a new quote, create it first
        if (isNewQuote) {
          const createRequestBody = {
            ...this.quoteForm
          }

          console.log('🔧 updateQuote: Creating new quote first')
          console.log('🔧 updateQuote: Create Body:', JSON.stringify(createRequestBody, null, 2))

          const createResponse = await apiFetch('/backend/api/job-quotations.php', {
            method: 'POST',
            body: JSON.stringify(createRequestBody)
          })

          console.log('🔧 updateQuote: Create response status:', createResponse.status)

          if (!createResponse.ok) {
            const errorData = await createResponse.json()
            console.error('🔧 updateQuote: Error creating quote:', errorData)
            this.handleError(errorData)
            return
          }

          const createResult = await createResponse.json()
          if (!createResult.success || !createResult.quote_id) {
            console.error('🔧 updateQuote: Invalid create response - missing quote_id')
            console.error('🔧 updateQuote: Create result:', createResult)
            alert('Error: Failed to create quote. Please try again.')
            return
          }
          quoteId = createResult.quote_id // Get the new quote ID
          console.log('🔧 updateQuote: New quote created with ID:', quoteId)
        }

        // Handle PDF upload after quote exists (for both new and existing quotes)
        if (this.pdfToUpload && !documentUrl) {
          console.log('🔧 updateQuote: PDF file detected - uploading for quote ID:', quoteId)
          await this.uploadPdfDocumentForQuote(quoteId)

          // If upload failed, stop here
          if (this.uploadingPdf) {
            alert('PDF upload in progress. Please wait for it to complete.')
            return
          }

          if (!this.quoteForm.quotation_document_url) {
            alert('Failed to upload PDF. Please try again or remove the file.')
            return
          }
        }

        // Update existing quote if it already existed
        if (!isNewQuote) {
          const updateRequestBody = {
            quote_id: this.editingQuote.id,
            ...this.quoteForm
          }

          console.log('🔧 updateQuote: Updating existing quote')
          console.log('🔧 updateQuote: Update Body:', JSON.stringify(updateRequestBody, null, 2))

          const updateResponse = await apiFetch('/backend/api/job-quotations.php', {
            method: 'PUT',
            body: JSON.stringify(updateRequestBody)
          })

          console.log('🔧 updateQuote: Update response status:', updateResponse.status)

          if (!updateResponse.ok) {
            const errorData = await updateResponse.json()
            console.error('🔧 updateQuote: Error updating quote:', errorData)
            this.handleError(errorData)
            return
          }
        }

        console.log('🔧 updateQuote: Success - refreshing quotes and jobs')
        this.closeEditQuoteModal()
        this.loadQuotes()
        this.loadJobs() // Refresh jobs to show any status changes
        alert(isNewQuote ? 'Quote created successfully!' : 'Quote updated successfully!')
      } catch (error) {
        console.error('🔧 updateQuote: Exception:', error)
        alert('Failed to save quote')
      } finally {
        this.loading = false
      }
    },

    async submitQuote(quote) {
      if (!confirm('Are you sure you want to submit this quote? You won\'t be able to edit it after submission.')) {
        return
      }

      this.loading = true
      try {
        const response = await apiFetch('/backend/api/job-quotations.php', {
          method: 'POST',
          body: JSON.stringify({
            job_id: quote.job_id,
            quotation_amount: quote.quotation_amount,
            quotation_description: quote.quotation_description,
            quotation_document_url: quote.quotation_document_url,
            valid_until: quote.valid_until
          })
        })

        if (response.ok) {
          this.loadQuotes()
          this.loadJobs() // Refresh jobs to show status change
          alert('Quote submitted successfully!')
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to submit quote')
      } finally {
        this.loading = false
      }
    },

    closeEditQuoteModal() {
      this.showEditQuoteModal = false
      this.editingQuote = null
      this.quoteForm = {
        job_id: '',
        quotation_amount: '',
        quotation_description: '',
        quotation_document_url: '',
        valid_until: ''
      }
    },

    viewQuoteDetails(quote) {
      this.selectedQuote = quote
      this.showQuoteDetailsModal = true
    },

    getQuoteStatusClass(status) {
      const statusClasses = {
        'draft': 'quote-draft',
        'submitted': 'quote-submitted',
        'accepted': 'quote-accepted',
        'rejected': 'quote-rejected'
      }
      return statusClasses[status] || 'quote-draft'
    },

    formatCurrency(amount) {
      // Format as South African Rand with "R" prefix instead of "ZAR"
      return 'R ' + parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
    },

    // Services Modal Methods
    getFilteredServices() {
      if (!this.availableServices || this.availableServices.length === 0) {
        return []
      }

      let filtered = this.availableServices || []

      if (this.searchTerm && this.searchTerm.trim()) {
        const search = this.searchTerm.toLowerCase().trim()
        filtered = filtered.filter(service =>
          service.name.toLowerCase().includes(search) ||
          service.category.toLowerCase().includes(search)
        )
      }

      return filtered
    },

    getFilteredCategories() {
      const services = this.getFilteredServices()
      const categories = [...new Set(services.map(service => service.category))]
      return categories.sort()
    },

    getServicesByCategory(category) {
      return this.getFilteredServices().filter(service => service.category === category)
    },

    getServiceName(serviceId) {
      const service = this.availableServices.find(s => s.id === serviceId)
      return service ? service.name : 'Unknown Service'
    },

    isCategoryFullySelected(category) {
      const categoryServices = this.getServicesByCategory(category)
      const selectedInCategory = categoryServices.filter(service => this.selectedServices.includes(service.id))
      return categoryServices.length > 0 && selectedInCategory.length === categoryServices.length
    },

    isCategoryPartiallySelected(category) {
      const categoryServices = this.getServicesByCategory(category)
      const selectedInCategory = categoryServices.filter(service => this.selectedServices.includes(service.id))
      return selectedInCategory.length > 0 && selectedInCategory.length < categoryServices.length
    },

    selectAllInCategory(category) {
      const categoryServices = this.getServicesByCategory(category)
      const serviceIdsToAdd = categoryServices
        .map(s => s.id)
        .filter(id => !this.selectedServices.includes(id))

      this.selectedServices.push(...serviceIdsToAdd)
    },

    deselectAllInCategory(category) {
      const categoryServices = this.getServicesByCategory(category)
      const categoryServiceIds = categoryServices.map(s => s.id)

      this.selectedServices = this.selectedServices.filter(id => !categoryServiceIds.includes(id))
    },

    toggleCategoryExpansion(category) {
      this.expandedCategories[category] = !this.expandedCategories[category]
    },

    expandAllCategories() {
      const categories = this.getFilteredCategories()
      categories.forEach(cat => {
        this.expandedCategories[cat] = true
      })
    },

    collapseAllCategories() {
      const categories = this.getFilteredCategories()
      categories.forEach(cat => {
        this.expandedCategories[cat] = false
      })
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        // Search is handled reactively, but we can clear the timeout
        this.searchTimeout = null
      }, 300)
    },

    getDefaultQuoteValidUntil() {
      const today = new Date();
      const thirtyDaysFromNow = new Date(today);
      thirtyDaysFromNow.setDate(today.getDate() + 30);
      return thirtyDaysFromNow.toISOString().split('T')[0]; // Return YYYY-MM-DD format
    },

    getCategoryColor(category) {
      const colors = {
        'Electrical': '#3B82F6',    // Blue
        'Mechanical': '#10B981',   // Green
        'Plumbing': '#06B6D4',     // Cyan
        'HVAC': '#8B5CF6',         // Purple
        'Construction': '#F59E0B', // Amber
        'Maintenance': '#EF4444',  // Red
        'IT': '#6366F1',           // Indigo
        'Other': '#6B7280'         // Gray
      };
      return colors[category] || colors['Other'];
    },

    getCategoryIcon(category) {
      const icons = {
        'Electrical': 'electric_bolt',
        'Mechanical': 'build',
        'Plumbing': 'water_drop',
        'HVAC': 'air',
        'Construction': 'construction',
        'Maintenance': 'maintenance',
        'IT': 'computer',
        'Other': 'settings'
      };
      return icons[category] || icons['Other'];
    },

    // Edit Job Page Navigation Methods
    handleEditJob(job) {
      console.log('Navigating to EditJob page for job:', job)

      // For Quote Requested jobs, open the quote editing modal instead
      if (job.job_status === 'Quote Requested') {
        console.log('Quote Requested job detected, opening quote modal instead')
        // Check if there's already a quote for this job
        const existingQuote = this.quotes.find(q => q.job_id === job.id)
        if (existingQuote) {
          console.log('Found existing quote, editing it')
          this.editQuote(existingQuote)
        } else {
          console.log('No existing quote found, creating new quote data structure')
          // Create new quote data structure
          const quoteData = {
            id: null, // This will be a new quote
            job_id: job.id,
            item_identifier: job.item_identifier,
            fault_description: job.fault_description,
            client_name: job.client_name,
            quotation_amount: '0.00',
            quotation_description: '',
            quotation_document_url: '',
            valid_until: this.getDefaultQuoteValidUntil(),
            status: 'draft'
          }
          this.editQuote(quoteData)
          // Actually open the quote modal
          this.showEditQuoteModal = true
        }
        return
      }

      // For all other jobs, navigate to the dedicated edit job page with scroll position context
      const scrollPosition = window.pageYOffset || 0
      this.$router.push({
        path: `/jobs/${job.id}/edit`,
        query: {
          from: 'service-provider',
          scroll: scrollPosition.toString()
        }
      })
    },

    async handleJobUpdated() {
      console.log('Job updated, refreshing jobs list')
      // Refresh the jobs list to show the updated job
      await this.loadJobs()
      // Close might already be handled by the modal, but ensure cleanup
      this.editingJobForModal = null
    },

    // Archive Job Methods
    async toggleArchiveJob(job) {
      const action = job.archived_by_service_provider ? 'unarchive' : 'archive'
      const confirmMessage = `Are you sure you want to ${action} this job?`

      if (!confirm(confirmMessage)) {
        return
      }

      try {
        const response = await apiFetch('/backend/api/service-provider-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: job.id,
            archived_by_service_provider: !job.archived_by_service_provider
          })
        })

        if (response.ok) {
          // Refresh jobs list from server to ensure proper filtering
          await this.loadJobs()
          alert(`Job ${action}d successfully!`)
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert(`Failed to ${action} job`)
      }
    },

    // PDF Upload Methods
    handlePdfUpload(event) {
      const file = event.target.files[0]
      if (file) {
        this.pdfToUpload = file
        this.pdfDisplayName = file.name
        console.log('PDF file selected:', file.name, 'Size:', file.size)
      }
    },

    async uploadPdfDocument() {
      // Legacy method for backward compatibility
      // This method assumes we're editing the current quote
      if (!this.editingQuote?.id) {
        alert('Please save the quote first before uploading documents.')
        return
      }

      await this.uploadPdfDocumentForQuote(this.editingQuote.id)
    },

    async uploadPdfDocumentForQuote(quoteId) {
      if (!this.pdfToUpload) {
        alert('Please select a PDF file first')
        return
      }

      if (!quoteId) {
        alert('No quote ID provided for PDF upload')
        return
      }

      this.uploadingPdf = true

      try {
        // Create FormData for file upload
        const formData = new FormData()
        formData.append('quote_document', this.pdfToUpload)
        formData.append('quote_id', quoteId.toString())
        formData.append('token', localStorage.getItem('token')) // API expects token in POST data

        console.log('Starting PDF upload for quote ID:', quoteId)

        const response = await fetch('/backend/api/upload-quote-document.php', {
          method: 'POST',
          body: formData
          // Removed Authorization header since API reads from POST data
        })

        if (response.ok) {
          const result = await response.json()
          console.log('PDF upload success:', result)

          // Update the quote form with the uploaded document URL
          this.quoteForm.quotation_document_url = result.document_url
          this.uploadedPdfPath = result.document_url

          // Clear the uploaded file since it's been processed
          this.pdfToUpload = null
        } else {
          const error = await response.json()
          console.error('PDF upload failed:', error)

          if (error.error) {
            alert(`PDF upload failed: ${error.error}`)
          } else {
            alert('PDF upload failed. Check server logs for details.')
          }
        }
      } catch (error) {
        console.error('PDF upload exception:', error)
        alert('Failed to upload PDF. Please try again.')
      } finally {
        this.uploadingPdf = false
      }
    },

    getPdfDownloadUrl(pdfPath) {
      if (!pdfPath) return '#'
      // Construct the download URL for the PDF
      const token = localStorage.getItem('token')
      return `/backend/api/upload-quote-document.php?path=${encodeURIComponent(pdfPath)}&token=${encodeURIComponent(token)}`
    },

    // Navigation method for header component
    handleNavigate(route) {
      if (route) {
        this.$router.push(route)
      }
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
  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
  }
}

/* Admin Settings Section */
.admin-settings-container {
  /* container styles */
}

.admin-section-header {
  transition: background-color 0.2s ease;
}

.admin-section-header:hover {
  background-color: #f9fafb; /* gray-50 */
}

.admin-subsections {
  /* background-color: #f9fafb; /* gray-50 */
  /* padding: 1rem; */
}

.subsection-container {
  background-color: #ffffff;
  border: 1px solid #e5e7eb; /* gray-200 */
  border-radius: 0.75rem; /* 12px */
  overflow: hidden;
  margin-left: 1rem; /* Indentation */
}

.subsection-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.subsection-header:hover {
  background-color: #f9fafb; /* gray-50 */
}

.subsection-title {
  display: flex;
  align-items: center;
  gap: 0.75rem; /* 12px */
  font-size: 1.125rem; /* 18px */
  font-weight: 600;
}

.subsection-title h4 {
  margin: 0;
  font-size: 1rem; /* 16px */
  font-weight: 500;
}

.completeness-badge {
  font-size: 0.75rem; /* 12px */
  padding: 0.25rem 0.5rem;
  background-color: #e0e7ff; /* indigo-100 */
  color: #3730a3; /* indigo-800 */
  border-radius: 9999px;
}

.actions {
  display: flex;
  align-items: center;
  gap: 0.5rem; /* 8px */
}

.subsection-content {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb; /* gray-200 */
  background-color: #f9fafb; /* gray-50 */
}

</style>
