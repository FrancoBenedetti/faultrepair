<template>
  <div class="edit-job-page min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
          <!-- Breadcrumb Navigation -->
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <router-link
              :to="from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard'"
              class="text-blue-600 hover:text-blue-800"
              @click="returnToDashboard"
            >
              {{ from === 'service-provider' ? 'Service Provider Dashboard' : 'Client Dashboard' }}
            </router-link>
            <span class="text-gray-400">></span>
            <span class="text-gray-900 font-medium">
              Edit Job: {{ job?.item_identifier || 'Loading...' }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button
              @click="cancelEditing"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
              :disabled="saving"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="text-center">
          <div class="loading-spinner w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p class="text-gray-600">Loading job details...</p>
        </div>
      </div>

      <!-- Job Edit Form -->
      <form v-else-if="job" @submit.prevent="handleFormSubmit" class="space-y-8">
        <!-- Job Origin Area (Read-Only for Reported Jobs) -->
        <div v-if="job.job_status === 'Reported'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="job-origin-area">
            <div class="origin-header">
              <span class="material-icon user-icon">person</span>
              <div class="origin-info">
                <div class="origin-text">
                  Reported by {{ getReportedByFullName() }} on {{ formatDate(job.created_at) }}
                </div>
                <div class="origin-status">Status: {{ job.job_status }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Sections Container -->
        <div class="edit-sections space-y-8">
          <!-- Job Details Section -->
          <div v-if="job.job_status === 'Reported' || canEditJobDetails" class="job-section job-details-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="section-header mb-6">
              <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
                <span class="material-icon section-icon text-blue-600">edit_note</span>
                Job Details
              </h3>
            </div>

            <div class="section-content">
              <div class="form-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="form-group">
                  <label for="item-identifier" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">build</span>
                    Item Identifier <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="item-identifier"
                    type="text"
                    v-model="editableJob.item_identifier"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    maxlength="100"
                    placeholder="Enter item identifier..."
                    required
                    :readonly="userRole === 3 || userRole === 4"
                  />
                </div>

                <div v-if="userRole === 2" class="form-group">
                  <label for="location-select" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">location_on</span>
                    Location <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="location-select"
                    v-model="selectedLocationId"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loadingLocations"
                  >
                    <option value="">{{ loadingLocations ? 'Loading...' : '-- Select Location --' }}</option>
                    <option v-for="location in availableLocations" :key="location.id" :value="location.id">
                      {{ location.name }}
                      <span v-if="location.address" class="location-address"> – {{ location.address }}</span>
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="contact-person" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">contact_mail</span>
                    Contact Person
                  </label>
                  <input
                    id="contact-person"
                    type="text"
                    v-model="editableJob.contact_person"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    maxlength="100"
                    placeholder="Contact person for this job..."
                    :readonly="userRole === 3 || userRole === 4"
                  />
                </div>

                <div class="form-group lg:col-span-2">
                  <label for="fault-description" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">description</span>
                    Fault Description <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="fault-description"
                    v-model="editableJob.fault_description"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="4"
                    maxlength="1000"
                    placeholder="Describe the fault or issue in detail..."
                    required
                    :readonly="userRole === 3 || userRole === 4"
                  ></textarea>
                </div>
              </div>

              <!-- Image Upload Area -->
              <div class="image-upload-area mt-8">
                <div class="border-t border-gray-200 pt-6">
                  <h4 class="text-lg font-semibold text-gray-900 mb-4">Images</h4>
                  <ImageUpload
                    ref="imageUpload"
                    :max-images="10"
                    :max-file-size="10 * 1024 * 1024"
                    :existing-images="existingImages"
                    @images-changed="handleImagesChanged"
                  />
                </div>
              </div>

              <!-- Section Actions -->
              <div class="section-actions flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="saveAndContinue('job-details')"
                  :disabled="saving"
                  class="btn-filled bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  Save & Continue
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="btn-outlined border-gray-400 text-gray-600 px-4 py-2.5 rounded-lg font-medium hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2"
                >
                  Save & Close
                </button>
              </div>
            </div>
          </div>

          <!-- Platform Provider Service Workflow (for roles 3 & 4 on non-XS jobs) -->
          <div v-if="(userRole === 3 || userRole === 4) && job?.assigned_provider_participant_id && !availableProviders?.some(p => p.service_provider_id === job.assigned_provider_participant_id && p.provider_type === 'XS')" class="job-section service-provider-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="section-header mb-6">
              <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
                <span class="material-icon section-icon text-blue-600">assignment</span>
                Service Provider Actions
              </h3>
              <p class="text-sm text-gray-600 mt-2">{{ getServiceProviderTransitionDescription() }}</p>
            </div>

            <div class="transition-buttons-grid">
              <!-- Role 3 (Admin) - Assigned Jobs -->
              <button
                type="button"
                v-if="userRole === 3 && job.job_status === 'Assigned'"
                @click="initiateSPTransition('Declined')"
                class="transition-action-btn status-declined-sp"
                :disabled="saving"
              >
                <span class="btn-icon">🙅</span>
                Decline Job
              </button>

              <button
                type="button"
                v-if="userRole === 3 && job.job_status === 'Assigned'"
                @click.stop="openTechnicianAssignment"
                class="transition-action-btn status-in-progress-sp"
                :disabled="saving"
              >
                <span class="btn-icon">▶️</span>
                Start Work
              </button>

              <!-- Role 3 (Admin) - Incomplete Jobs -->
              <button
                type="button"
                v-if="userRole === 3 && job.job_status === 'Incomplete'"
                @click.stop="openTechnicianAssignment"
                class="transition-action-btn status-in-progress-sp"
                :disabled="saving"
              >
                <span class="btn-icon">▶️</span>
                Restart Work
              </button>

              <!-- Role 3 & 4 (In Progress Jobs) -->
              <button
                type="button"
                v-if="(userRole === 3 || userRole === 4) && job.job_status === 'In Progress'"
                @click="initiateSPTransition('Completed')"
                class="transition-action-btn status-completed-sp"
                :disabled="saving"
              >
                <span class="btn-icon">✅</span>
                Mark Completed
              </button>

              <button
                type="button"
                v-if="(userRole === 3 || userRole === 4) && job.job_status === 'In Progress'"
                @click="initiateSPTransition('Cannot repair')"
                class="transition-action-btn status-cannot-repair-sp"
                :disabled="saving"
              >
                <span class="btn-icon">🚫</span>
                Cannot Repair
              </button>
            </div>
          </div>

          <!-- Client Actions for S-Type Provider Jobs -->
          <div v-if="userRole === 2 && job?.assigned_provider_participant_id && !isXSProviderJob && ['Completed', 'Cannot repair'].includes(job.job_status)" class="job-section client-review-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="section-header mb-6">
              <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
                <span class="material-icon section-icon text-blue-600">rate_review</span>
                Review & Confirm Work
              </h3>
              <p class="text-sm text-gray-600 mt-2">The service provider has updated the job status. Please review and select the next action.</p>
            </div>

            <div class="transition-buttons-grid">
              <!-- Actions for 'Completed' status -->
              <template v-if="job.job_status === 'Completed'">
                <button type="button" @click="initiateClientTransition('Confirmed')" class="transition-action-btn status-confirmed">
                  <span class="btn-icon">👍</span>
                  Confirm Completion
                </button>
                <button type="button" @click="initiateClientTransition('Incomplete')" class="transition-action-btn status-incomplete">
                  <span class="btn-icon">🔄</span>
                  Mark as Incomplete
                </button>
              </template>

              <!-- Actions for 'Cannot repair' status -->
              <template v-if="job.job_status === 'Cannot repair'">
                <button type="button" @click="initiateClientTransition('Confirmed')" class="transition-action-btn status-confirmed">
                  <span class="btn-icon">👍</span>
                  Acknowledge & Close
                </button>
                <button type="button" @click="initiateClientTransition('Incomplete')" class="transition-action-btn status-incomplete">
                  <span class="btn-icon">🔄</span>
                  Request Review
                </button>
                <button type="button" @click="showReassignmentForm = true" class="transition-action-btn status-reassign">
                  <span class="btn-icon">🔄</span>
                  Reassign Provider
                </button>
              </template>
            </div>
          </div>

          <!-- Assignment Workflow - Role 2 Users Can Assign Provider & Transition Reported/Declined Jobs -->
          <div v-if="(job.job_status === 'Reported' || job.job_status === 'Declined') && userRole === 2" class="job-section assignment-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="section-header mb-6">
              <h3 class="section-title text-xl font-semibold text-gray-900 flex items-center gap-3">
                <span class="material-icon section-icon text-blue-600">assignment_ind</span>
                Provider Assignment & Job Transition
              </h3>
              <p class="text-sm text-gray-600 mt-2">Select a service provider and choose how to proceed with this job.</p>
            </div>

            <div v-if="job.job_status === 'Declined'" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400">
              <div class="flex items-center">
                <span class="material-icon text-yellow-600 mr-2">info</span>
                <div>
                  <strong class="text-yellow-900">This job was declined by the previous provider.</strong>
                  <p class="text-yellow-700 text-sm">You can now reassign it to a different provider, request a new quote, or reject the job.</p>
                </div>
              </div>
            </div>

            <div v-if="!availableProviders || availableProviders.length === 0" class="no-providers-alert mb-6">
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                  <span class="material-icon text-yellow-600 mr-2">warning</span>
                  <div>
                    <strong class="text-yellow-900">No Approved Providers Available</strong>
                    <p class="text-yellow-700 text-sm">You need to approve at least one service provider in the "Approved Providers" section before you can transition jobs. No transitions are possible until providers are approved.</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="provider-assignment-workflow">
              <!-- Step 1: Provider Selection (Required for Service/Quote requests) -->
              <div class="provider-selection-step mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Step 1: Select Service Provider</h4>
                <div class="form-group">
                  <select
                    v-model.number="selectedProviderForAssignment"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="saving"
                  >
                    <option :value="null">-- Choose a service provider --</option>
                    <option v-for="provider in availableProviders" :key="provider.id" :value="provider.id">
                      {{ provider.name }}
                      <span v-if="provider.provider_type === 'XS'" class="text-orange-600 ml-2">(External)</span>
                      <span v-if="provider.address" class="text-gray-500 ml-2">- {{ provider.address }}</span>
                    </option>
                  </select>
                  <div class="mt-4 flex justify-between items-center">
                      <p class="text-sm text-gray-600">Required for requesting service or quote.</p>
                      <button
                        type="button"
                        @click="saveProviderAssignment"
                        :disabled="saving || !selectedProviderForAssignment || job.assigned_provider_participant_id === selectedProviderForAssignment"
                        class="btn-outlined border-gray-400 text-gray-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Save Provider
                      </button>
                  </div>
                </div>
              </div>

              <!-- Step 1.5: Transition Notes (for XS Providers) -->
              <div v-if="isAssigningToXSProvider" class="provider-selection-step mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Step 1.5: Add Transition Notes</h4>
                 <div class="form-group">
                  <label for="assignment-transition-notes" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">comment</span>
                    Transition Notes <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="assignment-transition-notes"
                    v-model="transitionNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="3"
                    placeholder="A note is required to document the state change for this external provider job..."
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">For XS provider jobs, a note is always required to document the reason for the status change.</p>
                </div>
              </div>

              <!-- Step 2: Transition Options -->
              <div class="transition-options-step">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Step 2: Choose Action</h4>

                <div class="transition-buttons-grid">
                  <!-- Request Service (Direct Assignment) -->
                  <button
                    type="button"
                    @click="requestService()"
                    class="transition-action-btn status-service-requested"
                    :disabled="saving || !selectedProviderForAssignment"
                  >
                    <span class="btn-icon">🔧</span>
                    Request Service<br><small class="text-xs opacity-75">(Direct Assignment)</small>
                  </button>

                  <!-- Request Quote -->
                  <button
                    type="button"
                    @click="requestQuote()"
                    class="transition-action-btn status-quote-requested"
                    :disabled="saving || !selectedProviderForAssignment"
                  >
                    <span class="btn-icon">📝</span>
                    Request Quote<br><small class="text-xs opacity-75">(Quotation Required)</small>
                  </button>

                  <!-- Reject Job (No provider needed) -->
                  <button
                    type="button"
                    @click="rejectJob()"
                    class="transition-action-btn status-rejected"
                    :disabled="saving"
                  >
                    <span class="btn-icon">❌</span>
                    Reject Job<br><small class="text-xs opacity-75">(Terminate Request)</small>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- External Service Provider Actions (XS jobs) -->
          <div v-if="job?.assigned_provider_participant_id && userRole === 2 && availableProviders?.some(p => p.service_provider_id === job.assigned_provider_participant_id && p.provider_type === 'XS')" class="job-section xs-provider-section bg-orange-50 border border-orange-200 rounded-lg shadow-sm p-6">
            <div class="section-header mb-6">
              <h3 class="section-title text-xl font-semibold text-orange-900 flex items-center gap-3">
                <span class="material-icon section-icon text-orange-600">settings</span>
                External Provider Mode
                <span class="text-sm bg-orange-100 px-2 py-1 rounded-full">External Provider</span>
              </h3>
              <p class="text-sm text-orange-700 mt-2">{{ getTransitionDescription() }}</p>
            </div>

            <div class="form-group my-6">
              <label for="xs-transition-notes" class="form-label flex items-center gap-2 mb-2">
                <span class="material-icon field-icon text-gray-500">comment</span>
                Transition Notes <span class="text-red-500">*</span>
              </label>
              <textarea
                id="xs-transition-notes"
                v-model="transitionNotes"
                class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                rows="3"
                placeholder="A note is required to document the state change for this external provider job..."
                required
              ></textarea>
              <p class="text-sm text-gray-600 mt-1">For XS provider jobs, a note is always required to document the reason for the status change.</p>
            </div>

            <div class="transition-buttons-grid">
              <!-- Assigned status: can go to In Progress or Declined -->
              <button
                type="button"
                v-if="job.job_status === 'Assigned'"
                @click="initiateTransition('In Progress')"
                class="transition-action-btn status-in-progress"
                :disabled="saving"
              >
                <span class="btn-icon">▶️</span>
                Mark In Progress
              </button>

              <button
                type="button"
                v-if="job.job_status === 'Assigned'"
                @click="initiateTransition('Declined')"
                class="transition-action-btn status-declined"
                :disabled="saving"
              >
                <span class="btn-icon">🙅</span>
                Decline Job
              </button>

              <!-- In Progress can go to Completed -->
              <button
                type="button"
                v-if="job.job_status === 'In Progress'"
                @click="initiateTransition('Completed')"
                class="transition-action-btn status-completed"
                :disabled="saving"
              >
                <span class="btn-icon">✅</span>
                Mark Completed
              </button>

              <!-- Completed can be marked Incomplete -->
              <button
                type="button"
                v-if="job.job_status === 'Completed'"
                @click="initiateTransition('Incomplete')"
                class="transition-action-btn status-incomplete"
                :disabled="saving"
              >
                <span class="btn-icon">🔄</span>
                Mark Incomplete
              </button>

              <!-- Incomplete can be marked In Progress again -->
              <button
                type="button"
                v-if="job.job_status === 'Incomplete'"
                @click="initiateTransition('In Progress')"
                class="transition-action-btn status-in-progress"
                :disabled="saving"
              >
                <span class="btn-icon">➡️</span>
                Continue Work
              </button>

              <!-- Cannot repair option -->
              <button
                type="button"
                v-if="job.job_status === 'In Progress'"
                @click="initiateTransition('Cannot repair')"
                class="transition-action-btn status-cannot-repair"
                :disabled="saving"
              >
                <span class="btn-icon">🚫</span>
                Cannot Repair
              </button>

              <!-- Reassign Provider option (only for Cannot repair status) -->
              <button
                type="button"
                v-if="job.job_status === 'Cannot repair' && userRole === 2"
                @click="showReassignmentForm = true"
                class="transition-action-btn status-reassign"
                :disabled="saving"
              >
                <span class="btn-icon">🔄</span>
                Reassign Provider
              </button>

              <!-- Request Review option for Cannot repair -->
              <button
                type="button"
                v-if="job.job_status === 'Cannot repair'"
                @click="initiateTransition('Incomplete')"
                class="transition-action-btn status-incomplete"
                :disabled="saving"
              >
                <span class="btn-icon">🔄</span>
                Request Review
              </button>

              <!-- Confirm option for Cannot repair -->
              <button
                type="button"
                v-if="job.job_status === 'Cannot repair'"
                @click="initiateTransition('Confirmed')"
                class="transition-action-btn status-confirmed"
                :disabled="saving"
              >
                <span class="btn-icon">👍</span>
                Confirm Receipt
              </button>

              <!-- Complete job option (only for Completed status) -->
              <button
                type="button"
                v-if="job.job_status === 'Completed'"
                @click="initiateTransition('Confirmed')"
                class="transition-action-btn status-confirmed"
                :disabled="saving"
              >
                <span class="btn-icon">🏁</span>
                Complete Job
              </button>
            </div>
          </div>

          <!-- Technician Assignment Overlay for "Start Work" action -->
          <div v-if="showTechnicianAssignment" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
              <h4 class="text-xl font-semibold text-gray-900 mb-4">Start Work on Job</h4>
              <p class="text-gray-600 mb-6">Select a technician and provide instructions to begin work on this job.</p>

              <div class="form-grid space-y-4">
                <!-- Technician Selection -->
                <div class="form-group">
                  <label for="sp-tech-select" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">person</span>
                    Assign Technician <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="sp-tech-select"
                    v-model="selectedTechnicianId"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">-- Select Technician --</option>
                    <option v-for="tech in technicians" :key="tech.id" :value="tech.userId">
                      {{ tech.first_name || tech.username }} {{ tech.last_name || '' }}
                    </option>
                  </select>
                  <p class="text-sm text-gray-600 mt-1">A technician must be assigned to start work on this job.</p>
                </div>

                <!-- Technician Instructions -->
                <div class="form-group">
                  <label for="sp-tech-notes" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">assignment</span>
                    Technician Instructions
                  </label>
                  <textarea
                    id="sp-tech-notes"
                    v-model="technicianNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="4"
                    placeholder="Provide detailed instructions for the technician working on this job..."
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">Optionally provide specific instructions for the assigned technician.</p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 justify-end mt-6">
                <button
                  @click="showTechnicianAssignment = false"
                  class="btn-outlined border-gray-400 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  @click="startWorkOnJob"
                  :disabled="!selectedTechnicianId || saving"
                  class="btn-filled bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                  <span class="material-icon-sm mr-2">play_arrow</span>
                  {{ saving ? 'Starting Work...' : 'Start Work' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Service Provider Transition Notes Forms -->
          <div v-if="pendingSPTransition" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
              <h4 class="text-xl font-semibold text-gray-900 mb-4">
                Confirm "{{ pendingSPTransition }}" Action
              </h4>

              <!-- Declined Confirmation -->
              <div v-if="pendingSPTransition === 'Declined'">
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400">
                  <div class="flex items-center">
                    <span class="material-icon text-red-600 mr-2">warning</span>
                    <div>
                      <strong class="text-red-900">Declining this job</strong>
                      <p class="text-red-700 text-sm">will terminate the service request. This action cannot be undone and the job will be returned to the client status.</p>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="decline-reason" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">comment</span>
                    Reason for Decline <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="decline-reason"
                    v-model="spTransitionNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="3"
                    placeholder="Please explain why this job is being declined..."
                    required
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">This reason will be documented and sent to the client.</p>
                </div>
              </div>

              <!-- Completed Confirmation -->
              <div v-if="pendingSPTransition === 'Completed'">
                <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-400">
                  <div class="flex items-center">
                    <span class="material-icon text-green-600 mr-2">check_circle</span>
                    <div>
                      <strong class="text-green-900">Marking as completed</strong>
                      <p class="text-green-700 text-sm">will notify the client that work has finished and allow them to confirm receipt.</p>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="completion-notes" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">note</span>
                    Completion Notes
                  </label>
                  <textarea
                    id="completion-notes"
                    v-model="spTransitionNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="3"
                    placeholder="Optional: Add any completion details or notes..."
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">Optional notes about the completed work.</p>
                </div>
              </div>

              <!-- Cannot Repair Confirmation -->
              <div v-if="pendingSPTransition === 'Cannot repair'">
                <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-400">
                  <div class="flex items-center">
                    <span class="material-icon text-yellow-600 mr-2">build</span>
                    <div>
                      <strong class="text-yellow-900">Marking as "cannot repair"</strong>
                      <p class="text-yellow-700 text-sm">will notify the client that this item cannot be repaired and allow them to confirm receipt or reassignment.</p>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="cannot-repair-reason" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">comment</span>
                    Explanation <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="cannot-repair-reason"
                    v-model="spTransitionNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="3"
                    placeholder="Please explain why this item cannot be repaired..."
                    required
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">A detailed explanation is required for transparency with the client.</p>
                </div>
              </div>

              <!-- Confirmation Action Buttons -->
              <div class="flex gap-3 justify-end mt-6">
                <button
                  @click="cancelSPTransition"
                  class="btn-outlined border-gray-400 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  @click="confirmSPTransition"
                  :disabled="(pendingSPTransition !== 'Completed' && !spTransitionNotes.trim()) || saving"
                  class="btn-filled bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                  <span class="material-icon-sm mr-2">{{ getConfirmationIcon(pendingSPTransition) }}</span>
                  {{ saving ? 'Updating...' : `Confirm ${pendingSPTransition}` }}
                </button>
              </div>
            </div>
          </div>

          <!-- Client Transition Notes Form -->
          <div v-if="pendingClientTransition" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
              <h4 class="text-xl font-semibold text-gray-900 mb-4">
                Confirm "{{ pendingClientTransition }}" Action
              </h4>

              <!-- Incomplete Confirmation -->
              <div v-if="pendingClientTransition === 'Incomplete'">
                <div class="mb-4 p-4 bg-orange-50 border-l-4 border-orange-400">
                  <div class="flex items-center">
                    <span class="material-icon text-orange-600 mr-2">warning</span>
                    <div>
                      <strong class="text-orange-900">Marking as Incomplete</strong>
                      <p class="text-orange-700 text-sm">will return the job to the service provider for further work. Please provide clear notes about what is still required.</p>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="incomplete-reason" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">comment</span>
                    Reason for Incomplete Status <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="incomplete-reason"
                    v-model="clientTransitionNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="3"
                    placeholder="Please explain why the job is considered incomplete..."
                    required
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">This reason will be sent to the service provider.</p>
                </div>
              </div>

              <!-- Confirmed Confirmation -->
              <div v-if="pendingClientTransition === 'Confirmed'">
                <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-400">
                  <div class="flex items-center">
                    <span class="material-icon text-green-600 mr-2">check_circle</span>
                    <div>
                      <strong class="text-green-900">Confirming Job Completion</strong>
                      <p class="text-green-700 text-sm">This will close the job. This action cannot be undone.</p>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="confirmation-notes" class="form-label flex items-center gap-2 mb-2">
                  <span class="material-icon field-icon text-gray-500">note</span>
                  Confirmation Notes
                  </label>
                  <textarea
                    id="confirmation-notes"
                    v-model="clientTransitionNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="3"
                    placeholder="Optional: Add any final comments..."
                  ></textarea>
                </div>
              </div>

              <!-- Confirmation Action Buttons -->
              <div class="flex gap-3 justify-end mt-6">
                <button
                  @click="cancelClientTransition"
                  class="btn-outlined border-gray-400 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  @click="confirmClientTransition"
                  :disabled="(pendingClientTransition === 'Incomplete' && !clientTransitionNotes.trim()) || saving"
                  class="btn-filled bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                  <span class="material-icon-sm mr-2">{{ getConfirmationIcon(pendingClientTransition) }}</span>
                  {{ saving ? 'Updating...' : `Confirm ${pendingClientTransition}` }}
                </button>
              </div>
            </div>
          </div>

          <!-- Provider Reassignment Overlay -->
          <div v-if="showReassignmentForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-3">
                <span class="material-icon text-blue-600">sync</span>
                Reassign to Different Provider
              </h4>
              <p class="text-gray-600 mb-6">Select a new approved service provider for this job. The reassignment reason will be documented in the job history.</p>

              <div class="form-grid space-y-4">
                <!-- Provider Selection -->
                <div class="form-group">
                  <label for="reassign-provider-select" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">business</span>
                    Select New Provider <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="reassign-provider-select"
                    v-model="selectedReassignProviderId"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">-- Choose a different provider --</option>
                    <option
                      v-for="provider in availableProviders"
                      :key="provider.service_provider_id"
                      :value="provider.service_provider_id"
                      :disabled="provider.service_provider_id == job.assigned_provider_participant_id"
                    >
                      {{ provider.name }}
                      <span v-if="provider.provider_type === 'XS'" class="text-orange-600 ml-2">(External)</span>
                      <span v-if="provider.service_provider_id == job.assigned_provider_participant_id" class="text-gray-400 ml-2">(Current)</span>
                    </option>
                  </select>
                  <p class="text-sm text-gray-600 mt-1">Only showing providers different from the current one. The current provider is marked as "(Current)" and disabled.</p>
                </div>

                <!-- Reassignment Reason -->
                <div class="form-group">
                  <label for="reassignment-reason" class="form-label flex items-center gap-2 mb-2">
                    <span class="material-icon field-icon text-gray-500">comment</span>
                    Reason for Reassignment <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="reassignment-reason"
                    v-model="reassignmentNotes"
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    rows="4"
                    placeholder="Explain why this job is being reassigned to a different provider..."
                    required
                  ></textarea>
                  <p class="text-sm text-gray-600 mt-1">This reason will be documented in the job history and sent as notes to the new provider.</p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 justify-end mt-6">
                <button
                  @click="showReassignmentForm = false"
                  class="btn-outlined border-gray-400 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  @click="reassignProvider"
                  :disabled="!selectedReassignProviderId || !reassignmentNotes.trim() || saving"
                  class="btn-filled bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                  <span class="material-icon-sm mr-2">sync</span>
                  {{ saving ? 'Reassigning...' : 'Reassign Provider' }}
                </button>
              </div>
            </div>
          </div>

        </div>
      </form>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-16">
        <div class="error-icon mx-auto w-16 h-16 flex items-center justify-center bg-red-100 rounded-full mb-4">
          <span class="material-icon text-red-600">error</span>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Error Loading Job</h3>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <button
          @click="returnToDashboard"
          class="btn-filled bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
        >
          Return to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'
import ImageUpload from '@/components/ImageUpload.vue'

export default {
  name: 'EditJob',
  components: {
    ImageUpload
  },
  props: {
    jobId: {
      type: Number,
      required: true
    },
    from: {
      type: String,
      default: 'client'
    },
    scrollPosition: {
      type: [String, Number],
      default: 0
    }
  },
  data() {
    return {
      job: null,
      loading: true,
      saving: false,
      error: null,
      editableJob: {},
      existingImages: [],
      newImages: [],
      availableLocations: [],
      loadingLocations: false,
      selectedLocationId: '',
      userRole: null,

      // Service Provider Workflow Data
      availableProviders: [],
      selectedProviderForAssignment: null,
      showTechnicianAssignment: false,
      pendingSPTransition: null,
      selectedTechnicianId: '',
      technicianNotes: '',
      spTransitionNotes: '',
      technicians: [],

      // Client Workflow Data
      pendingClientTransition: null,
      clientTransitionNotes: '',

      // XS Provider Transition Data
      selectedTransition: null,
      transitionNotes: '',
      selectedReassignProviderId: '',
      reassignmentNotes: '',
      showReassignmentForm: false
    }
  },
  computed: {
    // Check if this job is assigned to an external service provider (XS)
    isXSProviderJob() {
      return this.job?.assigned_provider_participant_id &&
             this.userRole === 2 &&
             this.availableProviders?.some(p => p.service_provider_id === this.job.assigned_provider_participant_id && p.provider_type === 'XS')
    },

    // Check if we're in XS provider mode (XS job + role 2)
    isXSProviderMode() {
      return this.isXSProviderJob
    },

    isAssigningToXSProvider() {
      if (!this.selectedProviderForAssignment || !this.availableProviders) return false;
      const provider = this.availableProviders.find(p => p.service_provider_id == this.selectedProviderForAssignment);
      return provider?.provider_type === 'XS';
    }
  },
  async mounted() {
    await this.getUserRole()
    await this.loadJob()
    await this.loadAvailableProviders()
    await this.loadTechnicians()
  },
  methods: {
    async getUserRole() {
      try {
        const token = localStorage.getItem('token')
        if (token) {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          this.userRole = payload.role_id
        }
      } catch (error) {
        this.error = 'Authentication error'
      }
    },

    async loadJob() {
      try {
        const response = await apiFetch(`/backend/api/${this.from === 'service-provider' ? 'service-provider-jobs.php' : 'client-jobs.php'}?job_id=${this.jobId}`)

        if (response.ok) {
          const data = await response.json()
          // Single job retrieval returns 'job' key, not 'jobs' array
          this.job = data.job || null
          if (!this.job) {
            throw new Error('Job not found')
          }

          // Initialize editable data
          this.editableJob = { ...this.job }

          // If a provider is already assigned (e.g. from QR code), pre-select them.
          if (this.job.assigned_provider_participant_id) {
            this.selectedProviderForAssignment = this.job.assigned_provider_participant_id.toString();
          }

          // Load images if in Reported status
          if (this.job.job_status === 'Reported') {
            await this.loadExistingImages()
            await this.loadLocations()
          }
        } else {
          throw new Error('Failed to load job')
        }
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async loadExistingImages() {
      try {
        const response = await apiFetch(`/backend/api/job-images.php?job_id=${this.jobId}`)
        if (response.ok) {
          const data = await response.json()
          this.existingImages = data.images || []
        }
      } catch (error) {
        console.error('Failed to load existing images:', error)
      }
    },

    async loadLocations() {
      if (this.userRole !== 2) return

      this.loadingLocations = true
      try {
        const response = await apiFetch('/backend/api/client-locations.php')

        if (response.ok) {
          const data = await response.json()
          this.availableLocations = data.locations || []

          // Set current location if job has one
          if (this.job.client_location_id) {
            this.selectedLocationId = this.job.client_location_id.toString()
          }
        }
      } catch (error) {
        console.error('Error loading locations:', error)
      } finally {
        this.loadingLocations = false
      }
    },

    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getReportedByFullName() {
      if (!this.job) return 'Unknown User'

      if (this.job.reporting_user_full_name) {
        return this.job.reporting_user_full_name
      }
      if (this.job.reporting_user_first_name && this.job.reporting_user_last_name) {
        return `${this.job.reporting_user_first_name} ${this.job.reporting_user_last_name}`
      }
      if (this.job.reporting_user_name) {
        return this.job.reporting_user_name
      }
      return this.job.reporting_user || 'Unknown User'
    },

    handleImagesChanged(images) {
      this.newImages = images
    },

    async handleFormSubmit() {
      await this.saveAndClose('final')
    },

    async saveAndContinue(sectionType) {
      this.saving = true;
      try {
        if (sectionType === 'job-details') {
          await this.saveJobDetails();
        }

        await this.saveImageChanges();

        alert('Changes saved successfully.');

        // Reload job data to get the latest state, e.g., for newly uploaded images
        await this.loadJob();

      } catch (error) {
        console.error('Save failed:', error);
        alert('Failed to save changes: ' + (error.message || error));
      } finally {
        this.saving = false;
      }
    },

    async saveAndClose(sectionType) {
      this.saving = true

      try {
        // Save job details
        if (sectionType === 'job-details' || sectionType === 'final') {
          await this.saveJobDetails()
        }

        // Save images
        await this.saveImageChanges()

        // Return to dashboard with scroll position
        await this.returnToDashboard()

        // Show success notification
        this.$router.push({
          path: this.from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
          query: { success: 'Job updated successfully' }
        })

      } catch (error) {
        console.error('Save failed:', error)
        // Show error notification
        alert('Failed to save changes: ' + (error.message || error))
      } finally {
        this.saving = false
      }
    },

    async saveJobDetails() {
      const updateData = {
        job_id: this.job.id
      }

      // Only include changed fields
      const jobFields = ['item_identifier', 'contact_person', 'fault_description']
      jobFields.forEach(field => {
        const oldValue = this.job[field] || '';
        const newValue = this.editableJob[field] || '';
        if (oldValue !== newValue) {
          updateData[field] = this.editableJob[field] || null
        }
      })

      // Add location if for Role 2 and it has changed
      if (this.userRole === 2 && this.selectedLocationId && this.job.client_location_id?.toString() !== this.selectedLocationId) {
        updateData.client_location_id = this.selectedLocationId
      }

      if (Object.keys(updateData).length > 1) {
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(updateData)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to update job details')
        }
      }
    },

    async saveImageChanges() {
      if (!this.$refs.imageUpload) return

      const result = await this.$refs.imageUpload.uploadImages(this.job.id)
      if (!result || !result.success) {
        throw new Error(result?.error || 'Failed to upload images')
      }
    },

    cancelEditing() {
      if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
        this.returnToDashboard()
      }
    },

    async returnToDashboard() {
      // Store scroll position for return
      const routeQuery = {
        scroll: this.scrollPosition || '0'
      }

      // Navigate back to originating dashboard
      await this.$router.push({
        path: this.from === 'service-provider' ? '/service-provider-dashboard' : '/client-dashboard',
        query: routeQuery
      })
    },

    async loadAvailableProviders() {
      // Only load providers for role 2 (clients)
      if (this.userRole !== 2) {
        this.availableProviders = []
        return
      }

      try {
        const response = await apiFetch('/backend/api/client-approved-providers.php')

        if (response.ok) {
          const data = await response.json()
          // The endpoint returns 'approved_providers' array
          this.availableProviders = data.approved_providers || []
        } else {
          console.error('Failed to load approved providers:', response.status)
          this.availableProviders = []
        }
      } catch (error) {
        console.error('Error loading available providers:', error)
        this.availableProviders = []
      }
    },

    async loadTechnicians() {
      if (this.userRole !== 3) return

      try {
        // Service provider jobs endpoint with get_technicians=1 to get technicians for this provider
        const response = await apiFetch('/backend/api/service-provider-jobs.php?get_technicians=1')

        if (response.ok) {
          const data = await response.json()
          this.technicians = data.technicians || []
          console.log(`Loaded ${this.technicians.length} technicians`);
        } else {
          console.error('Failed to load technicians - using empty list for now')
          this.technicians = []
        }
      } catch (error) {
        console.error('Error loading technicians - using empty list:', error.message)
        this.technicians = []
      }
    },

    canEditJobDetails() {
      if (!this.job) return false

      if (this.job.job_status === 'Reported') {
        return true
      }

      // Role 2 can edit all reported jobs, Role 1 can only edit their own jobs
      if (this.userRole === 2) {
        return true
      }

      return false
    },

    // Service Provider Workflow Methods
    getServiceProviderTransitionDescription() {
      const status = this.job.job_status
      const role = this.userRole

      if (role === 3 && status === 'Assigned') {
        return 'You can decline this job assignment or assign a technician to begin work.'
      } else if ((role === 3 || role === 4) && status === 'In Progress') {
        return 'Track the progress of this job and move it to completion or mark it as unreparable.'
      } else if (role === 3 && status === 'Incomplete') {
        return 'The client has marked this job as incomplete. Assign a technician to restart work.'
      } else {
        return 'Available actions for this job based on current status and your role.'
      }
    },

    initiateSPTransition(status) {
      if (status === 'Declined' || status === 'Cannot repair') {
        this.pendingSPTransition = status
        this.spTransitionNotes = ''
      } else {
        // For Completed, go directly to confirmation
        this.pendingSPTransition = status
        this.spTransitionNotes = ''
      }
    },

    cancelSPTransition() {
      this.pendingSPTransition = null
      this.spTransitionNotes = ''
    },

    openTechnicianAssignment() {
      console.log('Opening technician assignment overlay')
      this.showTechnicianAssignment = true
    },

    getConfirmationIcon(status) {
      switch (status) {
        case 'Declined': return 'cancel'
        case 'Completed': return 'check_circle'
        case 'Cannot repair': return 'build'
        case 'Incomplete': return 'sync'
        case 'Confirmed': return 'thumb_up'
        default: return 'help'
      }
    },

    async startWorkOnJob() {
      if (!this.selectedTechnicianId) {
        alert('Please select a technician to begin work on this job.')
        return
      }

      this.saving = true
      try {
        const payload = {
          job_id: this.job.id,
          job_status: 'In Progress',
          assigned_technician_user_id: parseInt(this.selectedTechnicianId),
          technician_notes: this.technicianNotes || null
        }

        const response = await apiFetch('/backend/api/service-provider-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to start work on job')
        }

        const result = await response.json()

        alert(`Work started successfully! Job status changed to "In Progress" and technician ${this.selectedTechnicianId ? 'assigned' : 'not assigned'}.`)

        this.$emit('job-updated', result)
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in startWorkOnJob:', error)
        alert('Failed to start work: ' + error.message)
      } finally {
        this.saving = false
        this.showTechnicianAssignment = false
      }
    },

    async confirmSPTransition() {
      if (!this.pendingSPTransition) return

      // Validate required fields
      if ((this.pendingSPTransition === 'Declined' || this.pendingSPTransition === 'Cannot repair') && !this.spTransitionNotes.trim()) {
        alert(`Please provide ${this.pendingSPTransition === 'Declined' ? 'a reason for declining' : 'an explanation why this cannot be repaired'}.`)
        return
      }

      this.saving = true
      try {
        const payload = {
          job_id: this.job.id,
          job_status: this.pendingSPTransition,
          transition_notes: this.spTransitionNotes.trim() || null
        }

        const response = await apiFetch('/backend/api/service-provider-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to update job status')
        }

        const result = await response.json()

        const successMessage = this.pendingSPTransition === 'Declined'
          ? 'Job declined successfully. The client will be notified.'
          : this.pendingSPTransition === 'Completed'
          ? 'Job marked as completed successfully. The client can now confirm receipt.'
          : 'Job marked as "cannot repair" successfully. The client can review and decide next steps.'

        alert(successMessage)

        this.$emit('job-updated', result)
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in confirmSPTransition:', error)
        alert('Failed to update job: ' + error.message)
      } finally {
        this.saving = false
        this.pendingSPTransition = null
        this.spTransitionNotes = ''
      }
    },

    // Client workflow methods for S-Type jobs
    initiateClientTransition(status) {
      if (status === 'Incomplete') {
        this.pendingClientTransition = status
        this.clientTransitionNotes = ''
      } else {
        this.pendingClientTransition = status
        this.clientTransitionNotes = ''
      }
    },

    cancelClientTransition() {
      this.pendingClientTransition = null
      this.clientTransitionNotes = ''
    },

    async confirmClientTransition() {
      if (!this.pendingClientTransition) return

      if (this.pendingClientTransition === 'Incomplete' && !this.clientTransitionNotes.trim()) {
        alert('Please provide a reason why the job is incomplete.')
        return
      }

      this.saving = true
      try {
        const payload = {
          job_id: this.job.id,
          job_status: this.pendingClientTransition,
          transition_notes: this.clientTransitionNotes.trim() || null
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to update job status')
        }

        const result = await response.json()
        alert('Job status updated successfully!')
        this.$emit('job-updated', result)
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in confirmClientTransition:', error)
        alert('Failed to update job: ' + error.message)
      } finally {
        this.saving = false
        this.pendingClientTransition = null
        this.clientTransitionNotes = ''
      }
    },

    // XS Provider workflow methods
    getTransitionDescription() {
      const status = this.job.job_status
      switch (status) {
        case 'Assigned':
          return 'This job is currently assigned to the external provider. Select what action to take, or change technician assignment first.'
        case 'In Progress':
          return 'The external provider is currently working on this job. Update status when work is completed or if issues arise.'
        case 'Completed':
          return 'Work has been completed. Confirm receipt of work or mark as needing correction.'
        case 'Cannot repair':
          return 'The external provider determined this cannot be repaired. Confirm receipt or request further evaluation.'
        case 'Incomplete':
          return 'Work was previously marked as incomplete. Start work again or update status.'
        default:
          return 'Manage this external service provider job by selecting the appropriate action.'
      }
    },

    async initiateTransition(targetStatus) {
      // Enhanced validation and transition note capture for all state changes
      const requiresNotes = [
        'Declined',
        'Cannot repair',
        'Rejected'
      ]

      // Always require transition notes for XS provider jobs
      if (this.isXSProviderMode && (!this.transitionNotes || !this.transitionNotes.trim())) {
        alert('Please provide transition notes for external provider system documentation.')
        return
      }

      // Require notes for certain status changes
      if (requiresNotes.includes(targetStatus) && (!this.transitionNotes || !this.transitionNotes.trim())) {
        const noteTypeMap = {
          'Declined': 'a reason for declining',
          'Cannot repair': 'an explanation why this cannot be repaired',
          'Rejected': 'a reason for rejecting'
        }
        alert(`Please provide ${noteTypeMap[targetStatus] || 'notes about this'} status change.`)
        return
      }

      this.saving = true
      this.error = null

      try {
        // Comprehensive payload with transition logging
        const payload = {
          transition_notes: this.transitionNotes?.trim() || '',
          assigned_provider_id: targetStatus !== 'Rejected' ? parseInt(this.job.assigned_provider_participant_id) : null
        }

        // Set job status for non-quote transitions
        if (targetStatus !== 'Quote Requested') {
          payload.job_status = targetStatus
        }

        // Add additional context based on transition type
        if (targetStatus === 'Declined') {
          payload.note = 'Job declined by client: ' + (this.transitionNotes?.trim() || 'No reason provided')
        } else if (targetStatus === 'Cannot repair') {
          payload.note = 'Marked as irreparable: ' + (this.transitionNotes?.trim() || 'No explanation provided')
        } else if (targetStatus === 'Completed') {
          payload.note = 'Job completed: ' + (this.transitionNotes?.trim() || 'Completion details not provided')
        } else if (targetStatus === 'In Progress') {
          payload.note = 'Work started on job: ' + (this.transitionNotes?.trim() || 'Started without additional notes')
        } else {
          payload.note = this.transitionNotes?.trim() || `Status changed to ${targetStatus}`
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify({
            job_id: this.job.id,
            ...payload
          })
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to update job status')
        }

        const result = await response.json()
        this.$emit('job-updated', result)

        // Success notification with transition details
        const successMessages = {
          'Declined': 'Job declined successfully. The service provider will be notified.',
          'Cannot repair': 'Job marked as irreparable. The service provider can review and decide next steps.',
          'Completed': 'Job marked as completed. The service provider will be notified.',
          'In Progress': 'Work started on job. Progress updates will be tracked.',
          'Confirmed': 'Job confirmed. The service provider can proceed with completion.',
          'Assigned': 'Job assigned successfully.',
          'Rejected': 'Job rejected. The service provider will be notified.',
          'Quote Requested': 'Quote requested. The service provider will provide a quotation.',
          'Incomplete': 'Job marked as incomplete. Consultation with service provider may be needed.'
        }

        const successMessage = successMessages[targetStatus] || `Job status updated to ${targetStatus}`
        alert(successMessage)

        this.returnToDashboard()
      } catch (error) {
        this.error = error.message
        alert('Failed to update job: ' + error.message)
      } finally {
        this.saving = false
      }
    },

    // Provider assignment workflow methods for "Reported" jobs (role 2)
    async saveProviderAssignment() {
      if (!this.selectedProviderForAssignment) {
        alert('Please select a service provider to save.');
        return;
      }

      if (this.job.assigned_provider_participant_id?.toString() === this.selectedProviderForAssignment) {
        alert('Provider selection has not changed.');
        return;
      }

      this.saving = true;
      try {
        const payload = {
          job_id: this.job.id,
          assigned_provider_id: parseInt(this.selectedProviderForAssignment),
        };

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to save provider assignment');
        }

        alert('Service provider selection saved.');
        await this.loadJob();

      } catch (error) {
        console.error('Error saving provider:', error);
        alert('Failed to save provider: ' + error.message);
      } finally {
        this.saving = false;
      }
    },

    async requestService() {
      // Validate provider selection (required for service request)
      if (!this.selectedProviderForAssignment) {
        alert('Please select a service provider before requesting service.')
        return
      }

      if (this.isXSProviderMode && (!this.transitionNotes || !this.transitionNotes.trim())) {
        alert('Please provide transition notes for assigning a job to an external provider.');
        return;
      }

      this.saving = true
      try {
        // Prepare payload for service request - this assigns provider and sets status to "Assigned"
        const payload = {
          job_id: this.job.id,
          assigned_provider_id: parseInt(this.selectedProviderForAssignment),
          job_status: 'Assigned',
          // Add transition notes if it's an XS provider
          ...(this.isAssigningToXSProvider && { transition_notes: this.transitionNotes.trim() })
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to assign provider and request service')
        }

        const result = await response.json()

        // Find the selected provider's name for success message
        const selectedProvider = this.availableProviders.find(p => p.service_provider_id == this.selectedProviderForAssignment)
        const providerName = selectedProvider ? selectedProvider.name : 'Selected Provider'

        alert(`Service successfully requested from ${providerName}! The job is now assigned and ready for the provider to begin work.`)

        this.$emit('job-updated', result)
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in requestService:', error)
        alert('Failed to request service: ' + error.message)
      } finally {
        this.saving = false
      }
    },

    async requestQuote() {
      // Validate provider selection (required for quote request)
      if (!this.selectedProviderForAssignment) {
        alert('Please select a service provider before requesting a quote.')
        return
      }

      if (this.isXSProviderMode && (!this.transitionNotes || !this.transitionNotes.trim())) {
        alert('Please provide transition notes for requesting a quote from an external provider.');
        return;
      }

      this.saving = true
      try {
        // Prepare payload for quote request - this assigns provider and sets status to "Quote Requested"
        const payload = {
          job_id: this.job.id,
          assigned_provider_id: parseInt(this.selectedProviderForAssignment),
          job_status: 'Quote Requested',
          // Add transition notes if it's an XS provider
          ...(this.isAssigningToXSProvider && { transition_notes: this.transitionNotes.trim() })
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to request quote from provider')
        }

        const result = await response.json()

        // Find the selected provider's name for success message
        const selectedProvider = this.availableProviders.find(p => p.service_provider_id == this.selectedProviderForAssignment)
        const providerName = selectedProvider ? selectedProvider.name : 'Selected Provider'

        alert(`Quote requested successfully from ${providerName}! The provider will submit a quotation through the platform for your approval.`)

        this.$emit('job-updated', result)
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in requestQuote:', error)
        alert('Failed to request quote: ' + error.message)
      } finally {
        this.saving = false
      }
    },

    async rejectJob() {
      // Provider selection is optional for rejection - can reject without provider
      this.saving = true
      try {
        // Ask for rejection reason before proceeding
        const rejectionReason = prompt('Please provide a reason for rejecting this job (optional):')

        // Prepare payload for job rejection
        const payload = {
          job_id: this.job.id,
          job_status: 'Rejected'
        }

        // Add rejection notes if provided
        if (rejectionReason && rejectionReason.trim()) {
          payload.transition_notes = rejectionReason.trim()
          payload.note = 'Job rejected: ' + rejectionReason.trim()
        }

        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to reject job')
        }

        const result = await response.json()

        alert('Job has been rejected and is now closed. No further action will be taken.')

        this.$emit('job-updated', result)
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in rejectJob:', error)
        alert('Failed to reject job: ' + error.message)
      } finally {
        this.saving = false
      }
    },

    // Provider reassignment method for 'Cannot repair' jobs
    async reassignProvider() {
      // Validate required fields
      if (!this.selectedReassignProviderId) {
        alert('Please select a new service provider to reassign this job.')
        return
      }

      if (!this.reassignmentNotes || !this.reassignmentNotes.trim()) {
        alert('Please provide a reason for this provider reassignment.')
        return
      }

      // Additional validation - don't allow reassignment to the same provider
      if (parseInt(this.selectedReassignProviderId) === this.job.assigned_provider_participant_id) {
        alert('Please select a different service provider. The job cannot be reassigned to the same provider.')
        return
      }

      this.saving = true
      try {
        // Prepare the update payload for provider reassignment
        const payload = {
          job_id: this.job.id,
          action: 'reassign_provider',
          assigned_provider_id: parseInt(this.selectedReassignProviderId),
          transition_notes: this.reassignmentNotes.trim(),
          note: `Job reassigned. Reason: ${this.reassignmentNotes.trim()}`
        }

        // Make the API call using the client-jobs endpoint
        const response = await apiFetch('/backend/api/client-jobs.php', {
          method: 'PUT',
          body: JSON.stringify(payload)
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to reassign provider')
        }

        const result = await response.json()

        // Success message with reassignment details
        const newProvider = this.availableProviders.find(p => p.service_provider_id == this.selectedReassignProviderId)
        const providerName = newProvider ? newProvider.name : 'Unknown Provider'

        alert(`Job reassigned successfully!\n\nThis job has been reassigned from "${this.job.provider_name}" to "${providerName}" and is now in "Assigned" status. All provider-specific data has been reset for the new provider.`)

        // Emit the job update event to refresh the parent component
        this.$emit('job-updated', result)

        // Close the modal
        this.returnToDashboard()

      } catch (error) {
        console.error('Error in reassignProvider:', error)
        alert(`Failed to reassign provider: ${error.message}`)
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.edit-job-page {
  /* Full-screen responsive layout */
}

.form-input:focus {
  outline: none;
}

/* Material Icons */
.material-icon {
  font-family: 'Material Symbols Outlined', sans-serif;
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
}

/* Loading Spinner */
.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Form Styles */
.form-label {
  color: #374151;
  font-weight: 500;
}

.field-icon,
.section-icon {
  font-size: 18px;
}

.section-icon {
  color: #3B82F6;
}

.btn-filled {
  @apply bg-blue-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors;
}

.btn-outlined {
  @apply bg-transparent text-gray-600 border border-gray-300 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors;
}

/* Transition Button Grid */
.transition-buttons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

@media (max-width: 640px) {
  .transition-buttons-grid {
    grid-template-columns: 1fr;
  }
}

/* Transition Action Buttons */
.transition-action-btn {
  @apply flex items-center justify-center gap-3 px-6 py-4 rounded-lg font-semibold text-base border-2 border-transparent transition-all duration-200 hover:shadow-lg focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Service Provider Button Styles */
.status-declined-sp {
  @apply bg-red-50 text-red-700 border-red-200 hover:bg-red-100 focus:ring-red-500;
}

.status-declined-sp .btn-icon {
  @apply text-red-600;
}

.status-in-progress-sp {
  @apply bg-green-50 text-green-700 border-green-200 hover:bg-green-100 focus:ring-green-500;
}

.status-in-progress-sp .btn-icon {
  @apply text-green-600;
}

.status-completed-sp {
  @apply bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 focus:ring-blue-500;
}

.status-completed-sp .btn-icon {
  @apply text-blue-600;
}

.status-cannot-repair-sp {
  @apply bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100 focus:ring-yellow-500;
}

.status-cannot-repair-sp .btn-icon {
  @apply text-yellow-600;
}

/* XS Provider Button Styles */
.status-in-progress {
  @apply bg-green-50 text-green-700 border-green-200 hover:bg-green-100 focus:ring-green-500;
}

.status-in-progress .btn-icon {
  @apply text-green-600;
}

.status-declined {
  @apply bg-red-50 text-red-700 border-red-200 hover:bg-red-100 focus:ring-red-500;
}

.status-declined .btn-icon {
  @apply text-red-600;
}

.status-completed {
  @apply bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 focus:ring-blue-500;
}

.status-completed .btn-icon {
  @apply text-blue-600;
}

.status-incomplete {
  @apply bg-orange-50 text-orange-700 border-orange-200 hover:bg-orange-100 focus:ring-orange-500;
}

.status-incomplete .btn-icon {
  @apply text-orange-600;
}

.status-cannot-repair {
  @apply bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100 focus:ring-yellow-500;
}

.status-cannot-repair .btn-icon {
  @apply text-yellow-600;
}

.status-confirmed {
  @apply bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100 focus:ring-purple-500;
}

.status-confirmed .btn-icon {
  @apply text-purple-600;
}

.status-reassign {
  @apply bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100 focus:ring-indigo-500;
}

.status-reassign .btn-icon {
  @apply text-indigo-600;
}

.status-service-requested {
  @apply bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 focus:ring-blue-500;
}

.status-service-requested .btn-icon {
  @apply text-blue-600;
}

.status-quote-requested {
  @apply bg-green-50 text-green-700 border-green-200 hover:bg-green-100 focus:ring-green-500;
}

.status-quote-requested .btn-icon {
  @apply text-green-600;
}

.status-rejected {
  @apply bg-red-50 text-red-700 border-red-200 hover:bg-red-100 focus:ring-red-500;
}

.status-rejected .btn-icon {
  @apply text-red-600;
}

/* Job Origin Styles */
.job-origin-area {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.origin-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.origin-info {
  flex: 1;
}

.origin-text {
  font-size: 0.95rem;
  opacity: 0.9;
  margin-bottom: 0.5rem;
}

.origin-status {
  font-weight: 600;
  opacity: 0.8;
  font-size: 0.85rem;
}

.material-icon.user-icon {
  font-size: 32px;
  opacity: 0.8;
  margin-top: -0.25rem;
}

/* Button Icon Styles */
.btn-icon {
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.material-icon-sm {
  font-size: 16px;
  margin-right: 8px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .max-w-4xl {
    @apply max-w-full;
  }

  .max-w-7xl {
    @apply max-w-full;
  }

  .px-6 {
    @apply px-4;
  }

  .lg\\:col-span-2 {
    @apply col-span-1;
  }

  .transition-action-btn {
    @apply px-4 py-3 text-sm;
  }

  .transition-buttons-grid {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 0.75rem;
  }

  .btn-icon {
    font-size: 18px;
    width: 20px;
    height: 20px;
  }
}
</style>
