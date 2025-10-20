# Work Management System

## File Structure and Purpose

### TODO.md - Planned Work (Flow-Based)
- New features and enhancements
- Refactoring and improvements
- Technical debt cleanup
- Planned optimizations
- Anything that's proactive, not reactive

### BUGS.md - Reactive Work (Interrupt-Driven)
- Bug fixes and issues
- Things that are broken
- User-reported problems
- Security vulnerabilities
- Performance degradations
- Anything that's reactive to problems

### COMPLETED.md - Historical Log
- All completed work (features + bugs)
- Implementation details and decisions
- Testing notes
- Serves as project history

## Decision Framework

When new work appears, ask:

**Is it broken or wrong?**
- YES → Add to BUGS.md, assess severity
- NO → Continue below

**Is it new functionality or improvement?**
- YES → Add to TODO.md in appropriate phase/section

**Is it maintenance or cleanup?**
- Add to TODO.md under "Technical Debt" or "Maintenance"

## Session Startup Protocol

### Every Session Must Start With:

1. Read all files in `.clinerules/` folder
2. Read `TODO.md` - understand planned work and current phase
3. Read `BUGS.md` - check for urgent issues
4. Assess priorities using this hierarchy:
   - 🔴 Critical bugs → Fix immediately
   - 🟠 High bugs → Suggest fixing before TODO work  
   - 🟡 Medium bugs → Note them, continue TODO work
   - 🟢 Low bugs → Note them, continue TODO work
   - 📋 TODO.md tasks → Planned feature work

5. Report status:
```
   Status Check:
   - BUGS: X Critical, Y High, Z Medium, W Low
   - TODO: Currently in [phase/section], [task] in progress
   - Recommendation: [what to work on next and why]
```

## Priority Decision Tree
```
Start Session
    ↓
Check BUGS.md
    ↓
🔴 Critical bugs? → YES → Fix immediately (drop all TODO work)
    ↓ NO
🟠 High bugs? → YES → Ask: "Fix now or after current TODO task?"
    ↓ NO or DEFERRED
Check TODO.md
    ↓
Task "In Progress"? → YES → Continue that task
    ↓ NO
Next task in "Up Next" → Start that task
```