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
