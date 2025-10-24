# Snappy Project TODO

## In Progress 🚧

- [x] **Quote Request Deadline Enhancement** - Add "Quote By" date field with default +7 days, minimum +1 day, visual indicators for urgency ✅
- [x] ClientDashboard Bug Fixes (4 High Priority + 2 Follow-up Issues) ✅
- [ ] Registration & Invitation System Enhancement
- [ ] Geographic Search & Location-Based Features

## Up Next 📋

### PHASE 1: Critical Foundation - Quote System Implementation (Complete ✅)

#### Backend Tasks

- [x] Create `job-quotations.php` API endpoint for quote CRUD operations
- [x] Create `job-quotation-responses.php` API for clients to accept/reject quotes
- [x] Update `client-jobs.php` to handle "Quote Requested" state transitions
- [x] Update `service-provider-jobs.php` to show quote-related jobs
- [x] Add quote validation logic to existing job status updates

#### Database Tasks

- [x] Verify `job_quotations` table exists (confirmed in snappy-dev.sql)
- [x] Ensure `job_quotation_history` table exists (confirmed in snappy-dev.sql)
- [ ] Add quote-related indexes for performance (if needed)

#### Frontend Tasks

- [x] Add quote management UI to Service Provider Dashboard
- [x] Add quote response UI to Client Dashboard
- [x] Update job creation flow to support quote requests

### PHASE 2: Missing Terminal States Implementation (Complete ✅)

#### Backend Tasks

- [x] Update `job-status-update.php` to handle "Cannot repair" transitions
- [x] Create client confirmation API for "Completed" → "Confirmed" workflow
- [x] Add "Incomplete" state handling for client rejection of completed work
- [x] Implement proper state transition validation

#### Frontend Tasks

- [x] Add "Cannot repair" option to Technician Dashboard
- [x] Add confirmation/rejection UI to Client Dashboard for completed jobs
- [x] Update status displays across all dashboards

### PHASE 3: Geographic Enhancement Implementation (Complete ✅)

#### Database Tasks

- [x] Create comprehensive geographic database schema (6 new tables)
- [x] Implement geographic boundaries using MariaDB spatial features
- [x] Establish proper region hierarchy relationships
- [x] Add regional classifications for South African regions
- [x] Populate South African geographic data (provinces, cities, suburbs)
- [x] Add performance indexes for geographic queries

#### Geographic Features

- [x] Geographic search optimization and caching
- [x] Geographic analytics and usage tracking
- [x] Enhanced location-based service provider matching
- [ ] Geographic search UI components (pending)

### PHASE 4: Registration & Invitation System Enhancement

#### Backend Tasks

- [x] Enhanced invitation validation and processing
- [x] Improved service provider registration workflow
- [x] Client user management system
- [ ] Registration email verification improvements

#### Frontend Tasks

- [ ] Unified registration system UI
- [ ] Invitation landing page improvements
- [ ] Service provider registration form enhancements
- [ ] Registration workflow optimization

#### Integration Tasks

- [ ] Email verification system integration
- [ ] Registration analytics and tracking
- [ ] Multi-step registration flow improvements

### PHASE 5: Role Permission Corrections

#### Backend Tasks

- [ ] Enhance Role 3 (dispatcher) permissions in all job APIs
- [ ] Add proper job reassignment capabilities for dispatchers
- [ ] Implement technician management features for Role 3
- [ ] Fix permission checks in `service-provider-jobs.php`

#### Frontend Tasks

- [ ] Update Service Provider Dashboard for Role 3 capabilities
- [ ] Add technician assignment UI for dispatchers
- [ ] Improve job editing permissions for Role 3 users

### PHASE 6: Enhanced State Management

#### Backend Tasks

- [ ] Create state transition validation engine
- [ ] Add comprehensive status history tracking
- [ ] Implement business rule validation for state changes
- [ ] Add notification system for state transitions

#### Database Tasks

- [ ] Add state transition constraints if needed
- [ ] Optimize status history queries
- [ ] Add indexes for common status-based queries

