# Snappy Project Rules

## Project Architecture

- **Backend:** Vanilla PHP with MariaDB database
- **Frontend:** Vue 3 with Vite build system
- **Database Schema:** Located in `snappy-dev.sql` in project root (authoritative source)
- **Build Script:** `snappy-build.sh` in project root
- **Deployment:** Build files copied to local virtual host with symlinks to PHP backend

## Critical Files - DO NOT MODIFY WITHOUT EXPLICIT PERMISSION

- `snappy-dev.sql` - Source of truth for database schema
- `snappy-build.sh` - Build and deployment script
- Any symlink configurations

---

## Work Management System

### File Structure and Purpose

- **TODO.md** - Planned features, enhancements, phases (flow-based work)
- **BUGS.md** - Bug fixes, issues, defects (interrupt-driven work)
- **COMPLETED.md** - Historical log of all completed work (features + bugs)
- **.clinerules** - This file (AI assistant instructions)

### When to Use Which File

**Add to TODO.md if:**

- New feature or enhancement
- Refactoring or improvement
- Technical debt cleanup
- Planned optimization
- "Nice to have" improvements
- Anything that's planned, not reactive

**Add to BUGS.md if:**

- Something is broken that previously worked
- User reports an issue
- You discover unexpected behavior
- Security vulnerability found
- Data corruption or loss risk
- Performance degradation
- Critical error that blocks users

---

## Before Starting ANY Work Session

### Mandatory Startup Checklist:

1. **Read TODO.md** to understand current priorities and context
2. **Read BUGS.md** to check for urgent issues
3. **Assess priorities:**
   - 🔴 Critical bugs? → Drop everything, fix immediately
   - 🟠 High bugs? → Suggest fixing before TODO work
   - 🟡🟢 Medium/Low bugs? → Note them, prioritize TODO work
4. **Announce status:** 
   - "Checking project status..."
   - Report: X bugs (by severity), Y TODO tasks in progress/up next
   - Recommend what to work on and why

### Priority Decision Tree:

```
🔴 Critical Bugs → Fix immediately (pause all TODO work)
      ↓ (none)
🟠 High Bugs → Suggest: "Fix now or after current TODO task?"
      ↓ (none or deferred)
TODO.md → Continue with planned work (check what's "In Progress")
```

---

## Before Starting ANY Task

### For TODO.md Tasks:

1. **Read TODO.md** to find the task
2. **Check database schema** in `snappy-dev.sql` if task involves data/API
3. **Identify which side** of project:
   - Backend (PHP/MariaDB)
   - Frontend (Vue/Vite)
   - Both (full-stack feature)
4. **Move task** from "Up Next" to "In Progress 🚧" section in TODO.md
5. **Announce:** "Starting: [task name] - [Backend/Frontend/Full-stack]"

### For BUGS.md Tasks:

1. **Read the bug description** completely
2. **Understand severity** and confirm it's appropriate priority
3. **Check related code** to understand context
4. **Move bug** to "In Progress 🚧" section in BUGS.md
5. **Announce:** "Fixing bug: [bug description] - [Severity]"

---

## During Development

### Backend (PHP) Standards

- Follow existing PHP code style in the project

- **Use prepared statements for ALL database queries** (security!)
  
  ```php
  // Good
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  
  // Bad - NEVER do this
  $query = "SELECT * FROM users WHERE id = $userId";
  ```

- Error handling: Use try-catch blocks and log errors appropriately

- API responses: Return consistent JSON format (check existing endpoints)

- **Never hardcode database credentials** - use existing config pattern

- Test API endpoints manually before marking task complete (use Postman, curl, or browser)

### Frontend (Vue) Standards

- Follow existing Vue component patterns in the project
- Use Composition API if that's the project standard, Options API if that's the pattern
- Keep components focused and reusable
- Use Vite environment variables for configuration (must start with `VITE_`)
- Follow existing state management pattern (Vuex/Pinia/composables)
- Test components in browser after changes

### Database Standards

- **CRITICAL:** If you need to modify database schema:
  1. **STOP and ask permission first**
  2. Explain what changes are needed and why
  3. Wait for approval before proceeding
  4. Document changes clearly
  5. Update `snappy-dev.sql` only after approval and testing
- For queries: **Always reference `snappy-dev.sql`** to understand:
  - Exact table names
  - Exact column names (watch for underscores: `user_id` not `userId`)
  - Data types
  - Constraints (NOT NULL, UNIQUE, etc.)
  - Foreign key relationships
  - Indexes

