<template>
  <div class="home">
    <!-- Trial Deployment Warning Banner - Only visible on try.snappyapp.co.za -->
    <div v-if="isTrialDeployment" class="bg-amber-50 border-b border-amber-200 px-6 py-4">
      <div class="max-w-4xl mx-auto">
        <div class="flex items-start gap-3">
          <span class="material-icon text-amber-600 mt-1">warning</span>
          <div class="flex-1 text-sm text-amber-900">
            <p class="font-semibold mb-2">Trial Environment Notice</p>
            <p class="mb-2">
              This is a trial environment for prospective users to explore Snappy. You can subscribe on this page to test the platform as asset owners, clients, or service providers. Please note that this is a demonstration site, and the database is reset periodically, resulting in the loss of all data.
            </p>
            <p class="font-medium">
              For production use, please subscribe at <a href="https://snappyapp.co.za" class="text-amber-800 underline hover:text-amber-900" target="_blank">https://snappyapp.co.za</a>.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Hero Section -->
    <div class="hero bg-gradient-to-br from-blue-50 to-indigo-100 py-16 px-6">
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-6">{{ siteName }}</h1>
        <p class="text-xl text-gray-700 mb-12 max-w-2xl mx-auto">
          Simplify equipment maintenance and repair workflows with QR code-enabled reporting and instant service request delivery.
        </p>

        <!-- Two Column Benefits -->
        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto mb-12">
          <!-- For Asset Owners (Clients) -->
          <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mx-auto mb-4">
              <span class="material-icon text-blue-600">business</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">For Asset Owners</h3>
            <p class="text-gray-700 mb-4">
              For owners of assets, equipment and whatever that is typically repaired and maintained by your contracted service providers - we provide a way to simplify and speed up how you get the service you want - in a managed way, just by using a cell phone app.
            </p>
            <ul class="text-left text-sm text-gray-600 space-y-1">
              <li>• Scan QR codes with your phone camera</li>
              <li>• Take photos of faults and send for approval</li>
              <li>• Get instant confirmation from service providers</li>
              <li>• Eliminate paperwork and communication delays</li>
            </ul>
          </div>

          <!-- For Service Providers -->
          <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mx-auto mb-4">
              <span class="material-icon text-green-600">engineering</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">For Service Providers</h3>
            <p class="text-gray-700 mb-4">
              For the repairers and maintainers, the providers of services, we provide a way for you to instantly get the service request notices or tickets so that you can react quickly and get the job confirmed as complete right away.
            </p>
            <ul class="text-left text-sm text-gray-600 space-y-1">
              <li>• Receive instant service request notifications</li>
              <li>• Access detailed fault reports with photos</li>
              <li>• Confirm and complete jobs efficiently</li>
              <li>• Streamline your service operations</li>
            </ul>
          </div>
        </div>

        <!-- Beta Banner -->
        <div class="bg-green-100 border border-green-300 rounded-lg p-4 max-w-2xl mx-auto mb-8">
          <div class="flex items-center justify-center gap-2 text-green-800">
            <span class="material-icon-sm">info</span>
            <span class="font-medium">Beta Service - Currently Free</span>
          </div>
          <p class="text-sm text-green-700 mt-1">
            We're currently in beta testing. Use is completely free during this period.
            <router-link to="/subscription" class="text-green-800 hover:text-green-900 underline ml-1">
              Learn about future subscription options
            </router-link>
          </p>
        </div>

        <!-- Trial Experience Banner - Visible on production, test, and local deployments -->
        <div v-if="isProductionDeployment" class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-2xl mx-auto mb-8">
          <div class="flex items-center justify-center gap-2 text-blue-800 mb-2">
            <span class="material-icon-sm">explore</span>
            <span class="font-medium">Experience Before You Commit</span>
          </div>
          <p class="text-sm text-blue-700">
            Curious about our platform but not ready to subscribe? Experience Snappy firsthand by visiting our
            <a href="https://try.snappyapp.co.za" class="text-blue-800 underline hover:text-blue-900 font-medium" target="_blank">trial environment</a>.
            Sign up there to explore features as an asset owner, client, or service provider. When you're ready for production use, return here to subscribe.
          </p>
        </div>
      </div>
    </div>

    <!-- Login Section -->
    <div class="max-w-2xl mx-auto px-6 py-12">
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Get Started</h2>
        <p class="text-lg text-gray-700 mt-2">Sign in to your account or create a new one</p>
      </div>

      <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-0 flex items-center justify-center gap-3">
            <span class="material-icon text-blue-600">login</span>
            Sign In
          </h2>
        </div>
        <div class="px-6 py-4">
          <form @submit.prevent="signin" class="space-y-6">
            <div>
              <label for="email" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">email</span>
                Email Address
              </label>
              <input
                type="email"
                id="email"
                v-model="form.email"
                required
                class="form-input"
                placeholder="Enter your email address"
              >
            </div>

            <div>
              <label for="password" class="form-label flex items-center gap-2">
                <span class="material-icon-sm text-gray-500">lock</span>
                Password
              </label>
              <input
                type="password"
                id="password"
                v-model="form.password"
                required
                class="form-input"
                placeholder="Enter your password"
              >
              <div class="text-right mt-2">
                <router-link to="/forgot-password" class="text-sm text-blue-600 hover:text-blue-500 transition-colors">
                  Forgot your password?
                </router-link>
              </div>
            </div>

            <div>
              <button
                type="submit"
                class="btn-filled w-full flex items-center justify-center gap-2"
                :disabled="loading"
              >
                <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
                <span v-else class="material-icon-sm">login</span>
                {{ loading ? 'Signing In...' : 'Sign In' }}
              </button>
            </div>
          </form>

          <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 mb-4">
              Don't have an account?
            </p>
            <div class="flex justify-center">
              <router-link to="/register" class="btn-filled flex items-center justify-center gap-2">
                <span class="material-icon-sm">person_add</span>
                Create Free Account
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch, handleTokenExpiration } from '@/utils/api.js'

