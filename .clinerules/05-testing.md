# Testing Standards

## Testing is Mandatory

Every task must be tested before marking complete. No exceptions.

## Backend Testing

### API Endpoint Testing

**Test with:**
- Browser (for GET requests)
- Postman / Insomnia
- curl command line
- Frontend (after basic backend test passes)

**What to Test:**

1. **Success Cases:**
   - Valid input returns expected output
   - Data is saved correctly to database
   - Correct HTTP status code (200, 201, etc.)
   - Response format matches expected structure

2. **Error Cases:**
   - Missing required parameters → 400 error
   - Invalid data types → 400 error
   - Unauthorized access → 401/403 error
   - Not found → 404 error
   - Server errors handled gracefully → 500 error

3. **Edge Cases:**
   - Empty strings
   - Very long strings
   - Special characters (', ", <, >, &)
   - SQL keywords (SELECT, DROP, etc.)
   - Null/undefined values
   - Maximum/minimum values

4. **Database:**
   - Data persisted correctly
   - Foreign keys maintained
   - Constraints respected
   - No orphaned records

### PHP Code Testing

- Check PHP error logs for warnings/notices
- Test with error reporting enabled
- Verify no SQL errors
- Check for proper error handling
- Test authentication/authorization works

## Frontend Testing

### Vue Component Testing

**Build First:**
```bash
./snappy-build.sh
```

**Must succeed without:**
- Errors
- Warnings (investigate warnings, may indicate issues)

**Test in Browser:**

1. **Functionality:**
   - Component renders correctly
   - User interactions work (clicks, typing, etc.)
   - Data displays as expected
   - Forms submit correctly
   - Navigation works

2. **Console (F12 Developer Tools):**
   - No JavaScript errors
   - No Vue warnings
   - No uncaught promise rejections
   - No 404s for assets

3. **Network Tab (F12):**
   - API calls succeed
   - Correct endpoints called
   - Correct data sent
   - Responses handled properly
   - No failed requests (404, 500, etc.)

4. **Responsive Design:**
   - Test on desktop size
   - Test on tablet size (if relevant)
   - Test on mobile size (if relevant)
   - Use browser dev tools device emulation

5. **Edge Cases:**
   - Empty states (no data)
   - Loading states
   - Error states (API failure)
   - Long text (overflow handling)
   - Many items (performance)

### Build Testing

**After `./snappy-build.sh`:**
- [ ] Build completed successfully
- [ ] No errors in build output
- [ ] Files copied to virtual host
- [ ] Symlinks still work
- [ ] Can access site at virtual host URL

**If Build Fails:**
1. Read error message carefully
2. Note file and line number
3. Fix the issue (usually syntax error)
4. Run build again
5. Don't mark task complete until build succeeds

## Full-Stack Testing

When task involves both backend and frontend:

### Test Backend First:
1. API works independently (Postman/curl)
2. Data persists correctly
3. Error handling works

### Then Test Frontend:
1. Run build
2. Frontend calls API correctly
3. Displays data correctly
4. Handles errors gracefully

### Then Test Integration:
1. Complete user flow works
2. Data flows correctly end-to-end
3. Edge cases handled
4. Error scenarios work

## Database Schema Testing

**After Schema Changes:**

1. **Verify Schema:**
   - Check actual database matches `snappy-dev.sql`
   - All tables created
   - All columns correct types
   - All constraints applied
   - All indexes created

2. **Test Relationships:**
   - Foreign keys work
   - Cascading deletes/updates work as intended
   - Constraints prevent invalid data

3. **Test Data Migration (if applicable):**
   - Existing data migrated correctly
   - No data loss
   - Data integrity maintained

4. **Test Rollback:**
   - Can revert if needed
   - Have backup before schema change

## Testing Checklist

Before marking any task complete:

### For All Tasks:
- [ ] Happy path works (normal usage)
- [ ] Error cases handled
- [ ] Edge cases considered
- [ ] No errors in logs/console

### For Backend:
- [ ] API tested with Postman/curl
- [ ] Database queries work
- [ ] Data persists correctly
- [ ] Error responses correct
- [ ] PHP errors checked

### For Frontend:
- [ ] Build succeeds: `./snappy-build.sh`
- [ ] No console errors
- [ ] No network errors
- [ ] Visual appearance correct
- [ ] User interactions work
- [ ] Responsive (if relevant)

### For Full-Stack:
- [ ] Backend tested independently
- [ ] Frontend tested independently
- [ ] Integration tested end-to-end
- [ ] Complete user flow works

### For Database:
- [ ] Schema matches dump file
- [ ] Relationships work
- [ ] Constraints enforced
- [ ] No data loss

## When Testing Reveals Issues

### Bug Found:
1. Add to BUGS.md with severity
2. Fix if Critical/High
3. Otherwise note and continue

### New Requirement Found:
1. Add to TODO.md
2. Discuss whether to address now or later

### Design Issue Found:
1. Document in COMPLETED.md
2. Discuss with user
3. Add improvement to TODO.md if needed

## Testing Documentation

In COMPLETED.md, document:
```markdown
**Testing:**
- Tested API with Postman ✓
  - Success case: User created with valid data
  - Error case: Duplicate email rejected
  - Edge case: Empty fields rejected
- Browser testing ✓
  - Form renders correctly
  - Validation messages display
  - Successful submission redirects
- Edge cases ✓
  - Very long email (truncated at 255 chars)
  - Special characters in name (handled correctly)
```

This creates a record of what was tested and helps catch gaps.