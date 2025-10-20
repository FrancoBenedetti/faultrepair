# Troubleshooting Guide

## Frontend Issues

### Changes Don't Appear

**Checklist:**
- [ ] Did you run `./snappy-build.sh`?
- [ ] Did build complete successfully?
- [ ] Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
- [ ] Check correct URL (virtual host, not dev server)
- [ ] Check browser console for errors
- [ ] Verify files copied to virtual host

**Solution:**
```bash
# Rebuild
./snappy-build.sh

# Hard reload browser
# Ctrl+Shift+R (Windows/Linux)
# Cmd+Shift+R (Mac)

# Check virtual host files
ls -la /path/to/virtual/host
```

### Build Fails

**Common Causes:**
1. **Syntax Error**
   - Error shows file and line number
   - Check for typos, missing brackets, etc.

2. **Import Error**
   - Check import path is correct
   - Check file exists at that path
   - Check case-sensitivity (Component.vue vs component.vue)

3. **Undefined Variable/Component**
   - Check component is imported
   - Check variable is declared
   - Check props are passed correctly

**Solution Process:**
1. Read error message completely
2. Note file name and line number
3. Open that file, go to that line
4. Look for the specific issue mentioned
5. Fix it
6. Run `./snappy-build.sh` again
7. Repeat until build succeeds

### Console Errors in Browser

**How to Check:**
1. Open browser Developer Tools (F12)
2. Go to Console tab
3. Look for red errors

**Common Errors:**

**"Cannot read property of undefined"**
- Check data exists before accessing
- Use optional chaining: `user?.name`
- Add v-if to check data exists

**"Component not found"**
- Check component is imported
- Check component name matches import
- Check component is registered (global or local)

**"Failed to fetch" / Network errors**
- Check API endpoint exists
- Check API URL is correct
- Check backend is running
- Check Network tab for response

**Vue warning messages**
- Missing :key in v-for
- Missing props
- Invalid prop types
- Usually won't break app but should be fixed

### Symlinks Broken

**Check:**
```bash
ls -la /path/to/virtual/host/api
# Should show: api -> /path/to/backend/api
```

