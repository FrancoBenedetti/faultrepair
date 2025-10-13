<template>
    <div class="client-dashboard max-w-7xl mx-auto px-6 py-8">
      <!-- User Identity Bar -->
      <div class="user-identity-bar bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
          <div class="user-info flex items-center gap-4">
            <div class="user-avatar flex-shrink-0">
              <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="material-icon text-white">{{ getCurrentUserName().charAt(0).toUpperCase() }}</span>
              </div>
            </div>
            <div class="identity-details">
              <div class="signed-in-user flex items-center gap-2 mb-1">
                <span class="material-icon-sm text-blue-600">person</span>
                <span class="text-sm font-medium text-gray-700">Signed in as:</span>
                <span class="text-sm font-semibold text-gray-900">{{ getCurrentUserName() }}</span>
                <span class="user-role-badge" :class="getRoleBadgeClass(userRole)">
                  {{ getRoleDisplayName(userRole) }}
                </span>
              </div>
              <div class="organization-info flex items-center gap-2">
                <span class="material-icon-sm text-indigo-600">business</span>
                <span class="text-sm font-medium text-gray-700">Organization:</span>
                <span class="text-sm font-semibold text-gray-900">{{ getOrganizationName() }}</span>
              </div>
            </div>
          </div>
          <div class="subscription-info flex items-center gap-4">
            <div class="subscription-badge flex items-center gap-2 px-3 py-1 bg-white border border-gray-300 rounded-full">
              <span class="material-icon-sm text-green-600">workspace_premium</span>
              <span class="text-xs font-medium text-gray-700">Free Plan</span>
            </div>
            <!-- Upgrade button for admin users only -->
            <button
              v-if="isAdmin"
              @click="handleUpgradeClick"
              class="upgrade-btn flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-sm transform hover:scale-105"
            >
              <span class="material-icon-sm">upgrade</span>
              Upgrade to Premium
            </button>
          </div>
        </div>
      </div>

      <!-- Dashboard Header -->
      <div class="dashboard-header flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6 mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-lg">
        <div class="header-left flex items-center gap-4">
          <div class="dashboard-title">
            <h1 class="text-3xl font-bold text-gray-900">
              {{ getOrganizationName() }} - {{ userRole === 1 ? 'Report Dashboard' : 'Client Dashboard' }}
            </h1>
          </div>
          <!-- Profile completeness bar - hidden for reporting employees -->
          <div v-if="userRole === 2" class="profile-completeness flex items-center gap-4">
            <div class="completeness-bar w-64 h-3 bg-gray-200 rounded-full overflow-hidden">
              <div class="completeness-fill h-full bg-blue-600 transition-all duration-500 ease-out" :style="{ width: profileCompleteness + '%' }"></div>
            </div>
            <span class="completeness-text text-lg font-medium text-gray-900">{{ profileCompleteness }}% Complete</span>
          </div>
        </div>
        <div class="header-right flex gap-3">
          <button v-if="isAdmin" @click="showEditProfileModal = true" class="btn-outline flex items-center gap-2">
            <span class="material-icon-sm">business</span>
            Edit Business Profile
          </button>
          <button @click="$router.push('/create-invitation')" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">person_add</span>
            Create Invitation
          </button>
          <button @click="signOut" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">logout</span>
            Sign Out
          </button>
        </div>
      </div>

    <div class="dashboard-content grid gap-8">
      <!-- Business Profile Section - Only for budget controllers -->
      <div v-if="userRole === 2" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('profile')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.profile }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
              <span class="material-icon text-blue-600">business</span>
              Business Profile
            </h2>
            <div class="profile-completeness-badge">
              <span class="completeness-percentage">{{ clientProfileCompleteness }}%</span>
              <span class="completeness-label">Complete</span>
            </div>
          </div>
          <button v-if="isAdmin" @click.stop="showEditProfileModal = true" class="btn-filled flex items-center gap-2">
            <span class="material-icon-sm">edit</span>
            Edit Profile
          </button>
        </div>

        <div v-show="sectionsExpanded.profile" class="section-content transition-all duration-300 ease-in-out">
          <!-- Loading state -->
          <div v-if="clientProfile === null" class="loading-state text-center py-16">
            <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-body-large text-on-surface-variant">Loading profile...</p>
          </div>

          <!-- Profile content -->
          <div v-else-if="clientProfile" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Basic Information Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                  <span class="material-icon-sm text-white">business_center</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
              </div>
              <div class="space-y-3">
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">store</span>
                    Business Name:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ clientProfile.name || 'Not specified' }}</span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">location_on</span>
                    Address:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ clientProfile.address || 'Not specified' }}</span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">link</span>
                    Website:
                  </span>
                  <span class="text-sm text-right">
                    <span v-if="clientProfile.website">
                      <a :href="clientProfile.website"
                         target="_blank"
                         class="text-blue-600 hover:text-blue-800 underline break-all">
                        {{ clientProfile.website }}
                      </a>
                    </span>
                    <span v-else class="text-gray-900">Not specified</span>
                  </span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">description</span>
                    Description:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ clientProfile.description || 'Not specified' }}</span>
                </div>
              </div>
            </div>

            <!-- Manager Contact Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
                <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                  <span class="material-icon-sm text-white">contact_phone</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Manager Contact</h3>
              </div>
              <div class="space-y-3">
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">person</span>
                    Manager Name:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ clientProfile.manager_name || 'Not specified' }}</span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">email</span>
                    Manager Email:
                  </span>
                  <span class="text-sm text-right">
                    <span v-if="clientProfile.manager_email">
                      <a :href="`mailto:${clientProfile.manager_email}`"
                         class="text-blue-600 hover:text-blue-800 underline break-all">
                        {{ clientProfile.manager_email }}
                      </a>
                    </span>
                    <span v-else class="text-gray-900">Not specified</span>
                  </span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">phone</span>
                    Manager Phone:
                  </span>
                  <span class="text-sm text-right">
                    <span v-if="clientProfile.manager_phone">
                      <a :href="`tel:${clientProfile.manager_phone}`"
                         class="text-blue-600 hover:text-blue-800 underline">
                        {{ clientProfile.manager_phone }}
                      </a>
                    </span>
                    <span v-else class="text-gray-900">Not specified</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Business Details Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center gap-3 mb-4 pb-2 border-b border-gray-100">
                <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                  <span class="material-icon-sm text-white">assignment</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Business Details</h3>
              </div>
              <div class="space-y-3">
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">receipt</span>
                    VAT Number:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ clientProfile.vat_number || 'Not specified' }}</span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">business</span>
                    Registration:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ clientProfile.business_registration_number || 'Not specified' }}</span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">toggle_on</span>
                    Status:
                  </span>
                  <span class="px-2 py-1 rounded-full text-xs font-medium text-right"
                        :class="clientProfile.is_active
                                ? 'bg-green-100 text-green-800'
                                : 'bg-red-100 text-red-800'">
                    {{ clientProfile.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
                <div class="flex justify-between items-start">
                  <span class="text-sm font-medium text-gray-500 flex items-center gap-2">
                    <span class="material-icon-sm w-4 h-4 flex-shrink-0">event_available</span>
                    Member Since:
                  </span>
                  <span class="text-sm text-gray-900 text-right">{{ formatDate(clientProfile.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- No profile state -->
          <div v-else class="no-profile text-center py-16">
            <div class="no-profile-icon material-icon-xl text-neutral-400 mb-4">business</div>
            <h3 class="text-title-large text-on-surface mb-2">Profile Not Set Up</h3>
            <p class="text-body-large text-on-surface-variant mb-4">Complete your business profile to help service providers understand your organization better.</p>
            <button @click="showEditProfileModal = true" class="btn-filled">
              <span class="material-icon-sm mr-2">edit</span>
              Set Up Profile
            </button>
          </div>
        </div>
      </div>

      <!-- User Management Section - Hidden for reporting employees -->
      <div v-if="userRole === 2" class="users-section card p-6">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('users')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.users }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0">User Management</h2>
          </div>
          <button v-if="isAdmin" @click.stop="showAddUserModal = true" class="btn-filled">
            <span class="material-icon-sm mr-2">person_add</span>
            Add New User
          </button>
        </div>

        <div v-show="sectionsExpanded.users" class="section-content transition-all duration-300 ease-in-out">
          <div class="users-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Loading state -->
            <div v-if="users === null" class="loading-state col-span-full text-center py-16">
              <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
              <p class="text-body-large text-on-surface-variant">Loading users...</p>
            </div>

            <!-- User cards -->
            <div v-else-if="users && users.length > 0" v-for="user in users" :key="user.id" class="user-card card overflow-hidden transition-all duration-200 hover:shadow-elevation-3">
              <div class="user-header card-header flex justify-between items-center">
                <div class="user-avatar w-12 h-12 bg-primary-600 rounded-full flex items-center justify-center">
                  <span class="material-icon text-on-primary">{{ user.username.charAt(0).toUpperCase() }}</span>
                </div>
                <div class="user-actions flex gap-2" v-if="isAdmin">
                  <button @click="editUser(user)" class="btn-outlined btn-small">
                    <span class="material-icon-sm">edit</span>
                  </button>
                  <button v-if="canDeleteUser(user)" @click="deleteUser(user)" class="btn-outlined btn-small text-error-600 border-error-600 hover:bg-error-50">
                    <span class="material-icon-sm">delete</span>
                  </button>
                </div>
              </div>

              <div class="user-content card-content">
                <h3 class="user-name text-title-medium text-on-surface mb-2">{{ user.username }}</h3>
                <p class="user-email text-body-medium text-on-surface-variant mb-3">{{ user.email }}</p>
                <div class="user-role">
                  <span class="role-badge status-badge" :class="getRoleClass(user.role_name)">
                    {{ user.role_name }}
                  </span>
                </div>
              </div>

              <div class="user-footer card-footer">
                <div class="user-date text-label-medium text-on-surface-variant">
                  Added {{ formatDate(user.created_at) }}
                </div>
              </div>
            </div>

            <!-- No users state -->
            <div v-else-if="users && users.length === 0" class="no-users col-span-full text-center py-16">
              <div class="no-users-icon material-icon-xl text-neutral-400 mb-4">group</div>
              <h3 class="text-title-large text-on-surface mb-2">No users found</h3>
              <p class="text-body-large text-on-surface-variant">Get started by adding your first user.</p>
            </div>

            <!-- Error state -->
            <div v-else class="error-state col-span-full text-center py-16">
              <div class="error-icon material-icon-xl text-error-400 mb-4">error</div>
              <h3 class="text-title-large text-on-surface mb-2">Failed to load users</h3>
              <p class="text-body-large text-on-surface-variant">Please try refreshing the page.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Locations Section - Hidden for reporting employees -->
      <div v-if="userRole === 2" class="locations-section card p-6">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('locations')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.locations }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0">Locations</h2>
          </div>
          <div class="header-right flex items-center gap-4">
            <!-- View Mode Toggle -->
            <div class="view-mode-toggle flex border border-gray-300 rounded-lg overflow-hidden">
              <button @click.stop="locationsViewMode = 'cards'" :class="{ 'bg-blue-600 text-white': locationsViewMode === 'cards', 'bg-white text-gray-700 hover:bg-gray-50': locationsViewMode !== 'cards' }" class="px-4 py-2 text-sm font-medium transition-colors">
                <span class="material-icon-sm mr-1">grid_view</span>
                Cards
              </button>
              <button @click.stop="locationsViewMode = 'table'" :class="{ 'bg-blue-600 text-white': locationsViewMode === 'table', 'bg-white text-gray-700 hover:bg-gray-50': locationsViewMode !== 'table' }" class="px-4 py-2 text-sm font-medium transition-colors">
                <span class="material-icon-sm mr-1">table_chart</span>
                Table
              </button>
            </div>
            <button v-if="isAdmin" @click.stop="showAddLocationModal = true" class="btn-filled">
              <span class="material-icon-sm mr-2">add</span>
              Add Location
            </button>
          </div>
        </div>

        <div v-show="sectionsExpanded.locations" class="section-content transition-all duration-300 ease-in-out">

        <!-- Cards View -->
        <div v-if="locationsViewMode === 'cards'" class="locations-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Loading state -->
          <div v-if="locations === null" class="loading-state col-span-full text-center py-16">
            <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-body-large text-on-surface-variant">Loading locations...</p>
          </div>

          <!-- Default location state -->
          <div v-else-if="locations && locations.length === 0" class="default-location col-span-full text-center py-16">
            <div class="material-icon-xl text-neutral-400 mb-4">business</div>
            <h3 class="text-title-large text-on-surface mb-2">Default Location</h3>
            <p class="text-body-large text-on-surface-variant">Using client name as default location. Add specific locations if needed.</p>
          </div>

          <!-- Location cards -->
          <div v-else-if="locations && locations.length > 0" v-for="location in locations" :key="location.id" class="location-card card overflow-hidden transition-all duration-200 hover:shadow-elevation-3 cursor-pointer" @click="filterJobsByLocation(location)">
            <div class="location-header card-header flex justify-between items-center">
              <div class="location-icon w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="material-icon text-white">location_on</span>
              </div>
              <div class="location-actions flex gap-2" v-if="isAdmin">
                <button @click.stop="editLocation(location)" class="btn-outlined btn-small">
                  <span class="material-icon-sm">edit</span>
                </button>
                <button v-if="location.job_count === 0" @click.stop="deleteLocation(location)" class="btn-outlined btn-small text-error-600 border-error-600 hover:bg-error-50">
                  <span class="material-icon-sm">delete</span>
                </button>
              </div>
            </div>

            <div class="location-content card-content">
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
            </div>

            <div class="location-footer card-footer">
              <div class="location-click-hint text-label-medium text-on-surface-variant">
                <span class="material-icon-sm mr-1">filter_list</span>
                Click to filter jobs
              </div>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <div v-else-if="locationsViewMode === 'table'" class="locations-table-container">
          <!-- Loading state -->
          <div v-if="locations === null" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Loading locations...</p>
          </div>

          <!-- Default location state -->
          <div v-else-if="locations && locations.length === 0" class="default-location">
            <div class="default-location-icon">🏢</div>
            <h3>Default Location</h3>
            <p>Using client name as default location. Add specific locations if needed.</p>
          </div>

          <!-- Locations table -->
          <div v-else-if="locations && locations.length > 0" class="locations-table-wrapper">
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
                <tr v-for="location in locations" :key="location.id" class="location-row" @click="filterJobsByLocation(location)">
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
                      <button @click.stop="editLocation(location)" class="btn-secondary btn-small">
                        Edit
                      </button>
                      <button v-if="location.job_count === 0" @click.stop="deleteLocation(location)" class="btn-danger btn-small">
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
      </div>

      <!-- Approved Providers Section - Hidden for reporting employees -->
      <div v-if="userRole === 2" class="providers-section card p-6">
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="toggleSection('providers')" style="cursor: pointer;">
          <div class="section-title flex items-center gap-3">
            <button class="expand-btn" :class="{ expanded: sectionsExpanded.providers }">
              <span class="material-icon-sm">expand_more</span>
            </button>
            <h2 class="text-title-large text-on-surface mb-0">Approved Service Providers</h2>
          </div>
          <router-link v-if="isAdmin" to="/browse-providers" class="btn-outlined" @click.stop>
            <span class="material-icon-sm mr-2">search</span>
            Browse Providers
          </router-link>
        </div>

        <div v-show="sectionsExpanded.providers" class="section-content transition-all duration-300 ease-in-out">

        <div class="providers-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Loading state -->
          <div v-if="approvedProviders === null" class="loading-state col-span-full text-center py-16">
            <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-body-large text-on-surface-variant">Loading providers...</p>
          </div>

          <!-- Provider cards -->
          <div v-else-if="approvedProviders && approvedProviders.length > 0" v-for="provider in approvedProviders" :key="provider.id" class="provider-card card overflow-hidden transition-all duration-200 hover:shadow-elevation-3">
            <div class="provider-header card-header flex justify-between items-center">
              <div class="provider-logo w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="material-icon text-on-primary">{{ provider.name.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="provider-actions flex gap-2">
                <button @click="viewProviderJobs(provider)" class="btn-outlined btn-small">
                  <span class="material-icon-sm">visibility</span>
                </button>
              </div>
            </div>

            <div class="provider-content card-content">
              <h3 class="provider-name text-title-medium text-on-surface mb-2">{{ provider.name }}</h3>
              <p class="provider-address text-body-medium text-on-surface-variant mb-4">{{ provider.address }}</p>

              <div class="provider-stats grid grid-cols-3 gap-3">
                <div class="stat-item text-center">
                  <span class="stat-number text-xl font-bold text-on-surface">{{ provider.total_jobs }}</span>
                  <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Total</span>
                </div>
                <div class="stat-item text-center">
                  <span class="stat-number text-xl font-bold text-on-surface">{{ provider.active_jobs }}</span>
                  <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Active</span>
                </div>
                <div class="stat-item text-center">
                  <span class="stat-number text-xl font-bold text-on-surface">{{ provider.completed_jobs }}</span>
                  <span class="stat-label text-label-small text-on-surface-variant uppercase tracking-wide">Completed</span>
                </div>
              </div>
            </div>

            <div class="provider-footer card-footer">
              <div class="approval-date text-label-medium text-on-surface-variant">
                <span class="material-icon-sm mr-1">check_circle</span>
                Approved {{ formatDate(provider.approved_at) }}
              </div>
            </div>
          </div>

          <!-- No providers state -->
          <div v-else-if="approvedProviders && approvedProviders.length === 0" class="no-providers col-span-full text-center py-16">
            <div class="material-icon-xl text-neutral-400 mb-4">handshake</div>
            <h3 class="text-title-large text-on-surface mb-2">No approved providers</h3>
            <p class="text-body-large text-on-surface-variant">Browse and approve service providers to get started.</p>
          </div>

          <!-- Error state -->
          <div v-else class="error-state col-span-full text-center py-16">
            <div class="error-icon material-icon-xl text-error-400 mb-4">error</div>
            <h3 class="text-title-large text-on-surface mb-2">Failed to load providers</h3>
            <p class="text-body-large text-on-surface-variant">Please try refreshing the page.</p>
          </div>
        </div>
        </div>
      </div>

      <!-- Jobs Section -->
      <div class="jobs-section card p-6">
        <!-- Jobs Section Header -->
        <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200">
          <div class="flex-1">
            <h2 class="text-title-large text-on-surface mb-0">Job Management</h2>
            <div v-if="!clientProfile?.is_enabled" class="admin-disabled-warning mt-3">
              <div class="disabled-banner">
                <div class="disabled-icon">⚠️</div>
                <div class="disabled-content">
                  <h4>Account Administratively Disabled</h4>
                  <p>Service request creation is restricted. {{ clientProfile?.disabled_reason || 'Please contact support for assistance.' }}</p>
                </div>
              </div>
            </div>
          </div>
          <button @click="showCreateJobModal = true" class="btn-filled" :disabled="!clientProfile?.is_enabled">
            <span class="material-icon-sm mr-2">add</span>
            Service Request
          </button>
        </div>

        <!-- Job Filters -->
        <div class="job-filters flex flex-wrap gap-4 mb-6 p-4 bg-neutral-50 rounded-lg">
          <div class="filter-group min-w-40">
            <label for="status-filter" class="form-label mb-1">Status:</label>
            <select id="status-filter" v-model="jobFilters.status" @change="loadJobs" class="form-input">
              <option value="">All Statuses</option>
              <option value="Reported">Reported</option>
              <option value="Assigned">Assigned</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
          <!-- Archive filter only for budget controllers -->
          <div v-if="userRole === 2" class="filter-group min-w-40">
            <label for="archive-filter" class="form-label mb-1">Archive Status:</label>
            <select id="archive-filter" v-model="jobFilters.archive_status" @change="loadJobs" class="form-input">
              <option value="">All Jobs</option>
              <option value="active">Active Jobs</option>
              <option value="archived">Archived Jobs</option>
            </select>
          </div>
          <!-- Location and Provider filters only for budget controllers -->
          <div v-if="userRole === 2" class="filter-group min-w-40">
            <label for="location-filter" class="form-label mb-1">Location:</label>
            <select id="location-filter" v-model="jobFilters.location_id" @change="loadJobs" class="form-input">
              <option value="">All Locations</option>
              <option v-for="location in locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
          </div>
          <div v-if="userRole === 2" class="filter-group min-w-40">
            <label for="provider-filter" class="form-label mb-1">Provider:</label>
            <select id="provider-filter" v-model="jobFilters.provider_id" @change="loadJobs" class="form-input">
              <option value="">All Providers</option>
              <option v-for="provider in approvedProviders" :key="provider.service_provider_id" :value="provider.service_provider_id">
                {{ provider.name }}
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
                <button v-if="canEditJob(job)" @click="editJob(job)" class="btn-outlined btn-small">
                  <span class="material-icon-sm">edit</span>
                </button>
                <button v-else @click="viewJobDetails(job)" class="btn-outlined btn-small">
                  <span class="material-icon-sm">visibility</span>
                </button>
                <!-- Archive/Unarchive button for budget controllers -->
                <button v-if="userRole === 2" @click="toggleArchiveJob(job)" class="btn-outlined btn-small" :class="{ 'text-orange-600 border-orange-600': job.archived_by_client }">
                  <span class="material-icon-sm">{{ job.archived_by_client ? 'unarchive' : 'archive' }}</span>
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
                  <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Reported by:</span>
                  <span class="meta-value text-body-small text-on-surface font-medium">{{ job.reporting_user }}</span>
                </div>
                <div class="meta-item">
                  <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Date:</span>
                  <span class="meta-value text-body-small text-on-surface font-medium">{{ formatDate(job.created_at) }}</span>
                </div>
                <div class="meta-item">
                  <span class="meta-label text-label-small text-on-surface-variant uppercase tracking-wide">Provider:</span>
                  <span class="meta-value text-body-small text-on-surface font-medium">{{ job.assigned_provider_name || 'Not assigned' }}</span>
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
            <p class="text-body-large text-on-surface-variant">Report your first fault to get started.</p>
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

    <!-- Add User Modal -->
    <div v-if="showAddUserModal" class="modal-overlay" @click="showAddUserModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Edit User: {{ editingUser.username }} (ID: {{ editingUser.id }})</h3>
          <button @click="showEditUserModal" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="addUser" class="user-form">
          <div class="form-row">
            <div class="form-group">
              <label for="username">Username *</label>
              <input type="text" id="username" v-model="newUser.username" required>
            </div>
            <div class="form-group">
              <label for="email">Email Address *</label>
              <input type="email" id="email" v-model="newUser.email" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name *</label>
              <input type="text" id="first_name" v-model="newUser.first_name" required>
            </div>
            <div class="form-group">
              <label for="last_name">Last Name *</label>
              <input type="text" id="last_name" v-model="newUser.last_name" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <input type="tel" id="phone" v-model="newUser.phone" required
                     placeholder="+27 12 345 6789">
            </div>
            <div class="form-group">
              <label for="role">Role *</label>
              <select id="role" v-model="newUser.role_id" required>
                <option value="">Select Role</option>
                <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="showAddUserModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="addingUser">
              {{ addingUser ? 'Adding User...' : 'Add User' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="showEditUserModal" class="modal-overlay" @click="showEditUserModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Edit User: {{ editingUser.username }}</h3>
          <button @click="showEditUserModal = false" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="updateUser" class="user-form">
          <div class="form-row">
            <div class="form-group">
              <label for="edit-username">Username</label>
              <input type="text" id="edit-username" v-model="editingUser.username" disabled>
              <small class="form-help">Username cannot be changed</small>
            </div>
            <div class="form-group">
              <label for="edit-email">Email Address *</label>
              <input type="email" id="edit-email" v-model="editingUser.email" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="edit-role">Role *</label>
              <select id="edit-role" v-model="editingUser.role_id" required>
                <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label for="edit-mobile">Mobile Number</label>
              <input type="tel" id="edit-mobile" v-model="editingUser.mobile"
                     placeholder="+27 12 345 6789">
            </div>
          </div>

          <div class="form-group">
            <label for="edit-password">New Password (leave blank to keep current)</label>
            <input type="password" id="edit-password" v-model="editingUser.newPassword"
                   minlength="6">
            <small class="form-help">Minimum 6 characters</small>
          </div>

          <div class="form-actions">
            <button type="button" @click="showEditUserModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="updatingUser">
              {{ updatingUser ? 'Updating...' : 'Update User' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Job Modal -->
    <div v-if="showCreateJobModal" class="modal-overlay" @click="showCreateJobModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>New Service Request</h3>
          <button @click="showCreateJobModal = false" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="createJob" class="job-form">
          <div class="form-row">
            <div class="form-group">
              <label for="item-identifier">Item Identifier *</label>
              <div class="input-with-button">
                <input type="text" id="item-identifier" v-model="newJob.item_identifier" required
                       placeholder="e.g., Computer-001, Printer-ABC or scan QR code">
                <QrScanner
                  :client-id="getClientId()"
                  @qr-detected="handleQrDetected"
                  class="qr-scanner-inline"
                />
              </div>
              <small class="form-help">Required: Unique identifier for the item (scan QR or enter manually)</small>
            </div>
            <div class="form-group">
              <label for="location">Location</label>
              <select v-if="locations && locations.length > 0" id="location" v-model="newJob.client_location_id">
                <option value="">Select Location (optional)</option>
                <option v-for="location in locations" :key="location.id" :value="location.id">
                  {{ location.name }}
                </option>
              </select>
              <div v-else class="default-location-display">
                <span class="default-location-text">Default Location (Client Name)</span>
                <input type="hidden" v-model="newJob.client_location_id" value="">
              </div>
              <small class="form-help">
                Optional: Can be auto-filled from QR code. If no custom locations defined, this service request will be associated with your client name.
              </small>
            </div>
          </div>

          <div class="form-group">
            <label for="fault-description">Service Description *</label>
            <textarea id="fault-description" v-model="newJob.fault_description" required
                      rows="4" placeholder="Describe the service request in detail..."></textarea>
          </div>

          <div class="form-group">
              <label for="contact-person">Contact Person</label>
              <input type="text" id="contact-person" v-model="newJob.contact_person"
                   placeholder="Person to contact about this service request">
            <small class="form-help">Optional: Who should the technician contact?</small>
          </div>

          <!-- Image Upload Section -->
          <ImageUpload
            ref="imageUpload"
            :max-images="10"
            :max-file-size="10 * 1024 * 1024"
            @images-changed="handleImagesChanged"
          />

          <div class="form-actions">
            <button type="button" @click="showCreateJobModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="creatingJob">
              {{ creatingJob ? 'Requesting Service...' : 'Request Service' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Location Modal -->
    <div v-if="showAddLocationModal" class="modal-overlay" @click="showAddLocationModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Add New Location</h3>
          <button @click="showAddLocationModal = false" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="addLocation" class="location-form">
          <div class="form-group">
            <label for="location-name">Location Name *</label>
            <input type="text" id="location-name" v-model="newLocation.name" required
                   placeholder="e.g., Main Office, Warehouse, Branch Office">
          </div>

          <div class="form-group">
            <label for="location-address">Address</label>
            <textarea id="location-address" v-model="newLocation.address"
                      rows="3" placeholder="Street address, city, postal code"></textarea>
            <small class="form-help">Optional: Physical address of the location</small>
          </div>

          <div class="form-group">
            <label for="location-coordinates">GPS Coordinates</label>
            <input type="text" id="location-coordinates" v-model="newLocation.coordinates"
                   placeholder="e.g., -26.1234,28.5678 or 2FGJ+4Q villes, France">
            <small class="form-help">Optional: Latitude,longitude format or Google Plus Code for mapping integration</small>
          </div>

          <div class="form-group">
            <label for="location-access-rules">Site Information URL</label>
            <input type="url" id="location-access-rules" v-model="newLocation.access_rules"
                   placeholder="https://example.com/access-rules">
            <small class="form-help">Optional: Link to online safety and security protocols</small>
          </div>

          <div class="form-group">
            <label for="location-access-instructions">Access Instructions</label>
            <textarea id="location-access-instructions" v-model="newLocation.access_instructions"
                      rows="4" placeholder="Additional instructions for accessing this location (contact details, parking, etc.)"></textarea>
            <small class="form-help">Optional: Any additional information that technicians should know</small>
          </div>

          <div class="form-actions">
            <button type="button" @click="showAddLocationModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="addingLocation">
              {{ addingLocation ? 'Adding Location...' : 'Add Location' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Location Modal -->
    <div v-if="showEditLocationModal" class="modal-overlay" @click="showEditLocationModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Edit Location</h3>
          <button @click="showEditLocationModal = false" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="updateLocation" class="location-form">
          <div class="form-group">
            <label for="edit-location-name">Location Name *</label>
            <input type="text" id="edit-location-name" v-model="editingLocation.name" required
                   placeholder="e.g., Main Office, Warehouse, Branch Office">
          </div>

          <div class="form-group">
            <label for="edit-location-address">Address</label>
            <textarea id="edit-location-address" v-model="editingLocation.address"
                      rows="3" placeholder="Street address, city, postal code"></textarea>
            <small class="form-help">Optional: Physical address of the location</small>
          </div>

          <div class="form-group">
            <label for="edit-location-coordinates">GPS Coordinates</label>
            <input type="text" id="edit-location-coordinates" v-model="editingLocation.coordinates"
                   placeholder="e.g., -26.1234,28.5678 or 2FGJ+4Q villes, France">
            <small class="form-help">Optional: Latitude,longitude format or Google Plus Code for mapping integration</small>
          </div>

          <div class="form-group">
            <label for="edit-location-site-information">Site Information URL</label>
            <input type="url" id="edit-location-site-information" v-model="editingLocation.access_rules"
                   placeholder="https://example.com/site-information">
            <small class="form-help">Optional: Link to online site information or access protocols</small>
          </div>

          <div class="form-group">
            <label for="edit-location-access-instructions">Access Instructions</label>
            <textarea id="edit-location-access-instructions" v-model="editingLocation.access_instructions"
                      rows="4" placeholder="Additional instructions for accessing this location (contact details, parking, etc.)"></textarea>
            <small class="form-help">Optional: Any additional information that technicians should know</small>
          </div>

          <div class="form-actions">
            <button type="button" @click="showEditLocationModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary">
              Update Location
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Job Details Modal -->
    <div v-if="showJobDetailsModal" class="modal-overlay" @click="showJobDetailsModal = false">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3>Job Details: {{ selectedJob?.item_identifier || 'No Item ID' }}</h3>
          <button @click="showJobDetailsModal = false" class="close-btn">&times;</button>
        </div>

        <div v-if="selectedJob" class="job-details-content">
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

    <!-- Edit Job Modal -->
    <div v-if="showEditJobModal" class="modal-overlay" @click="showEditJobModal = false">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3>Edit Job: {{ editingJob?.item_identifier || 'No Item ID' }}</h3>
          <button @click="showEditJobModal = false" class="close-btn">&times;</button>
        </div>

        <form v-if="editingJob" @submit.prevent="updateJob" class="job-form">
          <!-- Status and Provider (provider assignment restricted to budget controllers) -->
          <div class="form-row">
            <!-- Provider assignment only for budget controllers -->
            <div class="form-group" v-if="userRole === 2">
              <label for="edit-provider">Assigned Provider</label>
              <select id="edit-provider" v-model="editingJob.assigned_provider_id" @change="onProviderSelected">
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
            <div class="form-group">
              <label for="edit-status">Status *</label>
              <select id="edit-status" v-model="editingJob.job_status" required>
                <option value="Reported">Reported</option>
                <option value="Assigned" :disabled="!editingJob.assigned_provider_id">Assigned</option>
                <option value="In Progress" disabled>In Progress</option>
                <option value="Completed" disabled>Completed</option>
                <option value="Declined" disabled>Declined</option>
                <option value="Quote Requested" :disabled="!editingJob.assigned_provider_id">Quote Requested</option>
              </select>
              <small v-if="!editingJob.assigned_provider_id && (editingJob.job_status === 'Assigned' || editingJob.job_status === 'Quote Requested')" class="form-help text-red-600">
                A service provider must be selected to set this status
              </small>
            </div>
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

          <div class="form-actions">
            <button type="button" @click="showEditJobModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="!clientProfile?.is_enabled">
              Update Job
            </button>
          </div>
        </form>
      </div>
    </div>



    <!-- Edit Business Profile Modal -->
    <div v-if="showEditProfileModal" class="modal-overlay" @click="showEditProfileModal = false">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3>Edit Business Profile</h3>
          <button @click="showEditProfileModal = false" class="close-btn">&times;</button>
        </div>

        <!-- Admin Disabled Notice -->
        <div v-if="editingProfile && !editingProfile.is_enabled" class="admin-disabled-notice">
          <div class="disabled-banner">
            <div class="disabled-icon">⚠️</div>
            <div class="disabled-content">
              <h4>Account Administratively Disabled</h4>
              <p>{{ editingProfile.disabled_reason || 'This account has been disabled by an administrator. Business profile edits are restricted.' }}</p>
            </div>
          </div>
        </div>

        <form @submit.prevent="updateClientProfile" class="profile-form">
          <!-- Basic Information -->
          <div class="form-section">
            <h4 class="section-title">Basic Information</h4>
            <div class="form-row">
              <div class="form-group">
                <label for="business-name">Business Name *</label>
                <input type="text" id="business-name" v-model="editingProfile.name" required
                       placeholder="Your business name">
              </div>
              <div class="form-group">
                <label for="business-website">Website</label>
                <input type="url" id="business-website" v-model="editingProfile.website"
                       placeholder="https://yourbusiness.com">
              </div>
            </div>

            <div class="form-group">
              <label for="business-address">Address</label>
              <textarea id="business-address" v-model="editingProfile.address" rows="3"
                        placeholder="Business address"></textarea>
            </div>

            <div class="form-group">
              <label for="business-description">Description</label>
              <textarea id="business-description" v-model="editingProfile.description" rows="4"
                        placeholder="Brief description of your business"></textarea>
            </div>
          </div>

          <!-- Manager Contact -->
          <div class="form-section">
            <h4 class="section-title">Manager Contact Information</h4>
            <div class="form-row">
              <div class="form-group">
                <label for="manager-name">Manager Name</label>
                <input type="text" id="manager-name" v-model="editingProfile.manager_name"
                       placeholder="Primary contact person">
              </div>
              <div class="form-group">
                <label for="manager-email">Manager Email</label>
                <input type="email" id="manager-email" v-model="editingProfile.manager_email"
                       placeholder="manager@yourbusiness.com">
              </div>
            </div>

            <div class="form-group">
              <label for="manager-phone">Manager Phone</label>
              <input type="tel" id="manager-phone" v-model="editingProfile.manager_phone"
                     placeholder="+27 12 345 6789">
            </div>
          </div>

          <!-- Business Details -->
          <div class="form-section">
            <h4 class="section-title">Business Registration Details</h4>
            <div class="form-row">
              <div class="form-group">
                <label for="vat-number">VAT Number</label>
                <input type="text" id="vat-number" v-model="editingProfile.vat_number"
                       placeholder="VAT registration number">
              </div>
              <div class="form-group">
                <label for="business-registration">Business Registration Number</label>
                <input type="text" id="business-registration" v-model="editingProfile.business_registration_number"
                       placeholder="Company registration number">
              </div>
            </div>

            <div class="form-group">
              <label for="business-status">Status</label>
              <select id="business-status" v-model="editingProfile.is_active" :disabled="!editingProfile.is_enabled">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
              <small v-if="!editingProfile.is_enabled" class="form-help text-red-600">
                Status changes are restricted when account is administratively disabled
              </small>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="showEditProfileModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="updatingProfile || !editingProfile?.is_enabled">
              {{ updatingProfile ? 'Updating Profile...' : 'Update Profile' }}
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
          <img :src="generateImageUrl(selectedImage)"
               :alt="selectedImage.original_filename"
               class="full-size-image">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ImageUpload from '@/components/ImageUpload.vue'
import QrScanner from '@/components/QrScanner.vue'
import { apiFetch, handleTokenExpiration } from '@/utils/api.js'

export default {
  name: 'ClientDashboard',
  components: {
    ImageUpload,
    QrScanner
  },
  data() {
    return {
      users: null, // Start as null to show loading state
      approvedProviders: null, // Start as null to show loading state
      jobs: null, // Start as null to show loading state
      locations: [], // Client locations for job creation
      availableRoles: [],
      userRole: null, // Store user's role
      userId: null, // Store user's ID
      isAdmin: false, // Whether user can manage users/providers
      loading: true,
      profileCompleteness: 0,
      clientProfile: null, // Client profile data
      clientProfileCompleteness: 0, // Client profile completeness percentage
      showAddUserModal: false,
      showEditUserModal: false,
      showCreateJobModal: false,
      showAssignModal: false,
      showAddLocationModal: false,
      showEditLocationModal: false,
      showEditProfileModal: false, // Client profile editing modal
      editingLocation: {
        id: null,
        name: '',
        address: '',
        coordinates: '',
        access_rules: '',
        access_instructions: ''
      },
      editingProfile: { // Client profile editing data
        name: '',
        address: '',
        website: '',
        description: '',
        manager_name: '',
        manager_email: '',
        manager_phone: '',
        vat_number: '',
        business_registration_number: '',
        is_active: true
      },
      showJobDetailsModal: false,
      showEditJobModal: false,
      addingUser: false,
      updatingUser: false,
      creatingJob: false,
      updatingProfile: false, // Client profile update loading state
      jobFilters: {
        status: '',
        location_id: '',
        provider_id: '',
        archive_status: 'active'
      },
      newJob: {
        client_location_id: '',
        item_identifier: '',
        fault_description: '',
        contact_person: ''
      },
      jobToAssign: null,
      selectedImages: [], // Array to store selected images from component
      newUser: {
        username: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        role_id: ''
      },
      editingUser: {
        id: null,
        username: '',
        email: '',
        mobile: '',
        role_id: null,
        newPassword: ''
      },
      newLocation: {
        name: '',
        address: '',
        coordinates: '',
        access_rules: '',
        access_instructions: ''
      },
      addingLocation: false,
      selectedJob: null,
      selectedImage: null,
      locationsViewMode: 'cards', // 'cards' or 'table'
      editingJob: null,
      originalJobStatus: null,
      originalProviderId: null,
      editingImages: [], // Array to store additional images for editing
      // Section collapse/expand state
      sectionsExpanded: {
        profile: false, // Profile section collapsed by default
        users: false,
        locations: false,
        providers: false,
        jobs: true // Jobs section expanded by default
      }
    }
  },
  mounted() {
    // Handle expired token on component mount
    if (handleTokenExpiration()) {
      return // Stop execution if token was expired and user was redirected
    }

    this.checkUserPermissions()
    this.loadClientProfile()
    this.loadUsers()
    this.loadApprovedProviders()
    this.loadAvailableRoles()
    this.loadLocations()
    this.loadJobs()
  },
  watch: {
    users() {
      this.calculateProfileCompleteness()
    },
    approvedProviders() {
      this.calculateProfileCompleteness()
    },
    locations() {
      this.calculateProfileCompleteness()
    },
    clientProfile() {
      this.calculateClientProfileCompleteness()
    },
    showEditProfileModal(newValue) {
      // Populate form fields with current profile data when modal opens
      if (newValue) {
        this.resetEditProfileForm()
      }
    }
  },
  methods: {
    checkUserPermissions() {
      try {
        const token = localStorage.getItem('token')

        if (token) {
          try {
            const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
            this.userRole = payload.role_id
            this.userId = payload.user_id
            // Site Budget Controller (role_id = 2) can manage users/providers
            // Reporting Employee (role_id = 1) cannot
            this.isAdmin = payload.role_id === 2
          } catch (decodeError) {
            console.error('Failed to decode JWT payload:', decodeError)
            this.isAdmin = false
            this.userId = null
          }
        } else {
          this.isAdmin = false
          this.userId = null
        }
      } catch (error) {
        console.error('Failed to decode token:', error)
        this.isAdmin = false
        this.userId = null
      }
    },

    async loadUsers() {
      try {
        const response = await apiFetch('/backend/api/client-users.php')

        if (response.ok) {
          const data = await response.json()
          this.users = data.users
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to load users')
      }
    },

    async loadApprovedProviders() {
      try {
        const response = await apiFetch('/backend/api/client-approved-providers.php')

        if (response.ok) {
          const data = await response.json()
          this.approvedProviders = data.approved_providers
        } else {
          console.error('Failed to load approved providers')
        }
      } catch (error) {
        console.error('Failed to load approved providers')
      }
    },

    async loadAvailableRoles() {
      // Load available roles for client users
      this.availableRoles = [
        { id: 1, name: 'Reporting Employee' },
        { id: 2, name: 'Site Budget Controller' }
      ]
    },

    async addUser() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.addingUser = true
      try {
        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'POST',
          body: JSON.stringify({
            username: this.newUser.username,
            email: this.newUser.email,
            first_name: this.newUser.first_name,
            last_name: this.newUser.last_name,
            phone: this.newUser.phone,
            role_id: this.newUser.role_id
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showAddUserModal = false
          this.resetNewUserForm()
          this.loadUsers()
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to add user')
      } finally {
        this.addingUser = false
      }
    },

    editUser(user) {
      this.editingUser = {
        id: user.id,
        username: user.username,
        email: user.email,
        mobile: user.phone || '',  // Use user.phone, not user.mobile
        role_id: user.role_id,
        newPassword: ''
      }
      this.showEditUserModal = true
    },

    async updateUser() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.updatingUser = true
      try {
        const updateData = {
          user_id: this.editingUser.id,
          email: this.editingUser.email,
          mobile: this.editingUser.mobile || null,
          role_id: this.editingUser.role_id
        }

        if (this.editingUser.newPassword) {
          updateData.password = this.editingUser.newPassword
        }

        const response = await apiFetch('/backend/api/client-users.php', {
          method: 'PUT',
          body: JSON.stringify(updateData)
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showEditUserModal = false
          this.loadUsers()
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to update user')
      } finally {
        this.updatingUser = false
      }
    },

    async deleteUser(user) {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      if (!confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
        return
      }

      try {
        const response = await apiFetch(`/backend/api/client-users.php?user_id=${user.id}`, {
          method: 'DELETE'
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.loadUsers()
        } else {
          this.handleError(await response.json())
        }
      } catch (error) {
        alert('Failed to delete user')
      }
    },

    canDeleteUser(user) {
      // Prevent deletion of the current user and ensure at least one admin remains
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}')
      return user.id !== currentUser.id
    },

    viewProviderJobs(provider) {
      // Filter jobs to show only those for the selected provider
      this.jobFilters.provider_id = provider.service_provider_id
      this.loadJobs()

      // Scroll to jobs section for better UX
      const jobsSection = this.$el.querySelector('.jobs-section')
      if (jobsSection) {
        jobsSection.scrollIntoView({ behavior: 'smooth' })
      }
    },

    resetNewUserForm() {
      this.newUser = {
        username: '',
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        role_id: ''
      }
    },

    getRoleClass(roleName) {
      const roleClasses = {
        'Reporting Employee': 'admin',
        'Site Budget Controller': 'controller'
      }
      return roleClasses[roleName] || 'user'
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },

    signOut() {
      localStorage.removeItem('token')
      this.$router.push('/')
    },

    handleError(error) {
      if (error.error) {
        alert(error.error)
      } else {
        alert('An error occurred')
      }
    },

    // Job-related methods
    async loadLocations() {
      try {
        const response = await apiFetch('/backend/api/client-locations.php')

        if (response.ok) {
          const data = await response.json()
          this.locations = data.locations
        } else {
          console.error('Failed to load locations')
        }
      } catch (error) {
        console.error('Failed to load locations:', error)
      }
    },

    async loadJobs() {
      try {
        const params = new URLSearchParams()

        if (this.jobFilters.status) params.append('status', this.jobFilters.status)
        if (this.jobFilters.location_id) params.append('location_id', this.jobFilters.location_id)
        if (this.jobFilters.provider_id) params.append('provider_id', this.jobFilters.provider_id)

        // For reporting employees, only show their own jobs
        if (this.userRole === 1) {
          params.append('user_id', this.userId)
        }

        const response = await apiFetch(`/backend/api/client-jobs.php?${params}`)

        if (response.ok) {
          let data = await response.json()
          let jobs = data.jobs

          // Filter by archive status on the frontend (since backend doesn't support it yet)
          if (this.jobFilters.archive_status === 'active') {
            jobs = jobs.filter(job => !job.archived_by_client)
          } else if (this.jobFilters.archive_status === 'archived') {
            jobs = jobs.filter(job => job.archived_by_client)
          }

          this.jobs = jobs
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

    canAssignJob(job) {
      return job.job_status === 'Reported'
    },

    canEditJob(job) {
      // Cannot edit archived jobs
      if (job.archived_by_client) {
        return false
      }

      // Reporting employees can edit their own jobs when status is 'Reported'
      if (this.userRole === 1 && job.job_status === 'Reported' && job.reporting_user_id === this.userId) {
        return true
      }

      // Budget controllers can edit when status is 'Reported', 'Declined', or 'Quote Requested'
      if (this.userRole === 2 && ['Reported', 'Declined', 'Quote Requested'].includes(job.job_status)) {
        return true
      }

      return false
    },

    canEditJobDetails(job) {
      // Only allow editing full details when status is 'Reported'
      return job.job_status === 'Reported'
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

    showAssignModal(job) {
      this.jobToAssign = job
      this.showAssignModal = true
      // This would open a modal to assign the job to a provider
      // For now, just show a simple prompt
      const providerOptions = this.approvedProviders.map(p => `${p.id}: ${p.name}`).join('\n')
      const providerId = prompt(`Select a provider to assign this job to:\n\n${providerOptions}`)

      if (providerId) {
        this.assignJob(job.id, parseInt(providerId))
      }
    },

    async assignJob(jobId, providerId) {
      // Check if client account is administratively disabled
      if (!this.clientProfile?.is_enabled) {
        alert('Cannot assign jobs when account is administratively disabled. Please contact support.')
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: jobId,
            assigned_provider_id: providerId,
            job_status: 'Assigned'
          })
        })

        if (response.ok) {
          alert('Job assigned successfully!')
          this.loadJobs() // Refresh the jobs list
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to assign job')
      }
    },

    // Job creation methods
    async createJob() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.creatingJob = true
      try {
        // First create the job
        const jobData = {
          client_location_id: this.newJob.client_location_id,
          item_identifier: this.newJob.item_identifier || null,
          fault_description: this.newJob.fault_description,
          contact_person: this.newJob.contact_person || null
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'POST',
          body: JSON.stringify(jobData)
        })

        if (response.ok) {
          const data = await response.json()
          const jobId = data.job_id

      // Upload images if any were selected - let the ImageUpload component handle this
      if (this.selectedImages.length > 0) {
        console.log('ClientDashboard: Starting image upload for job', jobId, 'with', this.selectedImages.length, 'images')
        try {
          console.log('ClientDashboard: Calling uploadImages with jobId:', jobId)
          await this.$refs.imageUpload.uploadImages(jobId)
          console.log('ClientDashboard: Image upload completed successfully')
        } catch (imageError) {
          console.error('ClientDashboard: Image upload failed:', imageError)
          // Show a warning but don't fail the job creation
          setTimeout(() => {
            alert('Job created successfully, but image upload failed. You can try uploading images again by editing the job.')
          }, 100)
        }
      } else {
        console.log('ClientDashboard: No images selected, skipping upload')
      }

          alert('Service request submitted successfully!')
          this.showCreateJobModal = false
          this.resetNewJobForm()
          this.loadJobs() // Refresh the jobs list
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to report fault')
      } finally {
        this.creatingJob = false
      }
    },





    handleImagesChanged(images) {
      this.selectedImages = images
    },

    handleEditImagesChanged(images) {
      this.editingImages = images
    },

    resetNewJobForm() {
      this.newJob = {
        client_location_id: '',
        item_identifier: '',
        fault_description: '',
        contact_person: ''
      }
      // Clear selected images in component
      if (this.$refs.imageUpload) {
        // The component manages its own images, so we just need to reset our local array
        this.selectedImages = []
      }
    },

    // Location management methods
    async addLocation() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      this.addingLocation = true
      try {
        const response = await apiFetch('/backend/api/client-locations.php', {
          method: 'POST',
          body: JSON.stringify({
            name: this.newLocation.name.trim(),
            address: this.newLocation.address ? this.newLocation.address.trim() : '',
            coordinates: this.newLocation.coordinates ? this.newLocation.coordinates.trim() : '',
            access_rules: this.newLocation.access_rules ? this.newLocation.access_rules.trim() : '',
            access_instructions: this.newLocation.access_instructions ? this.newLocation.access_instructions.trim() : ''
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showAddLocationModal = false
          this.resetNewLocationForm()
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to add location')
      } finally {
        this.addingLocation = false
      }
    },

    resetNewLocationForm() {
      this.newLocation = {
        name: '',
        address: '',
        coordinates: '',
        access_rules: '',
        access_instructions: ''
      }
    },

    async editLocation(location) {
      // Populate the editing form with current location data
      this.editingLocation = {
        id: location.id,
        name: location.name,
        address: location.address || '',
        coordinates: location.coordinates || '',
        access_rules: location.access_rules || '',
        access_instructions: location.access_instructions || ''
      }
      this.showEditLocationModal = true
    },

    async updateLocation() {
      const token = localStorage.getItem('token')
      if (!token) {
        alert('You are not logged in. Please refresh the page and log in again.')
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-locations.php', {
          method: 'PUT',
          body: JSON.stringify({
            location_id: this.editingLocation.id,
            name: this.editingLocation.name.trim(),
            address: this.editingLocation.address ? this.editingLocation.address.trim() : '',
            coordinates: this.editingLocation.coordinates ? this.editingLocation.coordinates.trim() : '',
            access_rules: this.editingLocation.access_rules ? this.editingLocation.access_rules.trim() : '',
            access_instructions: this.editingLocation.access_instructions ? this.editingLocation.access_instructions.trim() : ''
          })
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.showEditLocationModal = false
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to update location')
      }
    },

    async deleteLocation(location) {
      if (!confirm(`Are you sure you want to delete "${location.name}"? This can only be done if there are no associated fault reports.`)) {
        return
      }

      try {
        const response = await apiFetch(`/backend/api/client-locations.php?location_id=${location.id}`, {
          method: 'DELETE'
        })

        if (response.ok) {
          const data = await response.json()
          alert(data.message)
          this.loadLocations()
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to delete location')
      }
    },

    filterJobsByLocation(location) {
      // Set the location filter to this location
      this.jobFilters.location_id = location.id
      this.loadJobs()
    },

    openImageModal(image) {
      this.selectedImage = image
    },

    async editJob(job) {
      // Set the job being edited and store original values for comparison
      this.editingJob = { ...job }
      this.originalJobStatus = job.job_status
      this.originalProviderId = job.assigned_provider_id
      this.originalItemIdentifier = job.item_identifier
      this.originalFaultDescription = job.fault_description
      this.originalContactPerson = job.contact_person

      // Load job images if not already loaded
      if (!job.images) {
        try {
          const response = await apiFetch(`/backend/api/job-images.php?job_id=${job.id}`)

          if (response.ok) {
            const data = await response.json()
            this.editingJob.images = data.images || []
          } else {
            console.error('Failed to load job images')
            this.editingJob.images = []
          }
        } catch (error) {
          console.error('Failed to load job images:', error)
          this.editingJob.images = []
        }
      } else {
        this.editingJob.images = job.images
      }

      // Refresh approved providers list to ensure it's current
      this.loadApprovedProviders().then(() => {
        // Ensure the current assigned provider is still approved
        const isProviderStillApproved = this.approvedProviders.some(provider => provider.id == this.editingJob.assigned_provider_id)
        if (this.editingJob.assigned_provider_id && !isProviderStillApproved) {
          // Provider is no longer approved, clear the assignment
          this.editingJob.assigned_provider_id = null
        }
        this.showEditJobModal = true
      })
    },

    async updateJob() {
      // Validate that provider is selected for status changes that require it
      if ((this.editingJob.job_status === 'Assigned' || this.editingJob.job_status === 'Quote Requested') && !this.editingJob.assigned_provider_id) {
        alert('A service provider must be selected to set this status.')
        return
      }

      try {
        const updateData = {
          job_id: this.editingJob.id
        }

        // Only include fields that can be edited based on status and role
        if (this.canEditJobDetails(this.editingJob)) {
          // Full edit when status is 'Reported' - only add changed fields
          if (this.editingJob.item_identifier !== this.originalItemIdentifier) {
            updateData.item_identifier = this.editingJob.item_identifier || null
          }
          if (this.editingJob.fault_description !== this.originalFaultDescription) {
            updateData.fault_description = this.editingJob.fault_description
          }
          if (this.editingJob.contact_person !== this.originalContactPerson) {
            updateData.contact_person = this.editingJob.contact_person || null
          }
        }

        // Status and provider can always be edited (when allowed)
        if (this.editingJob.job_status !== this.originalJobStatus) {
          updateData.job_status = this.editingJob.job_status
        }

        if (this.editingJob.assigned_provider_id !== this.originalProviderId) {
          updateData.assigned_provider_id = this.editingJob.assigned_provider_id || null
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
          const response = await apiFetch('/backend/api/client-jobs.php', {
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
          await this.$refs.editImageUpload.uploadImages(this.editingJob.id)
        }

        alert('Job updated successfully!')
        this.showEditJobModal = false
        this.loadJobs()
      } catch (error) {
        alert('Failed to update job')
      }
    },

    getCurrentUserName() {
      // Get current user name from localStorage or users array
      try {
        const userData = localStorage.getItem('user')
        if (userData) {
          const user = JSON.parse(userData)
          return user.username || 'User'
        }

        // Fallback: find current user in users array
        if (this.users && this.users.length > 0 && this.userId) {
          const currentUser = this.users.find(u => u.id == this.userId)
          return currentUser ? currentUser.username : 'User'
        }

        return 'User'
      } catch (error) {
        return 'User'
      }
    },

    calculateProfileCompleteness() {
      let completeness = 0
      const totalCriteria = 4

      // Check if users exist
      if (this.users && this.users.length > 0) {
        completeness += 1
      }

      // Check if locations exist
      if (this.locations && this.locations.length > 0) {
        completeness += 1
      }

      // Check if approved providers exist
      if (this.approvedProviders && this.approvedProviders.length > 0) {
        completeness += 1
      }

      // Check if client profile is complete (25% weight)
      if (this.clientProfileCompleteness >= 70) {
        completeness += 1
      }

      this.profileCompleteness = Math.round((completeness / totalCriteria) * 100)
    },

    calculateClientProfileCompleteness() {
      if (!this.clientProfile) {
        this.clientProfileCompleteness = 0
        return
      }

      let score = 0
      const total = 100

      // Basic information (40 points)
      const basicFields = ['name', 'address', 'description']
      basicFields.forEach(field => {
        if (this.clientProfile[field]) score += 13
      })

      // Website (10 points)
      if (this.clientProfile.website) score += 10

      // Manager contact (30 points)
      const managerFields = ['manager_name', 'manager_email', 'manager_phone']
      managerFields.forEach(field => {
        if (this.clientProfile[field]) score += 10
      })

      // Business details (20 points)
      const businessFields = ['vat_number', 'business_registration_number']
      businessFields.forEach(field => {
        if (this.clientProfile[field]) score += 10
      })

      this.clientProfileCompleteness = Math.min(score, total)
    },

    async loadClientProfile() {
      try {
        const response = await apiFetch('/backend/api/client-profile.php')

        if (response.ok) {
          const data = await response.json()
          this.clientProfile = data.profile
          this.clientProfileCompleteness = data.profile_completeness
        } else {
          console.error('Failed to load client profile')
          this.clientProfile = null
        }
      } catch (error) {
        console.error('Failed to load client profile:', error)
        this.clientProfile = null
      }
    },

    async updateClientProfile() {
      this.updatingProfile = true
      try {
        const response = await apiFetch('/backend/api/client-profile.php', {
          method: 'PUT',
          body: JSON.stringify(this.editingProfile)
        })

        if (response.ok) {
          const data = await response.json()
          this.clientProfile = data.profile
          this.clientProfileCompleteness = data.profile_completeness
          this.showEditProfileModal = false
          this.resetEditProfileForm()
          alert('Business profile updated successfully!')
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert('Failed to update business profile')
      } finally {
        this.updatingProfile = false
      }
    },

    resetEditProfileForm() {
      if (this.clientProfile) {
        this.editingProfile = {
          name: this.clientProfile.name || '',
          address: this.clientProfile.address || '',
          website: this.clientProfile.website || '',
          description: this.clientProfile.description || '',
          manager_name: this.clientProfile.manager_name || '',
          manager_email: this.clientProfile.manager_email || '',
          manager_phone: this.clientProfile.manager_phone || '',
          vat_number: this.clientProfile.vat_number || '',
          business_registration_number: this.clientProfile.business_registration_number || '',
          is_active: this.clientProfile.is_active !== false
        }
      } else {
        this.editingProfile = {
          name: '',
          address: '',
          website: '',
          description: '',
          manager_name: '',
          manager_email: '',
          manager_phone: '',
          vat_number: '',
          business_registration_number: '',
          is_active: true
        }
      }
    },

    getOrganizationName() {
      return this.clientProfile?.name || 'Organization'
    },

    getRoleDisplayName(role) {
      const roleNames = {
        1: 'Reporting Employee',
        2: 'Site Budget Controller'
      }
      return roleNames[role] || 'User'
    },

    getRoleBadgeClass(role) {
      return role === 2 ? 'role-admin' : 'role-user'
    },

    handleUpgradeClick() {
      // For now, show a simple alert. In a real implementation, this would navigate to a premium upgrade page or modal
      alert('Upgrade to Premium feature coming soon! Contact sales for more information.')
    },

    // QR Scanner methods
    handleQrDetected(qrData) {
      console.log('QR data detected:', qrData)

      try {
        // Initialize extracted data tracking
        let extractedFields = {}
        let extractionMethod = 'structured' // 'structured' or 'fallback'

        // First, try to validate client ID if present
        if (qrData.clientId && qrData.clientId !== this.getClientId()) {
          alert(`QR code is for a different client (ID: ${qrData.clientId}). This QR code is not valid for your account.`)
          return
        }

        // Check if structured data was successfully parsed
        if (qrData.itemIdentifier && qrData.itemIdentifier.trim()) {
          // We have structured item data - use it
          this.newJob.item_identifier = qrData.itemIdentifier.trim()
          extractedFields.item = qrData.itemIdentifier.trim()
        } else {
          // No structured item data - check if we have any location data
          if (qrData.locationName && qrData.locationName.trim()) {
            // We have location data but no item identifier - use location as item identifier
            this.newJob.item_identifier = qrData.locationName.trim()
            extractedFields.item = qrData.locationName.trim()
            extractedFields.note = 'location_used_as_item'
          } else {
            // No structured data at all - check if qrData is available as string
            // This handles cases where parsing completely failed
            const rawQrString = arguments[0] // Get the original QR data string
            if (rawQrString && typeof rawQrString === 'string' && rawQrString.trim()) {
              this.newJob.item_identifier = rawQrString.trim()
              extractedFields.item = rawQrString.trim()
              extractedFields.note = 'raw_qr_used_as_item'
              extractionMethod = 'fallback'
            } else {
              // Complete failure - shouldn't happen but handle gracefully
              alert('Could not extract any usable data from QR code. Please enter the item identifier manually.')
              return
            }
          }
        }

        // Handle location matching (if location data is available)
        if (qrData.locationName && qrData.locationName.trim() && this.locations && this.locations.length > 0) {
          const matchingLocation = this.locations.find(location =>
            location.name.toLowerCase() === qrData.locationName.toLowerCase()
          )
          if (matchingLocation) {
            this.newJob.client_location_id = matchingLocation.id
            extractedFields.location = qrData.locationName.trim()
          } else {
            extractedFields.location_attempted = qrData.locationName.trim()
            extractedFields.location_note = 'location_not_found'
          }
        }

        // Generate user-friendly success message
        let message = 'QR code scanned successfully!'

        if (extractionMethod === 'structured') {
          message += ` Item: "${extractedFields.item}"`
          if (extractedFields.location) {
            message += `, Location: "${extractedFields.location}"`
          } else if (extractedFields.location_attempted) {
            message += `. Location "${extractedFields.location_attempted}" not found in your locations`
          }
        } else {
          if (extractedFields.note === 'location_used_as_item') {
            message += ` Used location "${extractedFields.item}" as item identifier`
          } else if (extractedFields.note === 'raw_qr_used_as_item') {
            message += ` Used QR code content as item identifier: "${extractedFields.item}"`
          }
        }

        alert(message)

      } catch (error) {
        console.error('Error processing QR data:', error)
        // Fallback: try to use raw QR data
        const rawData = arguments[0]
        if (rawData && typeof rawData === 'string' && rawData.trim()) {
          this.newJob.item_identifier = rawData.trim()
          alert(`QR code scanned! Used raw content as item identifier: "${rawData.trim()}"`)
        } else {
          alert('Error reading QR code. Please try again or enter the item identifier manually.')
        }
      }
    },

    // Test method for QR handling logic (can be removed after testing)
    testQrHandling() {
      console.log('Testing QR handling logic...')

      // Test cases for different QR scenarios
      const testCases = [
        // Full structured data
        { clientId: 1, itemIdentifier: 'TEST-001', locationName: 'Main Office' },
        // Item only
        { clientId: 1, itemIdentifier: 'TEST-002' },
        // Location only (will use location as item)
        { clientId: 1, locationName: 'Warehouse' },
        // No structured data (fallback to raw string)
        'RAW-QR-DATA-123',
        // Invalid client ID
        { clientId: 999, itemIdentifier: 'INVALID-CLIENT' }
      ]

      testCases.forEach((testData, index) => {
        console.log(`Test case ${index + 1}:`, testData)
        // This would normally be called by the QR scanner
        // For testing, we could modify the method to accept test data
      })
    },

    getClientId() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          return payload.entity_id
        }
      } catch (error) {
        console.error('Failed to get client ID from token:', error)
      }
      return null
    },

    // Section collapse/expand functionality
    toggleSection(sectionName) {
      this.sectionsExpanded[sectionName] = !this.sectionsExpanded[sectionName]
    },

    // Archive/unarchive job functionality
    async toggleArchiveJob(job) {
      const action = job.archived_by_client ? 'unarchive' : 'archive'
      const confirmMessage = `Are you sure you want to ${action} this job?`

      if (!confirm(confirmMessage)) {
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: job.id,
            archived_by_client: !job.archived_by_client
          })
        })

        if (response.ok) {
          // Update the job in the local array
          const jobIndex = this.jobs.findIndex(j => j.id === job.id)
          if (jobIndex !== -1) {
            this.jobs[jobIndex].archived_by_client = !job.archived_by_client
            this.jobs[jobIndex].updated_at = new Date().toISOString()
          }

          alert(`Job ${action}d successfully!`)
        } else {
          const errorData = await response.json()
          this.handleError(errorData)
        }
      } catch (error) {
        alert(`Failed to ${action} job`)
      }
    },

    // Generate image URL for displaying images
    generateImageUrl(image) {
      const token = localStorage.getItem('token')
      if (!token) {
        console.warn('No JWT token found for image access')
        return ''
      }
      return `/backend/api/serve-image.php?filename=${image.filename}&token=${encodeURIComponent(token)}`
    },

    // Open image in modal for full-size view
    openImageModal(image) {
      this.selectedImage = image
    },

    // Handle provider selection change - automatically set status to 'Assigned'
    onProviderSelected() {
      if (this.editingJob.assigned_provider_id && this.editingJob.job_status !== 'Assigned' && this.editingJob.job_status !== 'Quote Requested') {
        this.editingJob.job_status = 'Assigned'
      }
    }


  }
}
</script>

<style scoped>
.client-dashboard {
  max-width: 1400px;
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

/*.dashboard-header h1 {
  color: #333;
  margin-bottom: 10px;
}*/

.profile-completeness {
  display: flex;
  align-items: center;
  gap: 15px;
  position: relative;
  left: 32px;
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
/*
.dashboard-header p {
  color: #666;
  font-size: 1.1em;
}*/

.dashboard-content {
  display: grid;
  gap: 40px;
}

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

/* Users Section */
.users-section, .providers-section, .jobs-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users-grid, .providers-grid, .jobs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 15px;
}

/* Job Filters */
.job-filters {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 150px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 500;
  color: #666;
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  background: white;
}

/* Job Cards */
.job-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
  background: white;
}

.job-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.job-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.job-status {
  display: flex;
  align-items: center;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.reported {
  background: #ffc107;
  color: #212529;
}

.status-badge.assigned {
  background: #17a2b8;
  color: white;
}

.status-badge.in-progress {
  background: #007bff;
  color: white;
}

.status-badge.completed {
  background: #28a745;
  color: white;
}

.job-actions {
  display: flex;
  gap: 8px;
}

.job-content {
  padding: 15px;
}

.job-title {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 1.1em;
}

.job-description {
  color: #666;
  margin-bottom: 8px;
  line-height: 1.4;
}

.job-location {
  color: #666;
  margin-bottom: 12px;
  font-size: 0.9em;
}

.job-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  min-width: 120px;
}

.meta-label {
  font-size: 0.8em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.meta-value {
  font-size: 0.9em;
  color: #333;
  font-weight: 500;
}

.job-footer {
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.job-date {
  font-size: 0.85em;
  color: #666;
  margin: 0;
}

/* No Jobs State */
.no-jobs {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-jobs-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.no-jobs h3 {
  margin-bottom: 10px;
}

/* User Cards */
.user-card, .provider-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
}

.user-card:hover, .provider-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.user-header, .provider-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.user-avatar, .provider-logo {
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

.avatar-placeholder, .logo-placeholder {
  font-size: 1.5em;
}

.user-actions, .provider-actions {
  display: flex;
  gap: 8px;
}

.user-content, .provider-content {
  padding: 15px;
}

.user-name, .provider-name {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 1.1em;
}

.user-email, .provider-address {
  color: #666;
  margin-bottom: 8px;
  font-size: 0.9em;
}

.user-role {
  margin-bottom: 12px;
}

.role-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 500;
  text-transform: uppercase;
}

.role-badge.admin {
  background: #28a745;
  color: white;
}

.role-badge.controller {
  background: #17a2b8;
  color: white;
}

.provider-stats {
  display: flex;
  gap: 15px;
  margin-top: 12px;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 1.5em;
  font-weight: bold;
  color: #333;
  margin-bottom: 2px;
}

.stat-label {
  font-size: 0.8em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.user-footer, .provider-footer {
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.user-date, .approval-date {
  font-size: 0.85em;
  color: #666;
  margin: 0;
}

/* No Items States */
.no-users, .no-providers {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-users-icon, .no-providers-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.no-users h3, .no-providers h3 {
  margin-bottom: 10px;
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
.user-form {
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
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group input:disabled {
  background: #f8f9fa;
  color: #666;
}

.form-group textarea {
  width: 100%;
  min-height: 80px;
  resize: vertical;
  box-sizing: border-box;
}

.input-with-button {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.input-with-button input {
  flex: 1;
}

.qr-scanner-inline {
  flex-shrink: 0;
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

.btn-primary, .btn-secondary, .btn-danger {
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

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.9em;
}

/* Loading and Error States */
.loading-state, .error-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-icon {
  font-size: 3em;
  margin-bottom: 15px;
}

.loading-state h3, .error-state h3 {
  margin-bottom: 10px;
}

/* View Mode Toggle */
.header-left {
  align-items: left;
  gap: 20px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 15px;
}

.view-mode-toggle {
  display: flex;
  border: 1px solid #ddd;
  border-radius: 6px;
  overflow: hidden;
}

.view-mode-btn {
  padding: 8px 16px;
  border: none;
  background: white;
  color: #666;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.view-mode-btn:hover {
  background: #f8f9fa;
}

.view-mode-btn.active {
  background: #007bff;
  color: white;
}

.view-icon {
  font-size: 16px;
}

/* Locations Table View */
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

.location-name-cell .location-icon {
  color: #007bff;
  font-size: 16px;
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

/* Large Modal for Job Details */
.large-modal .modal-content {
  max-width: 900px;
  padding: 5px 10px;
}

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

/* Profile Section Styles */
.profile-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-content {
  margin-top: 15px;
}

.profile-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.profile-card {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.profile-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.profile-card-title {
  color: #333;
  font-size: 1.1em;
  font-weight: 600;
  margin: 0 0 15px 0;
  border-bottom: 2px solid #007bff;
  padding-bottom: 8px;
}

.profile-field {
  margin-bottom: 12px;
}

.profile-field label {
  display: block;
  font-size: 0.85em;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.profile-field span {
  color: #333;
  font-size: 0.95em;
  line-height: 1.4;
}

.profile-completeness-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #e3f2fd;
  border: 1px solid #2196f3;
  border-radius: 12px;
  padding: 8px 12px;
  min-width: 60px;
}

.completeness-percentage {
  font-size: 1.2em;
  font-weight: bold;
  color: #1976d2;
  line-height: 1;
}

.completeness-label {
  font-size: 0.7em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.active {
  background: #28a745;
  color: white;
}

.status-badge.inactive {
  background: #dc3545;
  color: white;
}

/* Admin Disabled Notice */
.admin-disabled-notice {
  background: #fff3cd;
  border: 1px solid #ffeeba;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.disabled-banner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.disabled-icon {
  font-size: 24px;
  color: #856404;
  flex-shrink: 0;
}

.disabled-content h4 {
  margin: 0 0 8px 0;
  color: #856404;
  font-size: 16px;
  font-weight: 600;
}

.disabled-content p {
  margin: 0;
  color: #856404;
  font-size: 14px;
  line-height: 1.4;
}

/* Profile Form Styles */
.profile-form {
  padding: 20px;
}

.form-section {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.section-title {
  color: #333;
  font-size: 1.1em;
  font-weight: 600;
  margin: 0 0 20px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #007bff;
}

.form-section .form-row {
  margin-bottom: 15px;
}

.form-section .form-group {
  margin-bottom: 15px;
}

/* No Profile State */
.no-profile {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.no-profile-icon {
  font-size: 4em;
  margin-bottom: 20px;
  opacity: 0.5;
}

.no-profile h3 {
  margin-bottom: 10px;
  color: #333;
}

/* Responsive Design */
@media (max-width: 768px) {
  .client-dashboard {
    padding: 10px;
  }

  .dashboard-content {
    gap: 20px;
  }

  .section-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .header-left, .header-right {
    flex-direction: column;
    gap: 10px;
  }

  .view-mode-toggle {
    order: -1;
  }

  .users-grid, .providers-grid, .locations-grid, .profile-grid {
    grid-template-columns: 1fr;
  }

  .user-header, .provider-header {
    flex-direction: column;
    gap: 10px;
  }

  .user-actions, .provider-actions {
    align-self: flex-start;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

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

  .locations-table {
    font-size: 12px;
  }

  .locations-table th,
  .locations-table td {
    padding: 10px;
  }

  .edit-details-section,
  .readonly-details-section {
    padding: 15px;
  }

  .info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }

  .profile-completeness-badge {
    min-width: 50px;
    padding: 6px 10px;
  }

  .completeness-percentage {
    font-size: 1em;
  }
}


</style>
