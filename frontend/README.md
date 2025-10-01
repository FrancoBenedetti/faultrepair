# Fault Reporter Frontend

## Overview

The Fault Reporter frontend is a Vue.js 3 single-page application (SPA) that provides a user interface for clients and service providers to interact with the fault reporting system. It features separate registration and authentication flows for both user types.

## Architecture

The frontend follows Vue.js best practices with:
- **Component-based Architecture**: Modular Vue components for different features
- **Vue Router**: Client-side routing for navigation
- **Composition API**: Modern Vue 3 approach for component logic
- **Responsive Design**: Mobile-friendly interface with CSS styling
- **REST API Integration**: Fetch API for backend communication

## Technologies Used

- **Vue.js 3**: Progressive JavaScript framework
- **Vue Router 4**: Official router for Vue.js
- **Vite**: Fast build tool and development server
- **Axios**: HTTP client for API requests
- **TailwindCSS**: Utility-first CSS framework
- **PostCSS**: CSS processing tool
- **JavaScript (ES6+)**: Modern JavaScript features
- **CSS**: Component-scoped styling and global styles
- **HTML5**: Semantic markup
- **html5-qrcode**: QR code scanning library
- **jsQR**: Direct QR code detection library
- **Camera API**: Device camera access for QR scanning and photo capture
- **Material Symbols**: Icon library for UI elements

## Project Structure

```
frontend/
├── public/
│   └── index.html               # Main HTML template
├── src/
│   ├── views/                   # Page components
│   │   ├── Home.vue             # Landing page with navigation
│   │   ├── ClientDashboard.vue  # Client dashboard with job management and QR scanning
│   │   ├── ClientRegistration.vue # Client registration form
│   │   ├── ClientServiceProviderBrowser.vue # Browse approved providers
│   │   ├── ServiceProviderDashboard.vue # Service provider dashboard
│   │   ├── ServiceProviderRegistration.vue # Service provider registration
│   │   ├── ServiceProviderClientJobs.vue # View jobs for specific client
│   │   ├── ServiceProviderTechnicianJobs.vue # View jobs for specific technician
│   │   ├── TechnicianDashboard.vue # Technician dashboard
│   │   ├── VerifyEmail.vue       # Email verification page
│   │   ├── ResetPassword.vue     # Password reset page
│   │   ├── ForgotPassword.vue    # Forgot password page
│   │   └── Home.vue              # Landing page
│   ├── components/              # Reusable components
│   │   ├── ImageUpload.vue      # Image upload component
│   │   ├── ImageGallery.vue     # Image gallery display
│   │   └── QrScanner.vue        # QR code scanner component
│   ├── utils/
│   │   └── api.js               # API utility functions (using Axios)
│   ├── assets/
│   │   └── main.css             # Global styles
│   ├── router/
│   │   └── index.js             # Vue Router configuration
│   ├── App.vue                  # Root component
│   └── main.js                  # Application entry point
├── package.json                 # Dependencies and scripts
├── vite.config.js              # Vite configuration
└── index.html                   # Development HTML template
```

## Components

### Page Components

#### `Home.vue`
- **Purpose**: Landing page with navigation to registration/sign-in options
- **Features**:
  - Welcome message and system description
  - Two main sections: Clients and Service Providers
  - Prominent call-to-action buttons
  - Responsive card-based layout
- **Routes**: `/`

#### `ClientRegistration.vue`
- **Purpose**: Registration form for new client companies
- **Features**:
  - Form fields: Company Name, Address, Username, Email, Password
  - Client-side validation
  - API integration with backend registration endpoint
  - Success/error handling with user feedback
  - Navigation to sign-in page after successful registration
- **Routes**: `/client-registration`

#### `ClientSignin.vue`
- **Purpose**: Authentication form for existing clients
- **Features**:
  - Username and password fields
  - Integration with backend authentication API
  - JWT token storage in localStorage
  - Error handling for invalid credentials
  - Navigation links to registration
- **Routes**: `/client-signin`

#### `ServiceProviderRegistration.vue`
- **Purpose**: Registration form for new service provider companies
- **Features**:
  - Similar to client registration but for service providers
  - API integration with service provider registration endpoint
  - Company-focused form fields
- **Routes**: `/service-provider-registration`

#### `ServiceProviderSignin.vue`
- **Purpose**: Authentication form for existing service providers
- **Features**:
  - Similar to client sign-in but for service providers
  - Integration with same authentication API
- **Routes**: `/service-provider-signin`

#### `ServiceProviderDashboard.vue`
- **Purpose**: Main dashboard for service provider administrators and technicians
- **Features**:
  - **Admin View**: Services management, regions management, technician management, client overview
  - **Technician View**: Restricted view with only client jobs and profile access
  - Role-based UI restrictions using JWT token parsing
  - Technician cards with "View Jobs" functionality
  - Modal-based forms for adding/editing technicians
