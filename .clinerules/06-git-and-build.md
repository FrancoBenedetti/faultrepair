# Git Workflow and Build Process

## Git Commit Strategy

### When to Commit
- After each completed TODO item
- After each bug fix
- After completing a logical unit of work
- Before trying experimental approaches
- At end of work session (if work is complete)

### What to Commit
✅ **DO commit:**
- Source code (PHP, Vue, JS, CSS)
- Configuration files (if changed)
- TODO.md, BUGS.md, COMPLETED.md
- Documentation updates
- Database schema (`snappy-dev.sql`) - ONLY WITH APPROVAL

❌ **DON'T commit:**
- Build output (dist/, build/)
- node_modules/
- vendor/ (PHP dependencies)
- Local config (credentials, local paths)
- Log files
- Temporary files
- IDE-specific files (.vscode/, .idea/)

## Commit Message Format

### Structure
```
type(scope): brief description

Optional longer explanation if needed
```

### Types

**feat** - New feature
```
feat(backend): add user registration endpoint
feat(frontend): create comment list component
feat(fullstack): implement comment system with API and UI
```

**fix** - Bug fix
```
fix(backend): prevent SQL injection in search
fix(frontend): correct date formatting in posts
fix(database): add missing foreign key constraint
```

**chore** - Maintenance
```
chore(db): update database schema
chore(deps): update Vue to 3.4.0
chore(docs): update README
```

**refactor** - Code improvement (no behavior change)
```
refactor(backend): consolidate database connection code
refactor(frontend): extract validation to composable
```

**style** - Formatting, whitespace
```
style(frontend): fix button alignment
style(backend): format code per PSR-12
```

**docs** - Documentation only
```
docs: update TODO.md with new tasks
docs: add API documentation for comments
```

**build** - Build system
```
build: update snappy-build.sh for production
build: add minification to Vite config
```

**test** - Tests
```
test(backend): add tests for auth endpoints
test(frontend): add component tests
```

### Scope Examples
- `backend` - PHP code
- `frontend` - Vue components, JS
- `database` / `db` - Database/schema
- `fullstack` - Both backend and frontend
- `api` - API endpoints specifically
- `ui` - User interface
- `auth` - Authentication
- `build` - Build process
- `docs` - Documentation

### Good Commit Messages
```
✅ feat(backend): add comment creation endpoint with validation
✅ fix(frontend): prevent empty comment submission
✅ refactor(database): normalize user preferences to separate table
✅ chore(docs): update TODO.md after completing auth phase
```

### Poor Commit Messages
```
❌ update stuff
❌ fix bug
❌ changes
❌ WIP
❌ asdf
```

## Git Commands

### Basic Workflow
```bash
# Check what changed
git status

# Stage specific files
git add path/to/file.php
git add src/components/Comment.vue

# Stage all changes (use carefully)
git add .

# Commit with message
git commit -m "feat(backend): add comment endpoint"

# View recent commits
git log --oneline -5

# View what changed
git diff
git diff path/to/file.php
```

### Checking Status
```bash
# See what files changed
git status

# See actual changes
git diff

# See staged changes
git diff --cached
```

### Undoing Changes
```bash
# Discard changes to a file (CAREFUL!)
git checkout -- path/to/file.php

# Unstage a file
git reset HEAD path/to/file.php

# Revert to last commit (CAREFUL! Loses all changes)
git reset --hard HEAD
```

## Build Process

### When to Build

**ALWAYS build after:**
- Any Vue component changes
- Any JS/CSS changes
- Any frontend code changes
- Before testing in virtual host
- Before marking frontend task complete
- Before committing frontend work

**DON'T need to build after:**
- PHP-only changes
- Database-only changes
- Documentation changes (TODO.md, etc.)

### Build Command
```bash
./snappy-build.sh
```

### Build Success Checklist
After running build:
- [ ] Build completed without errors
- [ ] No critical warnings (investigate warnings)
- [ ] Files copied to virtual host location
- [ ] Symlinks to PHP backend still work
- [ ] Can access site at virtual host URL
- [ ] Test actual functionality in browser

### If Build Fails

1. **Read error message carefully**
   - Note file name and line number
   - Error usually indicates exact problem

2. **Common Build Errors:**
```
   Syntax error → Check line number, look for typos
   Cannot find module → Check import path
   Unexpected token → Check for missing/extra brackets
   Vue compilation error → Check template syntax
```

3. **Fix the error**
   - Go to file and line number mentioned
   - Fix the issue
   - Save file

4. **Run build again**
```bash
   ./snappy-build.sh
```

5. **Don't mark task complete** until build succeeds

### Build Troubleshooting

**Build fails with import error:**
```bash
# Check if module is installed
npm list module-name

# If missing, install
npm install
```

**Build succeeds but changes don't appear:**
```bash
# Clear browser cache
# Hard reload: Ctrl+Shift+R (or Cmd+Shift+R on Mac)

# Check files actually copied
ls -la ~/Projects/snappy/public

# Check symlinks
ls -la ~/Projects/snappy/public/backend  # Should show symlink
ls -la ~/Projects/snappy/public/snappy-admin
ls -la ~/Projects/snappy/public/all-logs
```

**Build is slow:**
- Normal for production builds
- Vite optimizes and minifies
- Development mode (`npm run dev`) is faster but not for virtual host

## Workflow Integration

### Complete Workflow for Frontend Task:

1. Make changes to Vue components
2. Test in dev mode: `npm run dev` (optional, for quick iteration)
3. Run build: `./snappy-build.sh`
4. Check build output for errors
5. Test in virtual host (browser)
6. If issues found, fix and repeat steps 3-5
7. Update TODO.md/BUGS.md
8. Update COMPLETED.md
9. Stage files: `git add`
10. Commit: `git commit -m "feat(frontend): ..."`

### Complete Workflow for Backend Task:

1. Make changes to PHP files
2. Test API directly (Postman/curl/browser)
3. Check database if data involved
4. Update TODO.md/BUGS.md
5. Update COMPLETED.md
6. Stage files: `git add`
7. Commit: `git commit -m "feat(backend): ..."`
8. NO BUILD NEEDED (backend only)

### Complete Workflow for Full-Stack Task:

1. Backend changes → Test backend
2. Frontend changes → Build and test frontend
3. Test integration end-to-end
4. Update TODO.md/BUGS.md
5. Update COMPLETED.md
6. Commit backend: `git commit -m "feat(backend): ..."`
7. Commit frontend: `git commit -m "feat(frontend): ..."`
   - OR single commit: `git commit -m "feat(fullstack): ..."`

## Pre-Commit Checklist

Before committing:
- [ ] Code follows project standards
- [ ] Tested and works (appropriate tests from testing rules)
- [ ] Build succeeds (if frontend changed)
- [ ] No console errors
- [ ] TODO.md/BUGS.md updated
- [ ] COMPLETED.md updated
- [ ] Commit message is clear and descriptive
- [ ] Only committing relevant files (check `git status`)

## Git Best Practices

### DO:
- ✅ Commit often (after each complete unit of work)
- ✅ Write clear commit messages
- ✅ Test before committing
- ✅ Build successfully before committing frontend
- ✅ Keep commits focused (one logical change)
- ✅ Use meaningful branch names (if using branches)

### DON'T:
- ❌ Commit broken code
- ❌ Commit untested code
- ❌ Use vague commit messages
- ❌ Commit build artifacts
- ❌ Commit sensitive data (passwords, keys)
- ❌ Make giant commits with many unrelated changes