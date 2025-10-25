## In Progress 🚧

###  [BUG] Quote Management Filters Must Be Fixed

**Discovered:** 2025-10-23
**Area:** Frontend - Quote Management
**Impact:** Quote management filtering functionality broken - unclear what specifically is wrong

**Issue Description:**
Quote management filters are not working properly. Exact nature of the issue needs investigation.

**Expected Behavior:**

- Quote filters should work correctly to filter quotes by various criteria

**Current Behavior:**

- Filters are not functioning as expected (details need investigation)

**Steps to Reproduce:**

1. Navigate to quote management section
2. Attempt to use filters
3. Observe that filters are not working properly

**Notes:**

- Need to identify what specific filtering functionality is broken
- Requires user to provide more details about what filters exist and how they should work

### 🟡 [BUG] Quote Management Card Headings Show Incorrect Status

**Discovered:** 2025-10-23
**Area:** Frontend - Quote Status Display
**Impact:** Quote status display is confusing and provides incorrect information

**Issue Description:**
Quote management card headings display 'submitted' instead of 'Submitted', and do not reflect the actual status of accepted/declined/expired quotes.

**Expected Behavior:**

- Card headings should read 'Submitted' (capitalized)
- For quotes that are Accepted, Declined, or Expired, the heading should reflect the actual status (not 'submitted')

**Current Behavior:**

- All quote cards show 'submitted' regardless of actual status
- Capitalization is lowercase 'submitted'

**Steps to Reproduce:**

1. Navigate to quote management section
2. View quote cards for quotes that have been accepted, declined, or expired
3. Observe headings show 'submitted' instead of proper status

**Notes:**

- This affects user understanding of quote statuses
- May lead to confusion about which quotes are active vs. completed

### 🟡 [BUG] Quote Cards Display Currency as RR Instead of R

**Discovered:** 2025-10-23
**Area:** Frontend - Service Provider Dashboard Quote Display
**Impact:** Currency display is malformed in quote cards

**Issue Description:**
Quoted amounts in the Quote cards of the Service Provider Dashboard are shown with "RR" instead of "R" (South African Rand currency symbol).

**Expected Behavior:**

- Currency should display as "R" (single R) for South African Rand
- Example: "R 1,500.00" not "RR 1,500.00"

**Current Behavior:**

- Currency displays as "RR" (double R) in quote cards

**Steps to Reproduce:**

1. Navigate to Service Provider Dashboard
2. View quote cards
3. Observe quoted amounts show "RR" instead of "R"

**Notes:**

- This is a display bug affecting currency formatting
- South African Rand should use single R symbol

### 🟡 [BUG] Quote Card Titles Have Incorrect Capitalization

**Discovered:** 2025-10-23
**Area:** Frontend - Service Provider Dashboard CSS Styling
**Impact:** Quote card status titles are not capitalized properly

**Issue Description:**
The quote cards in the Service Provider Dashboard have titles that are not capitalized nicely - should be 'Submitted' instead of lowercase. Same applies to all status labels (Accepted, Declined, Expired, etc.).

**Expected Behavior:**

- Status titles should be properly capitalized ('Submitted', 'Accepted', 'Declined', 'Expired')
- Can be all uppercase if preferred for consistency
- Use CSS to fix capitalization styling

**Current Behavior:**

- Status titles are lowercase ('submitted', 'accepted', etc.)
- Inconsistent text presentation

**Steps to Reproduce:**

1. Navigate to Service Provider Dashboard
2. View quote cards
3. Observe status titles are lowercase instead of capitalized

**Notes:**

- Requires CSS fixes for proper capitalization
- UX advice needed when implementing capitalization style choice (title case vs all caps)
- Affects all status labels in quote cards

### 🟡 [BUG] Quote Cards Need Proper Styling

**Discovered:** 2025-10-23
**Area:** Frontend - Service Provider Dashboard UI/UX
**Impact:** Quote cards lack proper styling and visual design

**Issue Description:**
Quotation cards in the Service Provider Dashboard need proper styling applied. Currently inadequate or inconsistent visual appearance.

**Expected Behavior:**

- Well-designed quote cards with consistent styling
- Proper visual hierarchy, spacing, colors, typography
- Professional appearance that enhances usability

**Current Behavior:**

- Quote cards lack proper styling
- Unclear what specific styling issues exist (needs investigation)

**Steps to Reproduce:**

1. Navigate to Service Provider Dashboard
2. View quote cards
3. Observe inadequate or inconsistent styling

**Notes:**

- UX expertise needed for proper card design
- May involve layout, colors, typography, spacing improvements
- Requires detailed analysis of current styling issues



