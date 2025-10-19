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

## Before Starting ANY Task

1. **Read TODO.md** to understand current priorities and context
2. **Check database schema** in `snappy-dev.sql` if task involves data/API
3. **Identify which side** of the project you're working on:
   - Backend (PHP/MariaDB)
   - Frontend (Vue/Vite)
   - Both (full-stack feature)
4. **Announce:** "Starting: [task name] - [Backend/Frontend/Full-stack]"
5. **Move task** to "In Progress 🚧" section in TODO.md

## During Development

### Backend (PHP) Standards
- Follow existing PHP code style in the project
- Use prepared statements for ALL database queries (security!)
- Error handling: Use try-catch blocks and log errors appropriately
- API responses: Return consistent JSON format
- **Never hardcode database credentials** - use existing config pattern
- Test API endpoints before marking task complete

### Frontend (Vue) Standards
- Follow existing Vue component patterns
- Use Composition API if that's the project standard, Options API if that's the pattern
- Keep components focused and reusable
- Use Vite environment variables for configuration
- Follow existing state management pattern (Vuex/Pinia/composables)

### Database Changes
- **CRITICAL:** If you need to modify database schema:
  1. **STOP and ask permission first**
  2. Explain what changes are needed and why
  3. Wait for approval before proceeding
  4. Document changes clearly
  5. Update `snappy-dev.sql` only after approval
- For queries: Reference `snappy-dev.sql` to understand exact table structure, column names, and relationships

### File Organization
- Backend PHP files: Follow existing backend directory structure
- Vue components: `src/components/` (or existing pattern)
- Vue views/pages: `src/views/` (or existing pattern)
- Assets: `src/assets/`
- Keep frontend and backend concerns separated

## Testing Workflow

### After Backend Changes:
1. Test PHP endpoint functionality
2. Check error handling and edge cases
3. Verify database queries work correctly
4. Note any API changes that affect frontend

### After Frontend Changes:
1. Run the build: `./snappy-build.sh`
2. Check build completes without errors
3. Test in local virtual host environment
4. Verify symlinks to PHP backend still work
5. Test in browser (functionality, console errors, network requests)

### After Full-Stack Changes:
1. Test backend API changes first
2. Then run frontend build: `./snappy-build.sh`
3. Test complete feature flow in virtual host
4. Verify frontend-backend integration works

## After Completing ANY Task

1. **Update TODO.md:**
   - Move task from "In Progress" to "Completed ✅"
   - Add completion date: `- [x] Task name (YYYY-MM-DD)`
   - Add any relevant notes

2. **Update COMPLETED.md** with:
   - Date and task name
   - Commit hash (after you commit)
   - Files changed (Backend/Frontend/Database)
   - Implementation summary
   - Any important decisions or gotchas
   - Testing notes

3. **If you modified frontend:** Note that `./snappy-build.sh` should be run

4. **If you modified database schema:** Clearly flag this in COMPLETED.md

5. **Suggest next task:** "Next task from TODO.md: [task name]. Should I proceed?"

## Git Workflow

### Commit Strategy:
- Commit after each completed TODO item
- **Separate commits** for backend and frontend when possible
- Commit message format:
  - `feat(backend): [description]` - New backend feature
  - `feat(frontend): [description]` - New frontend feature  
  - `fix(backend): [description]` - Backend bugfix
  - `fix(frontend): [description]` - Frontend bugfix
  - `chore(db): [description]` - Database schema changes
  - `build: [description]` - Build script changes

### Before Committing Frontend:
- Run `./snappy-build.sh` to ensure build works
- Check for build errors or warnings

### What to Commit:
- ✅ Source code (PHP, Vue components)
- ✅ TODO.md and COMPLETED.md updates
- ✅ Database schema changes (snappy-dev.sql) - WITH APPROVAL ONLY
- ❌ Build output (likely in .gitignore already)
- ❌ node_modules
- ❌ Vendor directories

## Build & Deployment

### When to Run Build:
- After ANY frontend changes
- Before testing in virtual host
- Before marking frontend tasks complete

### Build Command:
```bash
./snappy-build.sh
```

### If Build Fails:
1. Read error messages carefully
2. Check for Vue/Vite syntax errors
3. Verify all imports are correct
4. Check for missing dependencies
5. Report the error and ask for guidance

