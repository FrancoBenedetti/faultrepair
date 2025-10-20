# Development Workflow

## Before Starting ANY Task

### For TODO.md Tasks:
1. Read TODO.md to find the task
2. Check `snappy-dev.sql` if task involves database
3. Identify scope:
   - Backend only (PHP/MariaDB)
   - Frontend only (Vue/Vite)
   - Full-stack (both)
   - Database schema (requires approval)
4. Move task from "Up Next" to "In Progress 🚧" in TODO.md
5. Announce: "Starting: [task] - [Backend/Frontend/Full-stack]"

### For BUGS.md Tasks:
1. Read bug description completely
2. Understand severity and validate it's appropriate
3. Check related code to understand context
4. Move bug to "In Progress 🚧" section in BUGS.md  
5. Announce: "Fixing: [bug] - [severity level]"

## During Development

### General Principles
- Write clear, self-documenting code
- Add comments for complex logic only
- Use meaningful variable names
- Follow DRY (Don't Repeat Yourself)
- Follow existing patterns in the codebase
- Test as you go, don't wait until the end

### When Issues Arise During Work

**Bug Discovered:**
1. Assess severity immediately
2. If Critical: Stop, add to BUGS.md, fix now
3. If High: Add to BUGS.md, suggest fixing before continuing
4. If Medium/Low: Add to BUGS.md, continue current task

**New Work Identified:**
1. Add to TODO.md in appropriate section
2. Don't start it unless explicitly asked
3. Note it in your completion summary

**Technical Debt Found:**
1. Add to TODO.md "Technical Debt" section
2. Continue with current task unless it blocks you

**Blocker Encountered:**
1. Move current task to "Blocked 🔒" in appropriate file
2. Document what's blocking it
3. Ask for guidance or suggest alternative task

## After Completing ANY Task

### 1. Update Tracking Files

**For TODO.md tasks:**
- Move from "In Progress" to "Completed ✅"
- Format: `- [x] Task name (YYYY-MM-DD)`
- Add any notes or context discovered

**For BUGS.md tasks:**
- Move from "In Progress" to "Fixed ✅"  
- Add fix date and commit hash
- Note root cause and solution

### 2. Update COMPLETED.md

**For Features:**
```markdown
### ✅ [FEATURE] Task Name
**Source:** TODO.md
**Commit:** [hash]
**Type:** Backend/Frontend/Full-stack

**Implementation:**
- What was built
- How it was implemented
- Key decisions

**Files Changed:**
- List of modified files

**Testing:**
- What was tested
- Results

**Notes:**
- Gotchas, performance, security considerations
```

**For Bugs:**
```markdown
### ✅ [BUG] 🟠 Bug Description  
**Source:** BUGS.md
**Commit:** [hash]
**Severity:** Critical/High/Medium/Low

**Issue:** What was broken
**Root Cause:** Why it broke
**Solution:** How it was fixed
**Testing:** How verified
**Prevention:** Tasks added to prevent recurrence
```

### 3. Additional Steps

- If frontend work: Note that build should be run
- If schema change: Flag prominently in COMPLETED.md
- Commit your work (see git workflow rules)
- Suggest next task using priority hierarchy

### 4. Next Task Suggestion

Check in order:
1. BUGS.md for Critical bugs
2. BUGS.md for High bugs (ask whether to fix or continue TODO)
3. TODO.md for next "Up Next" task
4. Format: "Next: [task] from [source]. Should I proceed?"