### File Organization

- Backend PHP files: Follow existing backend directory structure
- Vue components: `src/components/` (or existing pattern in project)
- Vue views/pages: `src/views/` (or existing pattern)
- Vue utilities: `src/utils/`
- Assets: `src/assets/`
- Keep frontend and backend concerns separated

### Code Quality

- Write clear, self-documenting code
- Add comments for complex logic
- Use meaningful variable names
- Follow DRY principle (Don't Repeat Yourself)
- If you find duplicated code, suggest refactoring to TODO.md

### When You Discover Issues During Work

**If you find a bug while working:**

1. Assess severity (Critical/High/Medium/Low)
2. If **Critical**: Stop current work, add to BUGS.md, fix immediately
3. If **High**: Add to BUGS.md, suggest fixing before continuing
4. If **Medium/Low**: Add to BUGS.md, continue current task

**If you discover new tasks needed:**

1. Add them to TODO.md in appropriate section
2. Note them in your work summary
3. Don't start them unless asked

**If you find technical debt:**

1. Note it in TODO.md under "Technical Debt" section
2. Continue with current task unless it blocks progress

---

## Bug Tracking System

### Bug Priority Levels

**🔴 Critical (Drop Everything)**

- Site is down or unusable
- Data loss occurring
- Security breach
- Payment/critical business function broken
- Authentication completely broken
- Example: "Users cannot login at all"
- **Action:** Fix immediately, interrupt any TODO work

**🟠 High (Fix Today/Tomorrow)**

- Major feature broken
- Affects many users
- Workaround exists but painful
- Significant data inconsistency
- Example: "File upload fails for files >5MB"
- **Action:** Complete current TODO task, then fix bug before next TODO

**🟡 Medium (Fix This Week)**

- Affects some users
- Workaround available
- Not blocking critical paths
- Annoying but not breaking
- Example: "Date format shows incorrectly in user profile"
- **Action:** Schedule in current sprint, fit between TODO tasks

**🟢 Low (Fix When Convenient)**

- Minor annoyance
- Rarely encountered
- Cosmetic issues
- Development-only issues (console warnings)
- Example: "Button alignment off by 2px on mobile"
- **Action:** Batch with similar bugs, or fix during refactoring

### Bug Workflow

**When Bug is Discovered:**

1. Add to BUGS.md immediately with:
   - Clear description
   - Steps to reproduce (if applicable)
   - Severity emoji and label
   - Area (Backend/Frontend/Database)
   - Discovered date
2. Don't disrupt current work unless Critical
3. Assess and announce priority

**When Fixing a Bug:**

1. Move bug to "In Progress" section in BUGS.md
2. Read bug description completely
3. Reproduce the bug if possible
4. Identify root cause
5. Implement fix
6. Test thoroughly (including edge cases)
7. Update BUGS.md (move to Fixed section with date)
8. Add entry to COMPLETED.md with [BUG] tag
9. Commit with prefix: `fix(area): description`

**After Fixing Bug, Consider Prevention:**

- Does this reveal missing validation? → Add to TODO.md
- Does this show gaps in error handling? → Add to TODO.md
- Does this indicate brittle code? → Add refactoring to TODO.md
- Would tests have caught this? → Add testing task to TODO.md

---

## Testing Workflow

### After Backend Changes:

1. Test PHP endpoint functionality directly
   - Use browser, Postman, curl, or similar
   - Test success cases
   - Test error cases (invalid input, missing data, etc.)
2. Check error handling and edge cases
3. Verify database queries work correctly
   - Check data is saved correctly
   - Check foreign key constraints work
   - Check for SQL errors in logs
4. Note any API changes that affect frontend
5. Document endpoints in comments or COMPLETED.md

### After Frontend Changes:

1. **Run the build:** `./snappy-build.sh`
2. Check build completes without errors or warnings
3. Test in local virtual host environment
4. Verify symlinks to PHP backend still work
5. Test in browser:
   - Functionality works as expected
   - Check browser console for errors
   - Check Network tab for failed API requests
   - Test responsive design (mobile/tablet/desktop)
6. Test user interactions and edge cases

### After Full-Stack Changes:

1. Test backend API changes first (as above)
2. Then run frontend build: `./snappy-build.sh`
3. Test complete feature flow in virtual host:
   - User journey from start to finish
   - Error states and edge cases
   - Data persistence (refresh page, check data still correct)
4. Verify frontend-backend integration works
5. Check for any console errors or warnings

### After Database Schema Changes:

1. **Verify schema in database** matches `snappy-dev.sql`
2. Test all affected endpoints
3. Check foreign key relationships work
4. Verify data migration (if applicable)
5. Test rollback plan if needed

### Testing Checklist:

- [ ] Happy path works (normal user flow)
- [ ] Error handling works (invalid input, network errors)
- [ ] Edge cases handled (empty data, very long input, special characters)
- [ ] No console errors
- [ ] No SQL errors in logs
- [ ] Responsive on different screen sizes (if frontend)
- [ ] Build succeeds without warnings (if frontend)

---

## After Completing ANY Task

### Mandatory Completion Steps:

**1. Update Appropriate Tracking File:**

For TODO.md tasks:

- Move task from "In Progress" to "Completed ✅" section
- Add completion date: `- [x] Task name (YYYY-MM-DD)`
- If task created subtasks, ensure they're added to TODO.md

For BUGS.md tasks:

- Move bug from "In Progress" to "Fixed ✅" section
- Add fix date and commit hash
- Note if bug revealed other issues

**2. Update COMPLETED.md:**

For features (TODO.md):

```markdown
### ✅ [FEATURE] Task Name
**Source:** TODO.md
**Commit:** [commit hash after you commit]
**Type:** Backend / Frontend / Full-stack / Database

**Implementation Details:**
- What was built
- How it was implemented
- Key decisions made

**Files Changed:**
- path/to/file1.php
- src/components/Component.vue

**Testing:**
- What was tested
- Any issues found and resolved

**Notes:**
- Any gotchas or important context
- Performance considerations
- Security considerations
```

For bugs (BUGS.md):

```markdown
### ✅ [BUG] 🟠 Bug Description
**Source:** BUGS.md
**Commit:** [commit hash]
**Type:** Backend / Frontend / Database
**Severity:** Critical / High / Medium / Low

**Issue:** What was broken
**Root Cause:** Why it was broken
**Solution:** How it was fixed

**Testing:**
- How you verified the fix
- Edge cases tested

**Prevention:**
- Any tasks added to TODO.md to prevent similar bugs
```

**3. If Frontend Work:** Note that `./snappy-build.sh` should be run

**4. If Database Schema Changed:** Clearly flag this in COMPLETED.md with details

**5. Suggest Next Task:**

- Check BUGS.md for Critical/High bugs first
- If none, suggest next TODO.md task
- Format: "Next priority: [task name] from [TODO.md/BUGS.md]. Should I proceed?"

---

## Git Workflow

### Commit Strategy

- Commit after each completed TODO item or bug fix
- **Separate commits** for backend and frontend when possible
- Write clear, descriptive commit messages
- Reference tracking files in commits when helpful

### Commit Message Format

**Features (TODO.md):**

- `feat(backend): add user registration endpoint`
- `feat(frontend): create comment list component`
- `feat(fullstack): implement comment system`

**Bug Fixes (BUGS.md):**

- `fix(backend): prevent SQL injection in search`
- `fix(frontend): correct date formatting in posts`
- `fix(database): add missing foreign key constraint`

**Other:**

- `chore(db): update database schema` - Database changes
- `build: update build script for production`
- `docs: update TODO.md with new tasks`
- `refactor(backend): consolidate database connection code`
- `style(frontend): fix button alignment on mobile`

### Before Committing Frontend Work:

1. Run `./snappy-build.sh` to ensure build succeeds
2. Check for build errors or warnings
3. Test in virtual host environment
4. Verify functionality works

### What to Commit:

- ✅ Source code (PHP files, Vue components, JS, CSS)
- ✅ Configuration files (if changed)
- ✅ TODO.md, BUGS.md, COMPLETED.md updates
- ✅ Database schema changes (`snappy-dev.sql`) - WITH APPROVAL ONLY
- ✅ Documentation updates
- ❌ Build output (should be in .gitignore)
- ❌ node_modules (should be in .gitignore)
- ❌ vendor directories (should be in .gitignore)
- ❌ Local configuration (credentials, local paths)

### Git Commands Reference:

```bash
# Check status
git status

# Stage files
git add path/to/file
git add .

# Commit
git commit -m "feat(backend): add comment endpoint"

# View recent commits
git log --oneline -5

# View changes
git diff
```

---

## Build & Deployment

### When to Run Build

- After **ANY** frontend changes (Vue components, JS, CSS)
- Before testing in virtual host
- Before marking frontend tasks complete
- Before committing frontend work

### Build Command

```bash
./snappy-build.sh
```

### If Build Fails:

1. **Read error messages carefully** - they usually indicate the problem
2. Common issues:
   - Vue/Vite syntax errors (check line numbers in error)
   - Missing imports or incorrect paths
   - Undefined variables or components
   - Missing dependencies (check package.json)
3. Fix the issue
4. Run build again
5. If stuck, report the error and ask for guidance

### After Successful Build:

1. Verify files copied to virtual host correctly
2. Verify symlinks to PHP backend still work
3. Clear browser cache if needed
4. Test the actual functionality in browser
5. Check browser console for any errors

### Build Troubleshooting Checklist:

- [ ] All imports correct (check file paths)
- [ ] No syntax errors (check line numbers in error message)
- [ ] All used components are imported
- [ ] Environment variables start with `VITE_`
- [ ] No unused imports or variables (may cause warnings)

---

## API Development

### Creating/Modifying Endpoints

**Before writing code:**

1. Check `snappy-dev.sql` for exact table/column names
2. Review existing endpoints for patterns and conventions
3. Plan the endpoint signature (method, path, parameters, response)

**While writing code:**

1. **Always use prepared statements:**
   
   ```php
   $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ?");
   $stmt->execute([$userId]);
   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
   ```
2. Validate all input
3. Handle errors gracefully
4. Return consistent JSON format (follow existing pattern)
5. Add appropriate HTTP status codes

**API Response Format** (follow existing pattern in project):

```json
{
  "success": true,
  "data": {
    // response data here
  },
  "message": "Optional success message"
}
```

**Error Response Format:**

```json
{
  "success": false,
  "error": "Error description",
  "message": "User-friendly error message"
}
```

**After creating endpoint:**

1. Test with success cases
2. Test with error cases (missing params, invalid data, etc.)
3. Document endpoint in code comments or COMPLETED.md:
   - Method and path
   - Parameters (required/optional)
   - Response format
   - Error cases

---

## Common Gotchas & Solutions

### Database Issues

**Column Naming:**

- Database columns likely use underscores: `user_id`, `created_at`, `post_id`
- JavaScript/Vue typically uses camelCase: `userId`, `createdAt`, `postId`
- Convert between them as needed

**Common Issues:**

- ❌ Using wrong column name → Check `snappy-dev.sql`
- ❌ Forgetting nullable columns → Check schema for NULL/NOT NULL
- ❌ Wrong date format → MariaDB uses 'YYYY-MM-DD HH:MM:SS'
- ❌ Violating foreign keys → Check relationships in schema
- ❌ Missing data in INSERT → Check for NOT NULL columns

**Solutions:**

- Always reference `snappy-dev.sql` first
- Test queries in database directly before using in code
- Check for foreign key constraints
- Use prepared statements (prevents SQL injection AND helps catch errors)

### PHP Issues

**Common Issues:**

- ❌ SQL injection vulnerability → Use prepared statements
- ❌ Not checking if variable exists → Use `isset()` or `??` operator
- ❌ Wrong superglobal → $_GET for query params, $_POST for body data
- ❌ Not setting response headers → Set `Content-Type: application/json`
- ❌ Not handling exceptions → Use try-catch blocks

**Solutions:**

- Follow existing error handling patterns in the project
- Use existing database connection method (don't create new ones)
- Follow existing auth/session patterns
- Test API endpoints manually
- Check PHP error logs if something fails

### Vue/Vite Issues

**Common Issues:**

- ❌ Environment variables not working → Must start with `VITE_`
- ❌ Changes not appearing → Need to run build for virtual host testing
- ❌ Component not found → Check import path and component name
- ❌ Props not working → Check prop definition and usage
- ❌ Reactive data not updating → Check Vue reactivity rules

**Solutions:**

- Use `import.meta.env.VITE_API_URL` for env variables
- Hot reload works in dev (`npm run dev`), but changes need build for virtual host
- Check existing API base URL configuration in project
- Check for any proxy settings for API calls during development
- Use Vue DevTools browser extension for debugging

### Build/Deploy Issues

**Common Issues:**

- ❌ Build fails → Check error message for syntax errors
- ❌ Changes don't appear → Run build, clear browser cache
- ❌ Symlinks broken → Verify symlink configuration
- ❌ File permissions wrong → Check after deployment
- ❌ 404 errors → Check paths and routing configuration

**Solutions:**

- Build must complete successfully before testing
- Check `snappy-build.sh` script if build behavior is unexpected
- Verify files copied to correct location in virtual host
- Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
- Check browser Network tab for failed requests

---

## When to Ask for Help

### Ask Permission Before:

- Modifying `snappy-dev.sql` (database schema changes)
- Modifying `snappy-build.sh` (build script changes)
- Changing database connection configuration
- Adding new dependencies:
  - PHP: composer packages
  - Frontend: npm packages
- Changing build output directories or paths
- Modifying symlink structure or virtual host configuration
- Making security-related changes (auth, permissions, etc.)
- Changing API response formats (breaks frontend)

### You Can Proceed Without Asking:

- Creating new PHP endpoints following existing patterns
- Creating new Vue components following existing patterns
- Modifying existing components/endpoints (following patterns)
- Fixing bugs (document what you did)
- Adding features from TODO.md
- Adding bugs to BUGS.md
- Updating TODO.md or BUGS.md or COMPLETED.md
- Writing tests
- Refactoring code (if it doesn't change behavior)

### When Uncertain:

- Explain what you want to do
- Explain why you think it's needed
- Ask if you should proceed or if there's a better approach
- It's better to ask than to make wrong assumptions

---

## Progress Tracking & Reporting

### TODO.md Structure to Maintain

**Required Sections:**

- **In Progress 🚧** - Currently working on (move tasks here when starting)
- **Up Next 📋** - Prioritized backlog (organized by phase/category)
- **Completed ✅** - Done with dates (format: `- [x] Task (YYYY-MM-DD)`)
- **Blocked 🔒** - Waiting on something (explain what's blocking)
- **Future 💡** - Ideas for later (not prioritized yet)

**Optional Sections:**

- This Week / This Sprint - Current focus
- Technical Debt - Code that needs refactoring
- By Phase (Phase 1, Phase 2, etc.)
- By Area (Backend, Frontend, Database)

### BUGS.md Structure to Maintain

**Required Sections:**

- **Critical 🔴** - Fix immediately
- **High Priority 🟠** - Fix today/tomorrow
- **Medium Priority 🟡** - Fix this week
- **Low Priority 🟢** - Fix when convenient
- **Fixed ✅** - Completed bug fixes with dates
- **Won't Fix / By Design** - Closed without fixing (with reason)

**Optional Sections:**

- In Progress 🚧 - Currently fixing
- Bug Statistics - Summary counts
- Notes - Common patterns, prevention strategies

### COMPLETED.md Structure

- Chronological order (most recent first)
- Separate entries for features vs bugs
- Tag each entry: [FEATURE] or [BUG] with severity
- Include commit hashes for traceability
- Group by date

### When Generating Reports

**Daily Status:**

```
- Completed today: X features, Y bugs
- In progress: [current task]
- Blockers: [any issues]
- Next up: [next priority]
```

**Weekly Summary:**

```
- Features completed: [list from TODO.md]
- Bugs fixed: [list from BUGS.md with severity]
- Open bugs by severity: [counts]
- Next week focus: [planned work]
- Velocity: [completed items]
```

**Sprint Review:**

```
- Planned vs actual completion
- Bug trends (discovered vs fixed)
- Common bug sources identified
- Technical debt addressed
- Blockers resolved/remaining
```

---

## Troubleshooting Checklists

### Frontend Not Working

- [ ] Did you run `./snappy-build.sh`?
- [ ] Did build complete without errors?
- [ ] Are build files in the correct location?
- [ ] Check browser console for JavaScript errors
- [ ] Check Network tab for failed API calls (404, 500, etc.)
- [ ] Verify symlinks to backend PHP files are intact
- [ ] Clear browser cache and hard reload (Ctrl+Shift+R)
- [ ] Check if API endpoint exists and is accessible
- [ ] Verify environment variables are set correctly

### Backend Not Working

- [ ] Check PHP error logs (location varies by setup)
- [ ] Verify database connection is working
- [ ] Test SQL query syntax in database directly (phpMyAdmin, etc.)
- [ ] Check prepared statement parameter binding
- [ ] Verify endpoint URL is correct (method, path)
- [ ] Check request parameters are being sent correctly
- [ ] Verify authentication/session is working
- [ ] Check for PHP syntax errors
- [ ] Ensure required PHP extensions are loaded

### Database Issues

- [ ] Reference `snappy-dev.sql` for exact schema
- [ ] Check table names match exactly (case-sensitive on Linux)
- [ ] Check column names match exactly (underscores vs camelCase)
- [ ] Verify foreign key constraints are satisfied
- [ ] Check for required NOT NULL fields in INSERT
- [ ] Verify data types match (string vs int, etc.)
- [ ] Test query directly in database client first
- [ ] Check for index on columns used in WHERE/JOIN
- [ ] Verify user has correct database permissions

### Build Issues

- [ ] Check error message for specific file and line number
- [ ] Look for syntax errors in Vue components
- [ ] Verify all imports are correct (path and component name)
- [ ] Check for undefined variables or functions
- [ ] Ensure all used components are imported
- [ ] Verify environment variables start with `VITE_`
- [ ] Check package.json for missing dependencies
- [ ] Try deleting node_modules and reinstalling (`npm install`)
- [ ] Check for circular dependencies

---

## Quick Reference

### Key Commands

```bash
# Build frontend
./snappy-build.sh

# Git operations
git status
git add .
git commit -m "feat(area): description"
git log --oneline -5

# View database schema
cat snappy-dev.sql | less
grep "CREATE TABLE" snappy-dev.sql
```

### Key Files

- `snappy-dev.sql` - Database schema (READ ONLY without permission)
- `snappy-build.sh` - Build script (READ ONLY without permission)
- `TODO.md` - Feature and enhancement tracking
- `BUGS.md` - Bug tracking
- `COMPLETED.md` - Historical work log
- `.clinerules` - This file (AI assistant instructions)

### Priority Quick Reference

1. 🔴 Critical bugs - Drop everything
2. 🟠 High bugs - Fix soon (today/tomorrow)
3. ✅ Complete current TODO task
4. 📋 Next TODO task from "Up Next"
5. 🟡 Medium bugs - Schedule this week
6. 🟢 Low bugs - Batch fix or fix during refactoring

---

## Summary: Your Primary Responsibilities

### Every Session Start:

1. ✅ Read .clinerules (this file)
2. ✅ Read TODO.md and BUGS.md
3. ✅ Report status (bugs by severity, TODO progress)
4. ✅ Recommend priority (Critical bugs → High bugs → TODO tasks)

### Every Task:

1. ✅ Check `snappy-dev.sql` for schema if needed
2. ✅ Follow code standards (prepared statements, error handling, etc.)
3. ✅ Test thoroughly (backend direct, frontend in browser)
4. ✅ Run `./snappy-build.sh` if frontend changed
5. ✅ Update tracking files (TODO.md or BUGS.md → COMPLETED.md)
6. ✅ Commit with clear message
7. ✅ Suggest next priority

### Always:

- ✅ Use prepared statements in PHP (never concatenate SQL)
- ✅ Reference `snappy-dev.sql` for exact schema
- ✅ Run build after frontend changes
- ✅ Test in virtual host before marking complete
- ✅ Ask permission before modifying critical files
- ✅ Add discovered bugs to BUGS.md
- ✅ Add discovered tasks to TODO.md
- ✅ Write clear, maintainable code
- ✅ Document important decisions in COMPLETED.md

### Never:

- ❌ Modify `snappy-dev.sql` without explicit permission
- ❌ Modify `snappy-build.sh` without explicit permission
- ❌ Use SQL string concatenation (SQL injection risk)
- ❌ Skip testing before marking task complete
- ❌ Forget to run build after frontend changes
- ❌ Leave tasks in "In Progress" when done
- ❌ Skip updating COMPLETED.md
- ❌ Make assumptions about database schema (check the dump file)

---

## Philosophy

**You are a collaborative partner in this project.** Your role is to:

- Help plan and organize work (TODO.md, BUGS.md)
- Write high-quality, tested code following project patterns
- Maintain clear documentation of what was done (COMPLETED.md)
- Proactively identify issues and opportunities
- Ask questions when uncertain
- Prioritize correctly (Critical bugs → High bugs → Planned features)
- Keep the project moving forward efficiently

**The goal is maintainable, tested, working software** that solves real problems for users.

---

*Last updated: 2025-10-20*
*Project: Snappy (PHP/Vue/MariaDB)*