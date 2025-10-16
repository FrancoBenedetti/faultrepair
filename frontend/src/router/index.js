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
    path: '/register-service_provider',
    name: 'ServiceProviderRegistrationInvited',
    component: ServiceProviderRegistration
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
