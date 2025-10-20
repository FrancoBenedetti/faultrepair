# Project Information

## Project: Snappy

### Architecture
- **Backend:** Vanilla PHP with MariaDB database
- **Frontend:** Vue 3 with Vite build system
- **Database Schema:** `snappy-dev.sql` in project root (authoritative source)
- **Build Script:** `snappy-build.sh` in project root
- **Deployment:** Build files copied to local virtual host with symlinks to PHP backend

### Critical Files - NEVER MODIFY WITHOUT PERMISSION
- `snappy-dev.sql` - Source of truth for database schema
- `snappy-build.sh` - Build and deployment script
- Symlink configurations

### Technology Stack
- **Backend:** PHP (vanilla, no framework)
- **Database:** MariaDB
- **Frontend:** Vue 3, Vite
- **Version Control:** Git

### Local Development
- Virtual host setup with symlinks to backend
- Build required for frontend changes to appear in virtual host
- Development: `npm run dev` (hot reload)
- Production testing: `./snappy-build.sh` (builds to virtual host)