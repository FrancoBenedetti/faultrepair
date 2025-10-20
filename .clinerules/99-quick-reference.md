# Quick Reference

## File Purposes

| File | Purpose | Update When |
|------|---------|-------------|
| `TODO.md` | Planned features & work | Adding tasks, completing tasks |
| `BUGS.md` | Bug tracking & fixes | Finding bugs, fixing bugs |
| `COMPLETED.md` | Historical log | Completing any work |
| `snappy-dev.sql` | Database schema | Schema changes (WITH APPROVAL) |
| `snappy-build.sh` | Build script | Never (without permission) |
| `.clinerules/` | AI assistant rules | Never (or with user guidance) |

## Priority Hierarchy
```
1. 🔴 Critical Bugs → Fix NOW (drop everything)
2. 🟠 High Bugs → Fix today/tomorrow
3. 📋 TODO.md "In Progress" → Complete current task
4. 📋 TODO.md "Up Next" → Start next task
5. 🟡 Medium Bugs → Schedule this week
6. 🟢 Low Bugs → Batch fix later
```

## Essential Commands
```bash
# Build frontend (AFTER any Vue/JS/CSS changes)
./snappy-build.sh

# Git basics
git status                              # See what changed
git add path/to/file                    # Stage file
git add .                               # Stage all
git commit -m "type(scope): message"    # Commit
git log --oneline -5                    # Recent commits
git diff                                # See changes

# Check database schema
cat snappy-dev.sql | less
grep "CREATE TABLE" snappy-dev.sql
```

## Commit Message Templates
```bash
# Features
git commit -m "feat(backend): add user registration endpoint"
git commit -m "feat(frontend): create comment form component"
git commit -m "feat(fullstack): implement commenting system"

# Bug Fixes
git commit -m "fix(backend): prevent SQL injection in search"
git commit -m "fix(frontend): correct date formatting"
git commit -m "fix(database): add missing foreign key"

# Other
git commit -m "chore(docs): update TODO.md"
git commit -m "refactor(backend): consolidate DB connections"
git commit -m "style(frontend): fix button alignment"
```

## Session Workflow

### Every Session Start:
```
1. Read .clinerules files
2. Read TODO.md
3. Read BUGS.md
4. Report status (bugs + TODO progress)
5. Recommend priority
```

### Every Task:
```
1. Move to "In Progress" in TODO.md or BUGS.md
2. Check snappy-dev.sql if needed
3. Write code following standards
4. Test thoroughly
5. Run build if frontend changed
6. Update TODO.md/BUGS.md (move to Completed/Fixed)
7. Update COMPLETED.md with details
8. Commit with clear message
9. Suggest next task
```

## Testing Checklist

### Backend:
- [ ] Test API with Postman/curl
- [ ] Test success cases
- [ ] Test error cases
- [ ] Check database data
- [ ] Check PHP error logs

### Frontend:
- [ ] Run `./snappy-build.sh`
- [ ] Build succeeds
- [ ] Test in browser at virtual host
- [ ] No console errors (F12)
- [ ] No network errors (F12)
- [ ] Visual appearance correct

### Full-Stack:
- [ ] Backend tested independently
- [ ] Frontend tested independently
- [ ] Integration tested end-to-end

## Common Gotchas

| Issue | Solution |
|-------|----------|
| Column names wrong | Check `snappy-dev.sql` (snake_case) |
| SQL injection risk | Always use prepared statements |
| Build fails | Read error, check file/line, fix, rebuild |
| Changes don't appear | Run build, clear cache (Ctrl+Shift+R) |
| Foreign key error | Check parent record exists |
| NULL constraint error | Check NOT NULL columns in schema |
| Import error in Vue | Check path and component name |
| API 404 | Check endpoint URL and method |
| Console errors | F12 → Console → read error message |

## PHP Security (CRITICAL)
```php
// ✅ ALWAYS DO THIS
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// ❌ NEVER DO THIS (SQL injection!)
$query = "SELECT * FROM users WHERE id = $userId";
$result = $pdo->query($query);
```

## Database Column Naming
```
Database (snake_case)    →    Code (camelCase)
user_id                  →    userId
created_at               →    createdAt
post_id                  →    postId
comment_count            →    commentCount
```

Always check `snappy-dev.sql` for exact column names!

## When to Ask Permission

**ASK BEFORE:**
- Modifying `snappy-dev.sql`
- Modifying `snappy-build.sh`
- Adding dependencies (composer, npm)
- Changing database connection config
- Modifying symlinks
- Security-related changes

**CAN PROCEED:**
- Creating new endpoints (following patterns)
- Creating new components (following patterns)
- Fixing bugs
- Adding to TODO.md/BUGS.md
- Updating documentation

## Troubleshooting Quick Fixes

**Frontend issues:**
```bash
./snappy-build.sh
# Hard reload browser: Ctrl+Shift+R
```

**Backend issues:**
```bash
# Check PHP error logs
# Test query in database client
# Test API with Postman
```

**Build issues:**
```bash
# Read error message
# Fix file at line number shown
# Rebuild
```

**Database issues:**
```bash
# Check snappy-dev.sql for schema
# Use prepared statements
# Check foreign keys exist
```

## Bug Severity Quick Guide

| Emoji | Level | When | Action |
|-------|-------|------|--------|
| 🔴 | Critical | Site down, data loss, security | Fix NOW |
| 🟠 | High | Major feature broken | Fix today |
| 🟡 | Medium | Some users affected | Fix this week |
| 🟢 | Low | Minor, cosmetic | Batch fix |

## COMPLETED.md Templates

### Feature:
```markdown
### ✅ [FEATURE] Task Name
**Source:** TODO.md
**Commit:** abc123
**Type:** Backend/Frontend/Full-stack
**Implementation:** [what was built]
**Testing:** [what was tested]
```

### Bug:
```markdown
### ✅ [BUG] 🟠 Bug Description
**Source:** BUGS.md
**Commit:** def456
**Severity:** High
**Issue:** [what was broken]
**Solution:** [how it was fixed]
**Testing:** [how verified]
```

## Remember

1. ✅ Read TODO.md and BUGS.md every session
2. ✅ Critical bugs interrupt everything
3. ✅ Use prepared statements (PHP security)
4. ✅ Check snappy-dev.sql for schema
5. ✅ Run build after frontend changes
6. ✅ Test before marking complete
7. ✅ Update tracking files
8. ✅ Commit with clear messages
9. ✅ Ask before modifying critical files
10. ✅ Suggest next task when done

---

*This is your quick reference. For details, see other .clinerules files.*