### After Successful Build:
- Verify files copied to virtual host correctly
- Verify symlinks to PHP backend still work
- Test the actual functionality in browser

## API Development

### Creating/Modifying Endpoints:
1. Check `snappy-dev.sql` for exact table/column names
2. Use prepared statements: `$stmt = $pdo->prepare("SELECT...")`
3. Return consistent JSON format
4. Handle errors gracefully
5. Document endpoint behavior in COMPLETED.md

### API Response Format (follow existing pattern):
```json
{
  "success": true/false,
  "data": {...},
  "message": "Optional message",
  "error": "Error details if success=false"
}
```

## Common Gotchas

### Database:
- Column names might have underscores: `user_id` not `userId`
- Check for nullable columns before inserting
- MariaDB date format: 'YYYY-MM-DD HH:MM:SS'
- Always check foreign key constraints in `snappy-dev.sql`
- Instead of boolean type, use TNYINT

### PHP:
- Check existing error handling patterns
- Use existing database connection method
- Follow existing auth/session patterns
- Be careful with superglobals ($_GET, $_POST, etc.)

### Vue/Vite:
- Environment variables must start with `VITE_`
- Hot reload works in dev, but changes need build for virtual host
- Check existing API base URL configuration
- Proxy settings for API calls during development

### Build/Deploy:
- Build must complete successfully before testing
- Symlinks can break - verify they work after build
- Check file permissions after deployment
- Clear browser cache if changes don't appear

## When You're Unsure

### Ask Before:
- Modifying `snappy-dev.sql`
- Modifying `snappy-build.sh`
- Changing database connection configuration
- Adding new dependencies (composer packages, npm packages)
- Changing build output directories
- Modifying symlink structure

### You Can Proceed Without Asking:
- Creating new PHP endpoints following existing patterns
- Creating new Vue components
- Modifying existing components/endpoints
- Fixing bugs
- Adding features to TODO.md items

## Troubleshooting Checklist

### Frontend Not Working:
- [ ] Did you run `./snappy-build.sh`?
- [ ] Did build complete without errors?
- [ ] Are build files in the correct location?
- [ ] Check browser console for errors
- [ ] Check Network tab for failed API calls
- [ ] Verify symlinks to backend are intact

### Backend Not Working:
- [ ] Check PHP error logs
- [ ] Verify database connection
- [ ] Test SQL query syntax in database directly
- [ ] Check prepared statement bindings
- [ ] Verify endpoint URL is correct

### Database Issues:
- [ ] Reference `snappy-dev.sql` for schema
- [ ] Check table/column names match exactly
- [ ] Verify foreign key constraints
- [ ] Check for required NOT NULL fields
- [ ] Test query in MariaDB directly first

## Progress Tracking

### TODO.md Sections to Maintain:
- **In Progress 🚧** - Currently working on
- **Up Next 📋** - Prioritized backlog
- **Completed ✅** - Done with dates
- **Blocked 🔒** - Waiting on something
- **Future 💡** - Ideas for later

### COMPLETED.md Should Include:
- Date
- Task description
- Commit hash
- Backend/Frontend/Database classification
- Files modified
- Implementation notes
- Testing notes
- Any gotchas or decisions

## Quick Reference

### Key Commands:
```bash
# Build frontend
./snappy-build.sh

# Check git status
git status

# View database schema
cat snappy-dev.sql | less
```

### Key Files:
- `snappy-dev.sql` - Database schema (READ ONLY without permission)
- `snappy-build.sh` - Build script (READ ONLY without permission)
- `TODO.md` - Task tracking
- `COMPLETED.md` - Work log
- `.clinerules` - This file

## Summary

1. ✅ Always read TODO.md first
2. ✅ Check snappy-dev.sql for schema details
3. ✅ Test backend changes directly
4. ✅ Run ./snappy-build.sh after frontend changes
5. ✅ Test in virtual host environment
6. ✅ Update TODO.md and COMPLETED.md after each task
7. ✅ Commit with clear, categorized messages
8. ✅ Ask before modifying critical files
9. ✅ Suggest next task when done

---

*Last updated: 2025-10-19*