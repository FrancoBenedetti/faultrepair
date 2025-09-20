<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
          <span class="material-icon text-blue-600">login</span>
        </div>
        <h2 class="mt-6 text-3xl font-bold text-gray-900">Sign in to your account</h2>
        <p class="mt-2 text-sm text-gray-600">
          Access your maintenance dashboard
        </p>
      </div>

      <!-- Switch User Notice -->
      <div v-if="showSwitchUser" class="card">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-yellow-100 mb-4">
            <span class="material-icon text-yellow-600">warning</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Switch Account Type</h3>
          <p class="text-sm text-gray-600 mb-6">
            You are currently signed in as a service provider. To sign in as a client, you need to sign out first.
          </p>
          <button @click="signOut" class="btn-filled w-full flex items-center justify-center gap-2">
            <span class="material-icon-sm">logout</span>
            Sign Out & Continue
          </button>
        </div>
      </div>

      <!-- Sign In Form -->
      <div v-else class="card">
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
          <p class="text-sm text-gray-600">
            Don't have an account?
            <router-link to="/client-registration" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
              Register here
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ClientSignin',
  data() {
    return {
      loading: false,
      form: {
        username: '',
        password: ''
      },
      showSwitchUser: false
    }
  },
  mounted() {
    // Check if user is already authenticated
    const token = localStorage.getItem('token')
    if (token) {
      try {
        const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
        if (payload.entity_type === 'client') {
          // Already signed in as client, redirect to dashboard
          this.$router.push('/client-dashboard')
        } else {
          // Signed in as different user type, offer to sign out first
          this.showSwitchUser = true
        }
      } catch (error) {
        // Invalid token, remove it
        localStorage.removeItem('token')
      }
    }
  },
  methods: {
    signOut() {
      localStorage.removeItem('token')
      this.showSwitchUser = false
    },

    async signin() {
      this.loading = true
      try {
        const response = await fetch('/backend/api/auth.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        const data = await response.json();
        if (response.ok) {
          localStorage.setItem('token', data.token);
          this.$router.push('/client-dashboard');
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
