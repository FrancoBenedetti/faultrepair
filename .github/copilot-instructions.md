Purpose
Provide short, actionable instructions for AI coding agents working on the Fault Reporter repository. Focus on patterns, workflows, and files an agent should consult before editing code.

Quick context
- Full-stack LAMP app: PHP 8+ backend (no framework) + Vue 3 frontend (Vite).
- Backend is a collection of small REST endpoints in `backend/api/*.php` and shared utilities in `backend/includes/`.
- Frontend lives under `frontend/` and is a Vite + Vue 3 SPA that talks to the backend via relative paths (e.g. `/backend/api/auth.php`).

What to read first (order matters)
1. `README.md` (project overview and quick-start). It documents architecture and workflows.
2. `backend/README.md` and `frontend/README.md` for detailed API and UI flows.
3. `backend/config/*` to learn about database and site configuration expectations (these are copied from .example files at deploy-time).
4. `backend/includes/JWT.php` to understand the custom JWT format and token usage.
5. Example endpoints: `backend/api/auth.php`, `backend/api/client-jobs.php`, `backend/api/upload-job-image.php` — these show authentication, permission checks, and how inputs/outputs are structured.

Coding conventions and patterns
- No framework: endpoints are self-contained PHP scripts using `require_once '../config/database.php'` and `require_once '../includes/JWT.php'`.
- Authentication: JWT tokens are created with `JWT::encode()` and decoded with `JWT::decode()`. Tokens are accepted either in an Authorization header or as `token` query/form parameter for live-server compatibility. See `auth.php` and `client-jobs.php`.
- Database access: Uses PDO prepared statements via `$pdo` from `config/database.php`.
- Error handling: Endpoints return JSON with appropriate HTTP codes (400/401/403/404/405/500). Follow this pattern when adding new endpoints.
- File uploads: Stored under `uploads/job_images/` and inserted into `job_images` DB table. Max size ~10MB, MIME checks present in `upload-job-image.php`.
- Logging: Some endpoints set `error_log` to `$_SERVER['DOCUMENT_ROOT'].'/all-logs/*.log'` — preserving or using the same pattern keeps consistent debugging traces.

Developer workflows (how to run & test)
- Frontend dev: `cd frontend && npm install && npm run dev` (Vite default server at http://localhost:5173).
- Backend dev: Serve `backend/public/` as the document root. During local dev you can use PHP's built-in server from `backend/public`:
  php -S localhost:8000 index.php
  (This `index.php` routes requests into `../api/*` and serves `../uploads/*` during development.)
- DB setup: Run `schema.sql` or `database-update.sql` against a local MariaDB/MySQL. The code expects `config/database.php` to populate a `$pdo` variable.
- Environment/Secrets: JWT secret comes from env `JWT_SECRET` or falls back to `your-secret-key` in `backend/includes/JWT.php`. Database/site example files are expected to be copied to `backend/config/` during setup.

Safe editing checklist (for agents)
- Preserve the existing response JSON shapes and HTTP status codes for public endpoints unless the change includes a migration plan and tests.
- If you change authentication behavior, update `backend/includes/JWT.php` and every endpoint that reads `token` from header/query/form.
- When adding new SQL, use prepared statements and parameter binding via PDO. Avoid string interpolation.
- For file uploads, enforce the same MIME and size checks and write into `uploads/job_images/` with unique filenames.
- Keep logging consistent: use `error_log()` to the existing `all-logs/` files when debugging server-side issues.

Examples and idioms (copy-paste safe snippets)
- Read token from header or fallback to `token` parameter (use this exact approach for compatibility):
  $headers = getallheaders();
  if (isset($headers['Authorization']) && preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $m)) { $token = $m[1]; }
  $token = $token ?? $_POST['token'] ?? $_GET['token'] ?? null;

- Create JWT payload on login (see `auth.php`): include user id, role, entity_type, entity_id, iat, exp.

Files & places to reference in changes
- backend/api/*.php — API surface. Use these as canonical examples for request/response formats.
- backend/includes/JWT.php — auth core.
- backend/config/* — DB and site config templates.
- backend/public/index.php — development router for the backend (useful for quick manual testing).
- frontend/src/utils/api.js (or similar) — how the frontend constructs API calls and stores JWT in localStorage.
- schema.sql — DB schema reference when adding fields/tables.

When to ask the human
- Any change requiring secrets (JWT secret, SMTP) or production DB access.
- API contract changes that require frontend coordination (e.g. changing a response field name).
- Large refactors or introducing new services (e.g. move to a framework) — propose a migration plan.

If you modify APIs: include a minimal integration test
- Add a small curl example in the edited endpoint's file header comment showing the expected request and response.

End
Concise, targeted guidance only — ask me for clarification or to expand any section (deploy, CI, or detailed DB notes).