- **Routes**: `/service-provider-dashboard`

#### `ServiceProviderClientJobs.vue`
- **Purpose**: View and manage jobs for a specific client
- **Features**:
  - Job listing with status badges and filtering
  - Job details modal with status history
  - Status update functionality
  - Client information display
- **Routes**: `/service-provider/client/:clientId/jobs`

#### `ServiceProviderTechnicianJobs.vue`
- **Purpose**: View jobs assigned to a specific technician (admin view)
- **Features**:
  - Technician information header
  - Job listing with status tracking
  - Job details and status update modals
  - Statistics display (total, active, completed jobs)
- **Routes**: `/service-provider/technician/:technicianId/jobs`

#### `TechnicianDashboard.vue`
- **Purpose**: Dedicated dashboard for technicians
- **Features**:
  - Personal job statistics overview
  - Assigned jobs listing with status management
  - Job details modal with technician notes
  - Status update functionality
  - Clean, focused interface for field technicians
- **Routes**: `/technician-dashboard`

#### `ClientDashboard.vue`
- **Purpose**: Main dashboard for client users
- **Features**:
  - Job creation and management with QR code scanning
  - Service provider browsing
  - Job status tracking
  - Role-based access (Reporting Employee vs Budget Controller)
  - QR Code Integration: Scan QR codes to auto-fill fault reports
  - Camera access for both QR scanning and photo capture
- **Routes**: `/client-dashboard`

#### `ClientServiceProviderBrowser.vue`
- **Purpose**: Browse and view approved service providers
- **Features**:
  - List of approved service providers
  - Provider details and contact information
  - Service offerings display
- **Routes**: `/browse-providers`

### Core Components

#### `App.vue`
- **Purpose**: Root component that wraps the entire application
- **Features**:
  - Router view for displaying current page
  - Global layout and styling
  - Placeholder for future navigation components

#### `main.js`
- **Purpose**: Application entry point
- **Features**:
  - Vue application initialization
  - Router integration
  - Global configuration

## Routing

The application uses Vue Router with the following routes:

```javascript
const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  // Client routes
  {
    path: '/client-registration',
    name: 'ClientRegistration',
    component: ClientRegistration
  },
  {
    path: '/client-signin',
    name: 'ClientSignin',
    component: ClientSignin
  },
  {
    path: '/client-dashboard',
    name: 'ClientDashboard',
    component: ClientDashboard,
    meta: { requiresAuth: true, userType: 'client' }
  },
  {
    path: '/browse-providers',
    name: 'ClientServiceProviderBrowser',
    component: ClientServiceProviderBrowser,
    meta: { requiresAuth: true, userType: 'client' }
  },
  // Service Provider routes
  {
    path: '/service-provider-registration',
    name: 'ServiceProviderRegistration',
    component: ServiceProviderRegistration
  },
  {
    path: '/service-provider-signin',
    name: 'ServiceProviderSignin',
    component: ServiceProviderSignin
  },
  {
    path: '/service-provider-dashboard',
    name: 'ServiceProviderDashboard',
    component: ServiceProviderDashboard,
    meta: { requiresAuth: true, userType: 'service_provider' }
  },
  {
    path: '/service-provider/client/:clientId/jobs',
    name: 'ServiceProviderClientJobs',
    component: ServiceProviderClientJobs,
    meta: { requiresAuth: true, userType: 'service_provider' },
    props: true
  },
  {
    path: '/service-provider/technician/:technicianId/jobs',
    name: 'ServiceProviderTechnicianJobs',
    component: ServiceProviderTechnicianJobs,
    meta: { requiresAuth: true, userType: 'service_provider' },
    props: true
  },
  // Technician routes
  {
    path: '/technician-dashboard',
    name: 'TechnicianDashboard',
    component: TechnicianDashboard,
    meta: { requiresAuth: true, userType: 'service_provider' }
  }
]
```

### Route Guards

The application implements authentication and authorization guards:

- **Authentication Guard**: Redirects unauthenticated users to appropriate sign-in pages
- **Authorization Guard**: Ensures users can only access routes appropriate to their entity type
- **Role-Based Access**: Service provider routes check for technician vs admin permissions

## Technician Functionality

The application includes comprehensive technician management with role-based access control:

### User Roles
- **Service Provider Admin (Role 3)**: Full access to manage technicians, services, regions, and profiles
- **Technician (Role 4)**: Limited access to view assigned jobs and basic profile information

