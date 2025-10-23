## In Progress 🚧

### 🟠 [BUG] Service Provider Jobs API - 'j.due_date' Column Does Not Exist - PREVENTS JOB LOADING
**Discovered:** 2025-10-23
**Area:** Backend Database Query - service-provider-jobs.php
**Impact:** Critical service provider functionality broken - cannot load jobs dashboard

**Issue Description:**
SQL query in service-provider-jobs.php is selecting 'j.due_date' column which does not exist in jobs table, causing 500 error and preventing service provider jobs from loading.

**Error Details:**
500 Internal Server Error
{"error":"Failed to retrieve jobs: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'j.due_date' in 'SELECT'"}

**Expected Behavior:**
- Service provider jobs should load successfully
- Job dashboard displays without errors
- Query should only select columns that exist in database

**Current Behavior:**
- Jobs fail to load with 500 error
- Service provider dashboard unusable
- SQLSTATE[42S22] column not found error

**Area Impact:**
- Affects all service providers trying to access their jobs
- Complete dashboard functionality blocked
- Business critical - prevents job management operations

**Resolution Summary:**
- [x] Fixed SELECT query: changed 'j.due_date' to 'j.quotation_deadline' in service-provider-jobs.php
- [x] Fixed UPDATE query: changed 'due_date = ?' to 'quotation_deadline = ?' in client-jobs.php
- [x] Updated snappy-dev.sql to include quotation_deadline column in jobs table
- [ ] Test service provider jobs loading to confirm fix works

**Code Changes:**
1. **service-provider-jobs.php line 93**: `j.due_date,` → `j.quotation_deadline,`
2. **client-jobs.php line 475**: `due_date = ?,` → `quotation_deadline = ?,`
3. **snappy-dev.sql**: Added quotation_deadline column to jobs table definition

**Next Steps:**
- [ ] Test the fix by attempting to load service provider jobs
- [ ] If it works, move bug to Fixed ✅ section
- [ ] If it fails with different error, investigate further
