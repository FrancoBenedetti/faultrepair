import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import InvitationLanding from '../views/InvitationLanding.vue'
import UnifiedRegistration from '../views/UnifiedRegistration.vue'
import ClientRegistration from '../views/ClientRegistration.vue'
import ClientDashboard from '../views/ClientDashboard.vue'
import ServiceProviderRegistration from '../views/ServiceProviderRegistration.vue'
import ServiceProviderDashboard from '../views/ServiceProviderDashboard.vue'
import ServiceProviderClientJobs from '../views/ServiceProviderClientJobs.vue'
import ServiceProviderTechnicianJobs from '../views/ServiceProviderTechnicianJobs.vue'
import ClientServiceProviderBrowser from '../views/ClientServiceProviderBrowser.vue'
import CreateInvitation from '../views/CreateInvitation.vue'
import ForgotPassword from '../views/ForgotPassword.vue'
import ResetPassword from '../views/ResetPassword.vue'
import Terms from '../views/Terms.vue'
import Subscription from '../views/Subscription.vue'
import About from '../views/About.vue'
import { handleTokenExpiration } from '../utils/api.js'
import { apiFetch } from '../utils/api.js'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/invitation',
    name: 'InvitationLanding',
    component: InvitationLanding
  },
  {
    path: '/register',
    name: 'UnifiedRegistration',
    component: UnifiedRegistration
  },
  {
    path: '/terms',
    name: 'Terms',
    component: Terms
  },
  {
    path: '/subscription',
    name: 'Subscription',
    component: Subscription
  },
  {
    path: '/about',
    name: 'About',
    component: About
  },
  {
    path: '/client-registration',
    name: 'ClientRegistration',
    component: ClientRegistration,
    redirect: '/register' // Redirect old routes to new unified registration
  },
  {
    path: '/client-dashboard',
    name: 'ClientDashboard',
    component: ClientDashboard,
    meta: { requiresAuth: true, userType: 'client' }
  },
  {
    path: '/service-provider-registration',
    name: 'ServiceProviderRegistration',
    component: ServiceProviderRegistration,
    redirect: '/register' // Redirect old routes to new unified registration
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
  {
    path: '/browse-providers',
    name: 'ClientServiceProviderBrowser',
    component: ClientServiceProviderBrowser,
    meta: { requiresAuth: true, userType: 'client' }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPassword
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: ResetPassword
  },
  {
    path: '/verify-email',
    name: 'VerifyEmail',
    component: () => import('../views/VerifyEmail.vue')
  },
  {
    path: '/create-invitation',
    name: 'CreateInvitation',
    component: CreateInvitation,
    meta: { requiresAuth: true }
  },
  {
    path: '/register-client',
    name: 'ClientRegistrationInvited',
    component: ClientRegistration
  },
  {
    path: '/service-provider-registration',
    name: 'ServiceProviderRegistrationInvited',
    component: ServiceProviderRegistration
  },
  {
    path: '/jobs/:jobId/quotation/:quoteId',
    name: 'QuotationDetails',
    component: () => import('../views/QuotationDetails.vue'),
    meta: { requiresAuth: true },
    props: (route) => ({
      jobId: parseInt(route.params.jobId),
      quoteId: parseInt(route.params.quoteId),
      from: route.query.from || 'client',
      scrollPosition: route.query.scroll || 0
    }),
    beforeEnter: async (to, from, next) => {
      // Check if user is authenticated
      const token = localStorage.getItem('token')
      if (!token) {
        next('/')
        return
      }

      try {
        const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
        const jobId = to.params.jobId
        const quoteId = to.params.quoteId

        // Fetch quote to verify access
        const apiFetch = (await import('../utils/api.js')).apiFetch

        const response = await apiFetch(`/backend/api/job-quotations.php?quote_id=${quoteId}`)

        if (!response.ok) {
          // Quote not found or no access
          if (payload.entity_type === 'client') {
            next('/client-dashboard')
          } else if (payload.entity_type === 'service_provider') {
            next('/service-provider-dashboard')
          } else {
            next('/')
          }
          return
        }

        // If all checks pass, allow access
        next()
      } catch (error) {
        console.error('Error in QuotationDetails route guard:', error)
        // On error, redirect to appropriate dashboard
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          if (payload.entity_type === 'client') {
            next('/client-dashboard')
          } else if (payload.entity_type === 'service_provider') {
            next('/service-provider-dashboard')
          } else {
            next('/')
          }
        } catch {
          next('/')
        }
      }
    }
  },
  {
    path: '/client/create-job',
    name: 'CreateJob',
    component: () => import('../views/CreateJob.vue'),
    meta: { requiresAuth: true, userType: 'client' },
    props: (route) => ({
      from: 'client',
      scrollPosition: route.query.scroll || 0
    })
  },
  {
    path: '/service-provider/create-job',
    name: 'CreateJobSP',
    component: () => import('../views/CreateJob.vue'),
    meta: { requiresAuth: true, userType: 'service_provider' },
    props: (route) => ({
      from: 'service-provider',
      scrollPosition: route.query.scroll || 0
    })
  },
  {
    path: '/client/edit-profile',
    name: 'EditProfile',
    component: () => import('../views/EditProfile.vue'),
    meta: { requiresAuth: true, userType: 'client' },
    props: (route) => ({
      userType: 'client',
      scrollPosition: route.query.scroll || 0
    })
  },
  {
    path: '/service-provider/edit-profile',
    name: 'EditProfileSP',
    component: () => import('../views/EditProfile.vue'),
    meta: { requiresAuth: true, userType: 'service_provider' },
    props: (route) => ({
      userType: 'service_provider',
      scrollPosition: route.query.scroll || 0
    })
  },
  {
    path: '/jobs/:id/edit',
    name: 'EditJob',
    component: () => import('../views/EditJob.vue'),
    meta: { requiresAuth: true },
    props: (route) => ({
      jobId: parseInt(route.params.id),
      from: route.query.from || 'client',
      scrollPosition: route.query.scroll || 0
    }),
    beforeEnter: async (to, from, next) => {
      // Check if user is authenticated
      const token = localStorage.getItem('token')
      if (!token) {
        next('/')
        return
      }

      try {
        const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
        const jobId = to.params.id

        // Fetch basic job information to check provider type
        const response = await apiFetch(`/backend/api/client-jobs.php?job_id=${jobId}`)

        if (!response.ok) {
          // Job not found or no access
          if (payload.entity_type === 'client') {
            next('/client-dashboard')
          } else if (payload.entity_type === 'service_provider') {
            next('/service-provider-dashboard')
          } else {
            next('/')
          }
          return
        }

        const data = await response.json()
        if (!data.job) {
          // No job data returned
          if (payload.entity_type === 'client') {
            next('/client-dashboard')
          } else if (payload.entity_type === 'service_provider') {
            next('/service-provider-dashboard')
          } else {
            next('/')
          }
          return
        }

        // Additional check for service provider users: ensure this is not an XS job
        if (payload.entity_type === 'service_provider') {
          // Try to access the service provider job API with a query parameter
          // If it fails (which it will for XS jobs due to our filtering), redirect to dashboard
          const spJobCheck = await apiFetch(`/backend/api/service-provider-jobs.php?archive_status=active&job_id=${jobId}`)

          if (!spJobCheck.ok) {
            // Service provider cannot access this job - likely XS job
            // Redirect to service provider dashboard
            next('/service-provider-dashboard')
            return
          }
        }

        // If all checks pass, allow access
        next()
      } catch (error) {
        console.error('Error in EditJob route guard:', error)
        // On error, redirect to appropriate dashboard
        try {
          const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          if (payload.entity_type === 'client') {
            next('/client-dashboard')
          } else if (payload.entity_type === 'service_provider') {
            next('/service-provider-dashboard')
          } else {
            next('/')
          }
        } catch {
          next('/')
        }
      }
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Authentication guard
router.beforeEach((to, from, next) => {
  // Check for expired tokens first
  if (handleTokenExpiration()) {
    next('/')
    return
  }

  const token = localStorage.getItem('token')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const userType = to.matched.find(record => record.meta.userType)?.meta.userType

  if (requiresAuth && !token) {
    // Redirect to home page for signin
    next('/')
    return
  }

  if (requiresAuth && token && userType) {
    // Check if authenticated user has correct entity type for this route
    try {
      const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
      if (payload.entity_type !== userType) {
        // User doesn't have access to this route type, redirect to their correct dashboard
        if (payload.entity_type === 'client') {
          next('/client-dashboard')
        } else if (payload.entity_type === 'service_provider') {
          next('/service-provider-dashboard')
        } else {
          localStorage.removeItem('token')
          next('/')
        }
        return
      }
    } catch (error) {
      // Invalid token, remove it
      localStorage.removeItem('token')
      next('/')
      return
    }
  }

  // Allow authenticated users to access signin pages (they can choose to sign in as different user type)
  // The signin components themselves will handle redirecting after successful authentication

  next()
})

export default router
