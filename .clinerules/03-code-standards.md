# Code Standards

## PHP Backend Standards

### Security - CRITICAL
**Always use prepared statements:**
```php
// ✅ CORRECT - Always do this
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// ❌ WRONG - NEVER do this (SQL injection risk)
$query = "SELECT * FROM users WHERE id = $userId";
```

### Database Access
- Check `snappy-dev.sql` for exact table/column names
- Column names use underscores: `user_id`, `created_at`, `post_id`
- Always use prepared statements (prevents SQL injection)
- Handle database errors with try-catch
- Test queries in database client before using in code

### Error Handling
```php
try {
    // database operations
} catch (PDOException $e) {
    error_log($e->getMessage());
    // Return user-friendly error
}
```

### API Responses
Follow existing pattern in project:
```php
// Success
echo json_encode([
    'success' => true,
    'data' => $result,
    'message' => 'Optional message'
]);

// Error  
echo json_encode([
    'success' => false,
    'error' => 'Error details',
    'message' => 'User-friendly message'
]);
```

### General PHP Rules
- Never hardcode credentials (use config)
- Set appropriate headers (`Content-Type: application/json`)
- Validate all input
- Use `isset()` or `??` for checking variables
- Follow existing auth/session patterns

## Vue Frontend Standards

### Component Structure
- Follow existing patterns (Composition vs Options API)
- Keep components focused and reusable
- Use meaningful component names
- Props should be typed and validated

### Naming Conventions
- Components: PascalCase (UserProfile.vue)
- Props: camelCase
- Events: kebab-case (@user-updated)
- Variables: camelCase

### State Management
- Follow existing pattern (Vuex/Pinia/composables)
- Don't duplicate state
- Keep state minimal

### Environment Variables
- Must start with `VITE_`
- Access via `import.meta.env.VITE_VARIABLE_NAME`
- Don't commit sensitive values

### Imports
- Use absolute imports when configured
- Check existing import patterns
- Import only what you need

## Database Standards

### Schema Changes - REQUIRES APPROVAL
**NEVER modify schema without explicit permission**

Process:
1. STOP and explain what changes are needed
2. Explain why changes are necessary
3. Wait for approval
4. Make changes to actual database
5. Update `snappy-dev.sql` after approval
6. Test thoroughly
7. Document in COMPLETED.md

### Querying Database
**Always reference `snappy-dev.sql` for:**
- Table names (case-sensitive on Linux)
- Column names (usually snake_case)
- Data types
- Constraints (NOT NULL, UNIQUE, etc.)
- Foreign key relationships
- Indexes

### Common Database Patterns
- Primary keys: Usually `id` (INT AUTO_INCREMENT)
- Timestamps: `created_at`, `updated_at` (DATETIME)
- Foreign keys: `user_id`, `post_id` (reference parent table)
- Soft deletes: `deleted_at` (DATETIME, NULL)
- Date format: 'YYYY-MM-DD HH:MM:SS'

## File Organization

### Backend
- Follow existing directory structure
- Group related functionality
- Keep files focused (single responsibility)

### Frontend
- Components: `src/components/`
- Views/Pages: `src/views/`
- Utilities: `src/utils/`
- Assets: `src/assets/`
- Types: `src/types/` (if using TypeScript)

### General
- Keep concerns separated (frontend vs backend)
- Don't mix responsibilities
- Use clear, descriptive file names