### Technician Management Features
- **Technician CRUD Operations**: Admins can create, read, update, and delete technicians
- **Job Assignment**: Admins can view jobs assigned to specific technicians
- **Role-Based UI**: Different dashboard views based on user role
- **Technician Dashboard**: Dedicated interface for technicians to manage their jobs

### Key Components
- **ServiceProviderDashboard.vue**: Main dashboard with conditional rendering based on user role
- **TechnicianDashboard.vue**: Dedicated technician interface
- **ServiceProviderTechnicianJobs.vue**: Admin view of technician jobs
- **Role-based restrictions**: UI elements hidden/shown based on JWT token role

### Security Features
- **JWT Role Validation**: Frontend parses JWT tokens to determine user permissions
- **Conditional Rendering**: `v-if="userRole === 3"` for admin-only features
- **Route Protection**: Authentication guards prevent unauthorized access
- **API Integration**: Backend validates role permissions for all operations

## User Flow

### Client User Journey
1. **Landing**: User visits home page
2. **Registration**: User clicks "Register as Client"
3. **Form Submission**: User fills registration form and submits
4. **Backend Processing**: Form data sent to `/backend/api/register-client.php`
5. **Success**: User redirected to client sign-in page
6. **Authentication**: User signs in with credentials
7. **Dashboard**: User would be redirected to dashboard (future feature)

### Service Provider User Journey
1. **Landing**: User visits home page
2. **Registration**: User clicks "Register as Service Provider"
3. **Form Submission**: User fills registration form and submits
4. **Backend Processing**: Form data sent to `/backend/api/register-service-provider.php`
5. **Success**: User redirected to service provider sign-in page
6. **Authentication**: User signs in with credentials
7. **Dashboard**: User would be redirected to dashboard (future feature)

## API Integration

### Authentication Flow
```javascript
// Example from ClientSignin.vue
async signin() {
  const response = await fetch('/backend/api/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });

  if (response.ok) {
    const { token } = await response.json();
    localStorage.setItem('token', token);
    // Redirect to dashboard
  }
}
```

### Registration Flow
```javascript
// Example from ClientRegistration.vue
async register() {
  const response = await fetch('/backend/api/register-client.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
  });

  if (response.ok) {
    alert('Registration successful!');
    this.$router.push('/client-signin');
  }
}
```

## Styling

- **Scoped CSS**: Each component has its own styles
- **Responsive Design**: Mobile-first approach
- **Consistent Theme**: Blue color scheme (#007bff)
- **Form Styling**: Clean, accessible form elements
- **Button States**: Hover effects and loading states

## State Management

Currently uses:
- **Local Component State**: Vue's `data()` for form state
- **Local Storage**: For JWT token persistence
- **Router State**: Vue Router for navigation state

## Development Setup

### Prerequisites
- Node.js 16+
- npm or yarn

### Installation
```bash
cd frontend
npm install
```

### Development Server
```bash
npm run dev
```
Starts Vite development server at `http://localhost:5173`

### Build for Production
```bash
npm run build
```
Creates optimized build in `dist/` directory

### Preview Production Build
```bash
npm run preview
```

## Configuration

### Vite Configuration (`vite.config.js`)
```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/backend': {
        target: 'http://localhost',
        changeOrigin: true
      }
    }
  }
})
```

## Browser Support

- Chrome 88+
- Firefox 85+
- Safari 14+
- Edge 88+

## Future Enhancements

- **Dashboard Components**: User dashboards for clients and service providers
- **Form Validation**: More sophisticated client-side validation
- **Loading States**: Better UX during API calls
- **Error Boundaries**: Global error handling
- **Internationalization**: Multi-language support
- **Progressive Web App**: Offline capabilities
- **Component Library**: Shared UI components
- **State Management**: Vuex or Pinia for complex state
- **Testing**: Unit and integration tests
- **TypeScript**: Type safety for larger codebase

## Deployment

### Development
- Run `npm run build` to create production build
- Serve `dist/` directory with any static server
- Ensure backend API is accessible at `/backend` path

### Production Considerations
- Configure proper CORS settings
- Set up HTTPS
- Implement proper error logging
- Configure environment variables for API endpoints
- Set up monitoring and analytics

## Troubleshooting

### Common Issues

1. **CORS Errors**: Ensure backend has proper CORS headers
2. **API Connection**: Verify backend server is running and accessible
3. **Routing Issues**: Check Vue Router configuration
4. **Build Errors**: Ensure all dependencies are installed

### Development Tips

- Use browser developer tools for debugging
- Check Network tab for API requests
- Use Vue DevTools for component inspection
- Enable source maps for better debugging

## Contributing

1. Follow Vue.js style guide
2. Use scoped CSS for component styles
3. Implement proper error handling
4. Add comments for complex logic
5. Test components in different browsers
6. Follow existing code patterns
