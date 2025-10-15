<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-md mx-auto px-6 py-4">
        <div class="text-center">
          <h1 class="text-2xl font-bold text-gray-900">Reset your password</h1>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div class="max-w-md mx-auto px-6 py-12">
      <div class="text-center mb-8">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
          <span class="material-icon text-blue-600">lock_reset</span>
        </div>
        <p class="mt-2 text-sm text-gray-600">
          Enter your email address and we'll send you a link to reset your password.
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="emailSent" class="card">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 mb-4">
            <span class="material-icon text-green-600">check_circle</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Check your email</h3>
          <p class="text-sm text-gray-600 mb-6">
            We've sent a password reset link to <strong>{{ form.email }}</strong>.
            Please check your email and follow the instructions.
          </p>
          <router-link to="/" class="btn-filled w-full flex items-center justify-center gap-2">
            <span class="material-icon-sm">arrow_back</span>
            Back to Sign In
          </router-link>
        </div>
      </div>

      <!-- Reset Form -->
      <div v-else class="card">
        <form @submit.prevent="requestReset" class="space-y-6">
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
            <button
              type="submit"
              class="btn-filled w-full flex items-center justify-center gap-2"
              :disabled="loading"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">send</span>
              {{ loading ? 'Sending...' : 'Send Reset Link' }}
            </button>
          </div>
        </form>

        <div class="mt-6 text-center">
          <router-link to="/" class="text-sm text-blue-600 hover:text-blue-500 transition-colors">
            ← Back to Sign In
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ForgotPassword',
  data() {
    return {
      loading: false,
      emailSent: false,
      form: {
        email: ''
      }
    }
  },
  methods: {
    async requestReset() {
      this.loading = true
      try {
        const response = await fetch('/backend/api/request-password-reset.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        const data = await response.json();

        if (response.ok) {
          this.emailSent = true
        } else {
          alert(data.error || 'Failed to send reset email')
        }
      } catch (error) {
        alert('Request failed. Please try again.')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
