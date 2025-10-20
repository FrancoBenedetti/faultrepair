# Bug Tracking System

## Bug Severity Levels

### 🔴 Critical (Drop Everything)
**Definition:**
- Site completely down or unusable
- Data loss occurring
- Security breach active
- Core business function broken (payments, auth)

**Examples:**
- "Users cannot login at all"
- "Database connection failing"
- "Payment processing broken"
- "SQL injection vulnerability exploited"

**Action:** Fix immediately, interrupt any TODO work

### 🟠 High (Fix Today/Tomorrow)
**Definition:**
- Major feature broken but site functional
- Affects many users
- Workaround exists but painful
- Significant data inconsistency

**Examples:**
- "File upload fails for files >5MB"
- "Search returns no results"
- "User profile page crashes"
- "Email notifications not sending"

**Action:** Complete current TODO task, then fix before next TODO

### 🟡 Medium (Fix This Week)
**Definition:**
- Affects some users
- Reasonable workaround available
- Not blocking critical paths
- Annoying but not breaking

**Examples:**
- "Date format inconsistent across pages"
- "Comments don't sort correctly"
- "Profile image doesn't update immediately"
- "Pagination displays wrong page count"

**Action:** Schedule in current sprint between TODO tasks

### 🟢 Low (Fix When Convenient)
**Definition:**
- Minor annoyance
- Rarely encountered
- Cosmetic issues
- Development-only issues

**Examples:**
- "Button alignment off by 2px"
- "Console warning in dev tools"
- "Tooltip positioning slightly off"
- "Unused CSS classes"

**Action:** Batch with similar bugs or fix during refactoring

## Bug Workflow

### When Bug is Discovered

1. **Add to BUGS.md immediately** with:
```markdown
   ### 🟠 Bug Title
   **Discovered:** YYYY-MM-DD
   **Area:** Backend/Frontend/Database
   **Impact:** Description of impact
   
   **Steps to Reproduce:**
   1. Step one
   2. Step two
   3. Expected vs actual result
   
   **Notes:**
   - Any additional context
   - Potential root cause if known
```

2. **Don't disrupt current work** unless Critical

3. **Assess and communicate** priority

### When Fixing a Bug

1. Move to "In Progress" in BUGS.md
2. Read description completely
3. Reproduce if possible
4. Identify root cause
5. Implement fix
6. Test thoroughly:
   - Original scenario
   - Edge cases
   - Related functionality
7. Update BUGS.md (move to Fixed with date)
8. Update COMPLETED.md with [BUG] tag
9. Commit: `fix(area): description`

### After Fixing - Prevention

Consider if bug reveals:
- Missing validation → Add task to TODO.md
- Poor error handling → Add task to TODO.md
- Brittle code → Add refactoring to TODO.md
- Missing tests → Add testing task to TODO.md

Example:
```
After fixing "Empty comments accepted" bug, add to TODO.md:
- [ ] Add input validation helper function
- [ ] Add validation to all form submissions
- [ ] Create validation documentation
```

## Bug Review Cadence

### Daily (Every Session Start)
- Check for Critical bugs (fix immediately)
- Check for High bugs (prioritize today)

### Weekly
- Review Medium bugs
- Prioritize which to fix this week
- Update severity if needed

### Monthly
- Review Low bugs
- Batch fix similar issues
- Close stale bugs (mark "Won't Fix" with reason)

## Integration with TODO.md Work

### Decision Matrix During Active Work:

| Bug Severity | Current TODO Status | Action |
|--------------|---------------------|--------|
| Critical | Any | Pause TODO, fix bug now |
| High | Just started task | Consider fixing bug first |
| High | Mid-task | Finish task, then fix bug |
| High | Almost done | Complete task, then fix bug |
| Medium/Low | Any | Note bug, continue TODO |

### When Asked "What's Next?"

1. Check BUGS.md for Critical → Fix immediately
2. Check BUGS.md for High → Suggest: "Fix bug or continue TODO?"
3. If no urgent bugs → Continue TODO.md priorities

Example:
```
Cline: "Completed CommentForm.vue from TODO.md.

Checking BUGS.md:
- 🔴 0 Critical
- 🟠 1 High: 'File upload fails for large files'
- 🟡 2 Medium
- 🟢 3 Low

Next TODO task: Integrate comments into BlogPost.vue

Recommendation: Fix High bug first (affects many users trying 
to upload). Should take ~1 hour. Then continue with comment 
integration.

Proceed with bug fix?"
```