### PHASE 7: Frontend Integration & UX

#### Frontend Tasks

- [ ] Update job status displays across all dashboards
- [ ] Add quote workflow UI components
- [ ] Implement proper loading states and error handling
- [ ] Add comprehensive job filtering and search

### PHASE 8: Documentation & Cleanup

#### Documentation Tasks

- [ ] Fix `job-workflow.md` numbering and typos
- [ ] Clarify role permissions and responsibilities
- [ ] Document complete state transition matrix
- [ ] Add API documentation for new endpoints

## Completed ✅

- [x] **ClientDashboard Follow-up Bug Fixes (2 Issues)** (2025-10-21) [Frontend]
  - ✅ User edit/delete buttons now functional with proper event handlers
  - ✅ Removed duplicate add buttons and added placeholder "Add User" cards following ServiceProviderDashboard pattern
  - ✅ Location edit buttons now functional with placeholder "Add Location" cards
  - ✅ Consistent UI patterns across admin sections with proper grid layouts
- [x] **ClientDashboard Bug Fixes (4 High Priority Issues)** (2025-10-21) [Frontend]
  - ✅ Job management section now collapses/expands when clicked (fixed section expansion logic)
  - ✅ Job view/edit buttons now work correctly (admin users always see edit buttons, regular users see conditional edit/view buttons)
  - ✅ User management and locations sections now display correctly (removed conflicting CollapsibleSection wrappers)
  - ✅ All build tests pass, no console errors, sections expand/collapse properly

<!-- Most recent first, format: - [x] Task (YYYY-MM-DD) [Backend/Frontend/Full-stack] -->

## Blocked 🔒

- [ ] Database schema changes require approval before implementation

## Future Ideas 💡

- [ ] Quote expiration automation
- [ ] Bulk status update capabilities
- [ ] Advanced workflow analytics
- [ ] Mobile app workflow support

### PHASE 9: Job Details Modal & Location Integration

#### Frontend Tasks

- [ ] Check that Google Maps coordinates work correctly in job card view modal
- [ ] Implement Plus Code location display for location coordinates
- [ ] Add fallback to street address when no grid coordinates available
- [ ] Improve JobDetailsModal styling to look more professional and polished

#### Location Integration Tasks

- [ ] Integrate Google Maps API calls for address-based location display
- [ ] Add location click functionality to open maps in new tab
- [ ] Test coordinate display accuracy across different location formats
- [ ] Add location validation and error handling for map display

### PHASE 10: Quote Preparation Workflow Enhancement

#### Frontend Tasks

- [ ] Integrate quote preparation cycle into quote provided process
- [ ] Add quote template selection in quote creation workflow
- [ ] Implement multi-step quote preparation form
- [ ] Add quote preview functionality before sending to client

#### Client Quote Response Handling

- [ ] Allow edit modal access when clients receive "Unable to quote" response
- [ ] Enable provider reassignment after quote rejection
- [ ] Improve quote response workflow UX for clients

#### Quote Management Tasks

- [ ] Create quote preparation modal with template selection
- [ ] Add quote itemization and pricing components
- [ ] Implement quote save/suspend functionality for draft quotes
- [ ] Add quote history and version control features

#### UI Enhancement Tasks

- [ ] Fix squashed quote accept/reject buttons between View and Archive buttons
- [ ] Improve quote response button layout and styling
- [ ] Add proper spacing and prominence for quote action buttons

#### Integration Tasks

- [ ] Connect quote preparation to existing quote provided state transition
- [ ] Update service provider quote acceptance workflow
- [ ] Add quote completion notifications and follow-up
- [ ] Integrate quote analytics and performance tracking

## Current Sprint Focus

Complete Phase 1 (Quote System) - This addresses the most critical missing functionality and provides immediate value to users.

## Notes 📝

- Database schema: See `snappy-dev.sql`
- Build script: `./snappy-build.sh`
- Virtual host: http://snappy.local
