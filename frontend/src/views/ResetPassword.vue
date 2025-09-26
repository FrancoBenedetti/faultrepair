<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100">
          <span class="material-icon text-green-600">lock_reset</span>
        </div>
        <h2 class="mt-6 text-3xl font-bold text-gray-900">Set new password</h2>
        <p class="mt-2 text-sm text-gray-600">
          Enter your new password below.
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="passwordReset" class="card">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 mb-4">
            <span class="material-icon text-green-600">check_circle</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Password reset successful!</h3>
          <p class="text-sm text-gray-600 mb-6">
            Your password has been updated. You can now sign in with your new password.
          </p>
          <router-link to="/" class="btn-filled w-full flex items-center justify-center gap-2">
            <span class="material-icon-sm">login</span>
            Sign In Now
          </router-link>
        </div>
      </div>

      <!-- Reset Form -->
      <div v-else class="card">
        <form @submit.prevent="resetPassword" class="space-y-6">
          <div>
            <label for="password" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">lock</span>
              New Password
            </label>
            <input
              type="password"
              id="password"
              v-model="form.password"
              required
              minlength="6"
              class="form-input"
              placeholder="Enter new password (min 6 characters)"
            >
          </div>

          <div>
            <label for="confirmPassword" class="form-label flex items-center gap-2">
              <span class="material-icon-sm text-gray-500">lock</span>
              Confirm New Password
            </label>
            <input
              type="password"
              id="confirmPassword"
              v-model="form.confirmPassword"
              required
              minlength="6"
              class="form-input"
              placeholder="Confirm new password"
            >
          </div>

          <div v-if="form.password && form.confirmPassword && form.password !== form.confirmPassword" class="text-sm text-red-600">
            Passwords do not match
          </div>

          <div>
            <button
              type="submit"
              class="btn-filled w-full flex items-center justify-center gap-2"
              :disabled="loading || !form.password || !form.confirmPassword || form.password !== form.confirmPassword"
            >
              <span v-if="loading" class="material-icon-sm animate-spin">refresh</span>
              <span v-else class="material-icon-sm">save</span>
              {{ loading ? 'Updating...' : 'Update Password' }}
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
import { apiFetch } from '@/utils/api.js'

export default {
  name: 'ResetPassword',
  data() {
    return {
      loading: false,
      passwordReset: false,
      token: '',
      form: {
        password: '',
        confirmPassword: ''
      }
    }
  },
  mounted() {
    // Get token from URL query parameters
    this.token = this.$route.query.token || ''

    if (!this.token) {
      alert('Invalid reset link. Please request a new password reset.')
      this.$router.push('/forgot-password')
    }
  },
  methods: {
    async resetPassword() {
      if (this.form.password !== this.form.confirmPassword) {
        alert('Passwords do not match')
        return
      }

      this.loading = true
      try {
        const response = await apiFetch('/backend/api/verify-email.php', {
          method: 'POST',
          body: JSON.stringify({
            token: this.token,
            action: 'reset',
            password: this.form.password
          })
        })

        if (response.ok) {
          this.passwordReset = true
        } else {
          const data = await response.json()
          alert(data.error || 'Failed to reset password')
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
