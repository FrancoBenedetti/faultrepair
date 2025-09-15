import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import ClientRegistration from '../views/ClientRegistration.vue'
import ClientSignin from '../views/ClientSignin.vue'
import ServiceProviderRegistration from '../views/ServiceProviderRegistration.vue'
import ServiceProviderSignin from '../views/ServiceProviderSignin.vue'
import ServiceProviderDashboard from '../views/ServiceProviderDashboard.vue'
import ClientServiceProviderBrowser from '../views/ClientServiceProviderBrowser.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
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
    path: '/client-dashboard',
    name: 'ClientServiceProviderBrowser',
    component: ClientServiceProviderBrowser,
    meta: { requiresAuth: true, userType: 'client' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