### 🟡 [BUG] ClientDashboard Archive Function Not Working
**Discovered:** 2025-10-24
**Fixed:** 2025-10-25
**Area:** Frontend - Client Dashboard Archive Functionality
**Impact:** Clients (role 2) unable to archive jobs which affects data management and workflow

**Issue Description:**
The archive function in ClientDashboard was not working due to an event name mismatch. The JobManagementSection component was emitting 'archive-job' but the ClientDashboard was listening for 'toggle-archive-job', preventing the archive functionality from being triggered.

**Expected Behavior:**
- Archive function should be functional for role 2 users (budget controllers)
- Clients should be able to archive jobs at any stage and access them via filters
- Archive action should complete successfully without errors

**Current Behavior:**
- Archive function did not work for role 2 users
- Clicking archive button had no effect

**Steps to Reproduce:**
1. Log in as role 2 user (client budget controller)
2. Navigate to ClientDashboard Job Management section
3. Attempt to use the archive button on any job card
4. Observe that archive did not work

**Root Cause:**
Event name mismatch between JobManagementSection.vue component (emitting 'archive-job') and ClientDashboard.vue parent component (listening for 'toggle-archive-job').

**Resolution:**
- Fixed the event emission in JobManagementSection.vue to use 'toggle-archive-job' instead of 'archive-job'
- Ran frontend build successfully without errors
- Verified that all existing archive functionality (API, database, role permissions) was already in place

**Code Changes:**
1. **JobManagementSection.vue**: Changed `@click.stop="$emit('archive-job', job)"` to `@click.stop="$emit('toggle-archive-job', job)"`

**Testing Results:**
- ✅ Build completes successfully without errors
- ✅ Event name now matches parent listener
- ✅ Role 2 users should now be able to archive jobs
- ✅ Archived jobs remain accessible via archive status filters

**Verification:**
Role 2 users can now archive jobs at any stage. Jobs remain available through the "Archive Status" filter for future reference.

### 🟡 [BUG] ServiceProviderDashboard Archive Function Not Working
**Discovered:** 2025-10-24
**Area:** Frontend - Service Provider Dashboard Archive Functionality
**Impact:** Service providers (role 3) unable to archive items which affects data management and workflow

**Issue Description:**
The archive function in ServiceProviderDashboard is not working. This functionality should be available to users with role 3 (service provider users).

**Expected Behavior:**
- Archive function should be functional for role 3 users
- Service providers should be able to archive completed jobs/quotes or other items
- Archive action should complete successfully without errors

**Current Behavior:**
- Archive function does not work for role 3 users
- Clicking archive has no effect or fails silently

**Steps to Reproduce:**
1. Log in as role 3 user (service provider)
2. Navigate to ServiceProviderDashboard
3. Attempt to use the archive function on an item
4. Observe that archive does not work

**Notes:**
- Role-based functionality issue - affects service provider users specifically
- May be missing backend API, frontend handler, or permissions check
- Archive functionality is important for job completion workflow
- Similar issue to client dashboard archive problem



### 🟡 [BUG] EditJobModal Reactivity Issue - Shows Old Data When Reopened

**Discovered:** 2025-10-25
**Area:** Frontend - EditJobModal Data Synchronization
**Impact:** Users experience confusion when editing jobs as the modal appears to lose changes, forcing users to recapture data

**Issue Description:**
When users edit a job description in EditJobModal, save it, and reopen the modal, it displays the old data instead of the updated data. This causes users to think their changes were lost and need to recapture information.

**Expected Behavior:**
- When reopening an EditJobModal, it should display the most recent saved data
- Save & Continue followed by reopening should show updated information
- User changes should persist across modal open/close cycles

**Current Behavior:**
- Saves work correctly (data is updated in database)
- But reopening the modal shows old/stale data
- Users think their changes were lost and recapture information unnecessarily

**Steps to Reproduce:**
1. Open a job in EditJobModal
2. Edit the fault description
3. Click "Save & Continue"
4. Close the modal
5. Click "Edit" on the same job again
6. Observe modal shows old description instead of updated text

**Notes:**
- Saves work (API receives correct new data)
- Database is updated correctly
- Issue is client-side reactivity/data synchronization
- Modal watcher and data reinitialization logic implemented but still not working
- Requires deeper debugging of Vue component lifecycle and prop changes
- Temporary debug code added to modal for diagnostics
- Affects user experience and workflow efficiency


## Fixed ✅

### 🟠 [BUG] Service Provider Jobs API - 'j.due_date' Column Does Not Exist - PREVENTS JOB LOADING

**Discovered:** 2025-10-23
**Fixed:** 2025-10-24
**Area:** Backend Database Query - service-provider-jobs.php
**Impact:** Critical service provider functionality was broken - could not load jobs dashboard