export default {
  name: 'Home',
  data() {
    return {
      loading: false,
      form: {
        email: '',
        password: ''
      }
    }
  },
  computed: {
    siteName() {
      return import.meta.env.VITE_SITE_NAME || 'Fault Reporter'
    },
    isTrialDeployment() {
      return window.location.hostname === 'try.snappyapp.co.za'
    },
    isProductionDeployment() {
      const hostname = window.location.hostname
      return hostname === 'snappyapp.co.za' ||
             hostname === 'test.snappyapp.co.za' ||
             hostname === 'snappy.local'
    }
  },
  mounted() {
    // Set dynamic page title
    document.title = this.siteName

    // Handle expired token on login page
    handleTokenExpiration()

    // Check if user is already authenticated
    const token = localStorage.getItem('token')
    if (token) {
      try {
        const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
        if (payload.entity_type === 'client') {
          // Already signed in as client, redirect to dashboard
          this.$router.push('/client-dashboard')
        } else if (payload.entity_type === 'service_provider') {
          // Already signed in as service provider, redirect to dashboard
          this.$router.push('/service-provider-dashboard')
        }
      } catch (error) {
        // Invalid token, remove it
        localStorage.removeItem('token')
      }
    }
  },
  methods: {
    async signin() {
      this.loading = true
      try {
        const response = await apiFetch('/backend/api/auth.php', {
          method: 'POST',
          body: JSON.stringify(this.form)
        });
        const data = await response.json();

        if (response.ok && data.token) {
          localStorage.setItem('token', data.token);
          // Store user details for display in other components
          if (data.user) {
            localStorage.setItem('user', JSON.stringify(data.user));
          }

          // Check for QR redirect data after successful login
          const qrRedirectData = localStorage.getItem('qrRedirectData');
          if (qrRedirectData) {
            localStorage.removeItem('qrRedirectData'); // Clear it after use
            this.$router.push(`/client/create-job?${qrRedirectData}`);
            return; // Stop further redirection
          }

          const payload = JSON.parse(atob(data.token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
          if (payload.entity_type === 'client') {
            this.$router.push('/client-dashboard');
          } else if (payload.entity_type === 'service_provider') {
            this.$router.push('/service-provider-dashboard');
          } else {
            alert('Login successful but unable to determine user type. Please contact support.');
          }
        } else {
          // Provide user-friendly error message for auth failures
          let errorMessage = 'Sign in failed';
          if (data.error) {
            if (data.error === 'Invalid credentials') {
              errorMessage = 'Invalid email or password. Please check your credentials and try again.';
            } else if (data.error.includes('verify your email')) {
              errorMessage = data.error;
            } else if (data.error.includes('inactive')) {
              errorMessage = data.error;
            } else {
              errorMessage = data.error;
            }
          }
          alert(errorMessage);
        }
      } catch (error) {
        alert('Network error. Please check your connection and try again.');
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
