<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
          <span class="material-icon text-blue-600">email</span>
        </div>
        <h2 class="mt-6 text-3xl font-bold text-gray-900">Verify your email</h2>
        <p class="mt-2 text-sm text-gray-600">
          We're verifying your email address...
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="verified" class="card">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 mb-4">
            <span class="material-icon text-green-600">check_circle</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Email verified successfully!</h3>
          <p class="text-sm text-gray-600 mb-6">
            Your account is now active. You can sign in with your credentials.
          </p>
          <router-link to="/" class="btn-filled w-full flex items-center justify-center gap-2">
            <span class="material-icon-sm">login</span>
            Sign In Now
          </router-link>
        </div>
      </div>

      <!-- Error Message -->
      <div v-else-if="error" class="card">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-100 mb-4">
            <span class="material-icon text-red-600">error</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Verification failed</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ error }}
          </p>
          <router-link to="/" class="btn-filled w-full flex items-center justify-center gap-2">
            <span class="material-icon-sm">home</span>
            Go to Home
          </router-link>
        </div>
      </div>

      <!-- Loading -->
      <div v-else class="card">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 mb-4">
            <span class="material-icon text-blue-600 animate-spin">refresh</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Verifying...</h3>
          <p class="text-sm text-gray-600">
            Please wait while we verify your email address.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'VerifyEmail',
  data() {
    return {
      verified: false,
      error: null,
      token: ''
    }
  },
  mounted() {
    // Get token from URL query parameters
    const urlParams = new URLSearchParams(window.location.search)
    this.token = urlParams.get('token') || ''

    if (!this.token) {
      this.error = 'Invalid verification link. Please check your email for the correct link.'
      return
    }

    this.verifyEmail()
  },
  methods: {
    async verifyEmail() {
      try {
        const response = await fetch(`/backend/api/verify-email.php?token=${encodeURIComponent(this.token)}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          }
        });
        const data = await response.json();

        if (response.ok) {
          this.verified = true
        } else {
          this.error = data.error || 'Verification failed. Please try again or contact support.'
        }
      } catch (error) {
        this.error = 'Request failed. Please try again or contact support.'
      }
    }
  }
}
</script>
