<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
          <span class="material-icon text-blue-600">business</span>
        </div>
        <h2 class="mt-6 text-3xl font-bold text-gray-900">Create your account</h2>
        <p class="mt-2 text-sm text-gray-600">
          Join our platform to manage your maintenance requests
        </p>
      </div>

      <div class="card">
        <form @submit.prevent="register" class="space-y-6">
          <div>
            <label for="clientName" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">business</span>
              Company Name
            </label>
            <input
              type="text"
              id="clientName"
              v-model="form.clientName"
              required
              class="form-input"
              placeholder="Enter your company name"
            >
          </div>

          <div>
            <label for="address" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">location_on</span>
              Address
            </label>
            <textarea
              id="address"
              v-model="form.address"
              required
              rows="3"
              class="form-input resize-none"
              placeholder="Enter your company address"
            ></textarea>
          </div>

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
              placeholder="Choose a username"
            >
          </div>

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
              placeholder="Create a secure password"
            >
          </div>

          <div>
            <button
              type="submit"
              class="btn-filled w-full flex items-center justify-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">person_add</span>
              {{ loading ? 'Creating Account...' : 'Create Account' }}
            </button>
          </div>
        </form>

        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Already have an account?
            <router-link to="/client-signin" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
              Sign in here
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ClientRegistration',
  data() {
    return {
      loading: false,
      form: {
        clientName: '',
        address: '',
        username: '',
        email: '',
        password: ''
      }
    }
  },
  methods: {
    async register() {
      this.loading = true
      try {
        const response = await fetch('/backend/api/register-client.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        const data = await response.json();
        if (response.ok) {
          alert('Registration successful! You can now sign in.');
          this.$router.push('/client-signin');
        } else {
          alert(data.error);
        }
      } catch (error) {
        alert('Registration failed. Please try again.');
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
