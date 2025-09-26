<template>
  <div class="home max-w-2xl mx-auto px-6 py-12 text-center">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ siteName }}</h1>
    <p class="text-lg text-gray-700 mb-12">
      Welcome to the {{ siteName }}. Login or register below:
    </p>

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
            <label for="username" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">person</span>
              Username
            </label>
            <input
              type="text"
              id="username"
              v-model="form.username"
              required
              class="form-input"
              placeholder="Enter your username"
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
          <div class="flex flex-col sm:flex-row justify-center gap-4">
            <router-link to="/client-registration" class="bg-blue-600 text-white font-medium px-6 py-2.5 rounded-full shadow-lg hover:bg-blue-700 hover:shadow-xl active:bg-blue-800 active:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 ease-out">
              Register as Client
            </router-link>
            <router-link to="/service-provider-registration" class="bg-transparent text-blue-600 border-2 border-blue-600 font-medium px-6 py-2.5 rounded-full hover:bg-blue-50 hover:shadow-lg active:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 ease-out">
              Register as Service Provider
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'Home',
  data() {
    return {
      loading: false,
      form: {
        username: '',
        password: ''
      }
    }
  },
  computed: {
    siteName() {
      return import.meta.env.VITE_SITE_NAME || 'Fault Reporter'
    }
  },
  mounted() {
    // Set dynamic page title
    document.title = this.siteName

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
        if (response.ok) {
          localStorage.setItem('token', data.token);
          const payload = JSON.parse(atob(data.token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
          if (payload.entity_type === 'client') {
            this.$router.push('/client-dashboard');
          } else if (payload.entity_type === 'service_provider') {
            this.$router.push('/service-provider-dashboard');
          }
        } else {
          alert(data.error);
        }
      } catch (error) {
        alert('Sign in failed');
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