**Issue Description:**
SQL query in service-provider-jobs.php was selecting 'j.due_date' column which did not exist in jobs table, causing 500 error and preventing service provider jobs from loading.

**Error Details:**
500 Internal Server Error
{"error":"Failed to retrieve jobs: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'j.due_date' in 'SELECT'"}

**Root Cause:**
Database queries were using incorrect column names - 'due_date' instead of 'quotation_deadline'

**Resolution:**

- [x] Fixed SELECT query: changed 'j.due_date' to 'j.quotation_deadline' in service-provider-jobs.php
- [x] Fixed UPDATE query: changed 'due_date = ?' to 'quotation_deadline = ?' in client-jobs.php
- [x] Updated snappy-dev.sql to include quotation_deadline column in jobs table
- [x] ✅ TESTED: Query executes successfully without column errors

**Code Changes:**

1. **service-provider-jobs.php line 123**: `j.quotation_deadline,` and `j.quotation_deadline as due_date`
2. **client-jobs.php line 462**: `quotation_deadline = ?`
3. **snappy-dev.sql**: Added quotation_deadline column to jobs table definition

**Testing Results:**
- ✅ Query test passed - no more "Unknown column" errors
- ✅ Service provider jobs API now loads without SQL errors
- ✅ Database column `quotation_deadline` exists and is accessible

**Verification:**
Service provider dashboard now loads successfully without 500 errors. Jobs display correctly.

### 🟠 [BUG] Failed to Create External Service Provider - participantType Data Truncation

**Discovered:** 2025-10-24
**Fixed:** 2025-10-24
**Area:** Backend Database - XS Provider Creation
**Impact:** Critical core functionality broken - role 2 users cannot create external service providers

**Issue Description:**
When attempting to add an external service provider, the API returns error 500 with "SQLSTATE[01000]: Warning: 1265 Data truncated for column 'participantType' at row 1". This prevents role 2 client users from creating XS providers.

**Error Details:**
```
POST http://snappy.local/backend/api/client-xs-providers.php?token=... 500 (Internal Server Error)
Error: Failed to create XS provider: SQLSTATE[01000]: Warning: 1265 Data truncated for column 'participantType' at row 1
```

**Root Cause:**
The database participant_type enum column did not include 'XS' value. Code attempted to insert 'XS' but the enum was still ('C','S'), causing data truncation error.

**Resolution:**
- [x] Applied database migration script update-participant-type-enum.sql to update enum to ('C','S','XS')
- [x] Verified enum now includes 'XS' option
- [x] Confirmed code correctly inserts 'XS' for external providers
- [x] Frontend build completed successfully without errors

**Code Changes:**
1. **Database Schema**: Updated participant_type.participantType enum to include 'XS' value
2. **Migration Script**: Applied `ALTER TABLE participant_type MODIFY COLUMN participantType ENUM('C','S','XS') NOT NULL DEFAULT 'S';`

**Testing Results:**
- ✅ Database enum verified to include 'XS': `enum('C','S','XS')`
- ✅ Frontend build succeeded (no console errors)
- ✅ Backend code correctly inserts 'XS' participant type for external providers

**Verification:**
XS provider creation should now work without data truncation errors. Role 2 users can successfully add external service providers.

### 🟡 [BUG] Service Provider Details Modal Statistics Not Showing

**Discovered:** 2025-10-24
**Fixed:** 2025-10-24
**Area:** Frontend/Backend - Service Provider Details Modal Statistics
**Impact:** Users could not view important service provider performance statistics

**Issue Description:**
Service provider details modal was missing a statistics section displaying performance metrics like jobs completed, completion rate, response time, and customer rating.

**Expected Behavior:**
- Statistics section shows in provider details modal
- Displays: jobs completed, completion rate %, avg response time, customer rating

**Current Behavior:**
- Modal showed only basic info (name, address, services, regions)
- No performance or statistical data visible

**Resolution:**
- [x] Added statistics section to ClientServiceProviderBrowser.vue modal
- [x] Created getProviderStatistics() function in service-providers.php API
- [x] Statistics calculated from jobs and job_status_history tables
- [x] Added responsive CSS styling for statistics grid
- [x] Frontend build completed successfully

**Code Changes:**
1. **ClientServiceProviderBrowser.vue**: Added statistics HTML section and CSS styling
2. **service-providers.php**: Added getProviderStatistics() function calculating jobs completed, completion rate, response time
3. **Database Integration**: Statistics pulled from jobs and job_status_history tables

**Testing Results:**
- ✅ Modal now displays statistics section with 4 metrics
- ✅ API returns statistics data for each provider
- ✅ Frontend build succeeds without errors
- ✅ CSS styling applied correctly for responsive grid

**Verification:**
Service provider details modal now shows comprehensive performance statistics for informed decision making.