**If broken:**
- Contact user (don't modify symlinks without permission)
- Document the issue
- Suggest checking deployment configuration

## Backend Issues

### API Not Working

**Checklist:**
- [ ] Check PHP error logs
- [ ] Check endpoint URL is correct
- [ ] Check HTTP method (GET/POST/etc.)
- [ ] Check parameters are being sent
- [ ] Check authentication if required
- [ ] Test with Postman/curl directly

**Where to look:**

1. **Browser Network Tab (F12)**
   - See exact request sent
   - See exact response received
   - Check status code (200, 400, 500, etc.)
   - Check response body

2. **PHP Error Logs**
   - Location varies by setup
   - Often: `/var/log/apache2/error.log`
   - Or: `/var/log/php/error.log`
   - Ask user for log location if unsure

3. **Database**
   - Check query actually runs
   - Test query in database client (phpMyAdmin, etc.)
   - Check for SQL errors

### Database Connection Fails

**Symptoms:**
- "SQLSTATE[HY000]" errors
- "Access denied for user" errors
- "Unknown database" errors

**Check:**
- Database credentials correct (in config)
- Database server running
- Database exists
- User has permissions

**Solution:**
- Check config file for credentials
- Don't modify without permission
- Report issue to user

### SQL Errors

**Common Errors:**

**"Unknown column 'X'"**
```
Solution: Check snappy-dev.sql for exact column name
- Might be user_id not userId
- Might be snake_case not camelCase
```

**"Duplicate entry for key 'PRIMARY'"**
```
Solution: 
- Trying to insert with existing ID
- Remove ID from INSERT or use different ID
- Check for UNIQUE constraints in schema
```

**"Cannot add or update child row: foreign key constraint fails"**
```
Solution:
- Referenced record doesn't exist
- Check foreign key in snappy-dev.sql
- Ensure parent record exists before inserting child
```

**"Table 'X' doesn't exist"**
```
Solution:
- Check exact table name in snappy-dev.sql
- Table names may be case-sensitive on Linux
- Check database connection is correct database
```

### Prepared Statement Errors

**"Invalid parameter number"**
```
// Problem: Wrong number of parameters
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND email = ?");
$stmt->execute([$userId]); // Only 1 param, need 2

// Solution: Match number of ? with parameters
$stmt->execute([$userId, $email]);
```

**"SQLSTATE[HY093]: Invalid parameter number"**
```
// Problem: Named params mixed with positional
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND email = ?");

// Solution: Use one style consistently
// Named:
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND email = :email");
$stmt->execute(['id' => $userId, 'email' => $email]);

// Positional:
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND email = ?");
$stmt->execute([$userId, $email]);
```

## Database Issues

### Column Name Confusion

**Problem:** Column names in database vs. code

**Database (snake_case):**
- user_id
- created_at
- post_id
- comment_count

**JavaScript/Vue (camelCase):**
- userId
- createdAt
- postId
- commentCount

**Solution:**
- Always check `snappy-dev.sql` for exact column names
- Convert between formats as needed:
```php
  // PHP: Fetch from DB (snake_case)
  $row = $stmt->fetch();
  // Convert to camelCase for JSON response
  return [
      'userId' => $row['user_id'],
      'createdAt' => $row['created_at']
  ];
```

### Foreign Key Violations

**Error:** "Cannot add or update child row"

**Cause:** Trying to insert record that references non-existent parent

**Solution:**
1. Check `snappy-dev.sql` for foreign key constraints
2. Ensure parent record exists before inserting child
3. Example:
```php
   // Before inserting comment
   // Check post exists:
   SELECT id FROM posts WHERE id = ?
   
   // Then insert comment with post_id foreign key
   INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)
```

### NULL Constraint Violations

**Error:** "Column 'X' cannot be null"

**Cause:** Trying to insert NULL into NOT NULL column

**Solution:**
1. Check `snappy-dev.sql` for NOT NULL columns
2. Ensure all required fields have values
3. Example:
```sql
   -- Schema shows:
   email VARCHAR(255) NOT NULL
   
   -- Must provide email:
   INSERT INTO users (email, name) VALUES (?, ?)
   -- Can't do: INSERT INTO users (name) VALUES (?)
```

### Query Performance Issues

**Symptoms:**
- Slow page loads
- Database taking long time
- High CPU on database

**Common Causes:**
1. Missing indexes on WHERE/JOIN columns
2. N+1 query problem (query in loop)
3. Selecting too much data (SELECT * on huge table)

**Solution:**
1. Check `snappy-dev.sql` for indexes
2. Add indexes on frequently queried columns (needs approval)
3. Optimize queries (use JOINs instead of loops)
4. Add LIMIT to large result sets

## Build System Issues

### Build Hangs or Takes Forever

**Possible Causes:**
- Large dependencies being processed
- Memory issue
- Infinite loop in code

**Solution:**
1. Wait a few minutes (builds can be slow)
2. Check for syntax errors that might cause issues
3. Try Ctrl+C to cancel, fix any errors, rebuild
4. Check build script if consistently slow

### Build Output Location Wrong

**Problem:** Build succeeds but files not in virtual host

**Check:**
1. Look at `snappy-build.sh` script (don't modify)
2. Check if files are being copied
3. Check permissions on target directory

**Solution:**
- Report to user if build output location is wrong
- Don't modify `snappy-build.sh` without permission

### node_modules Issues

**Error:** "Cannot find module"

**Solution:**
```bash
# Reinstall dependencies
rm -rf node_modules
npm install

# Then rebuild
./snappy-build.sh
```

## General Debugging Strategies

### Step-by-Step Isolation

1. **Identify the layer:**
   - Frontend issue (browser/Vue)
   - Backend issue (PHP/API)
   - Database issue (MariaDB)
   - Integration issue (frontend + backend)

2. **Test each layer independently:**
```
   Database: Test query in database client
      ↓ Works?
   Backend: Test API with Postman/curl
      ↓ Works?
   Frontend: Check console, network tab
      ↓ Works?
   Integration: Test complete flow
```

3. **Narrow down the problem:**
   - If database query works but API doesn't → Problem in PHP
   - If API works but frontend doesn't → Problem in Vue/JS
   - If both work separately but not together → Integration issue

### Reading Error Messages

**Error messages usually tell you:**
1. **What** went wrong
2. **Where** it went wrong (file, line number)
3. **Why** it went wrong (type of error)

**Example:**
```
SyntaxError: Unexpected token '}' in CommentList.vue at line 45
```
- **What:** Syntax error (unexpected })
- **Where:** CommentList.vue, line 45
- **Why:** Extra closing brace

**Solution:**
1. Open CommentList.vue
2. Go to line 45
3. Look for extra } or missing {
4. Fix and rebuild

### Console Logging (Debugging)

**Backend (PHP):**
```php
// Temporary debugging (remove after fixing)
error_log("User ID: " . $userId);
error_log("Query result: " . print_r($result, true));
```

**Frontend (Vue/JS):**
```javascript
// Temporary debugging (remove after fixing)
console.log('User data:', userData);
console.log('API response:', response);
```

**Remember:** Remove console.log and error_log after debugging

### Network Tab Investigation

**Browser Network Tab (F12) shows:**
1. **Request:**
   - URL called
   - Method (GET, POST, etc.)
   - Headers sent
   - Body sent

2. **Response:**
   - Status code (200 = success, 404 = not found, 500 = server error)
   - Headers received
   - Body received (data)

**Common Status Codes:**
- 200: Success
- 201: Created successfully
- 400: Bad request (invalid data sent)
- 401: Unauthorized (not logged in)
- 403: Forbidden (no permission)
- 404: Not found (wrong URL)
- 500: Server error (PHP error, check logs)

## When Stuck

### Before Asking for Help

**Gather information:**
1. What were you trying to do?
2. What did you expect to happen?
3. What actually happened?
4. Error messages (complete text)
5. File names and line numbers
6. What have you already tried?

### Information to Report
```markdown
**Problem:** [Brief description]

**Expected:** [What should happen]

**Actual:** [What's happening]

**Error Messages:**
```
[Paste complete error]
```

**What I've Tried:**
1. [First thing tried]
2. [Second thing tried]
3. [etc.]

**Files Involved:**
- path/to/file.php (line X)
- src/components/Component.vue (line Y)

**Context:**
- Working on: [TODO/BUG task]
- Last successful step: [what worked]
- First failing step: [where it broke]
```

This gives enough context to help solve the problem quickly.

## Quick Troubleshooting Checklist

### Frontend Not Working
1. [ ] Build succeeded? (`./snappy-build.sh`)
2. [ ] Browser cache cleared? (Ctrl+Shift+R)
3. [ ] Console errors? (F12 → Console)
4. [ ] Network errors? (F12 → Network)
5. [ ] Correct URL? (virtual host)
6. [ ] Symlinks work?

### Backend Not Working
1. [ ] PHP error logs checked?
2. [ ] Database connection works?
3. [ ] Query tested in database client?
4. [ ] API tested with Postman/curl?
5. [ ] Correct endpoint URL?
6. [ ] Parameters sent correctly?

### Database Not Working
1. [ ] Checked `snappy-dev.sql` for schema?
2. [ ] Column names correct (snake_case)?
3. [ ] Foreign keys exist?
4. [ ] NOT NULL fields have values?
5. [ ] Query syntax valid?
6. [ ] Using prepared statements?

### Everything Broken
1. [ ] Check if services running (database, web server)
2. [ ] Check recent changes (git log)
3. [ ] Can revert recent commit if needed
4. [ ] Check file permissions
5. [ ] Restart services (may need user help)