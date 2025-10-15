<template>
  <div class="min-h-screen bg-gray-50 py-12 px-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <router-link to="/" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
          <span class="material-icon">arrow_back</span>
          <span class="ml-2">Back to Home</span>
        </router-link>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Subscription Options</h1>
        <p class="text-xl text-gray-600 mb-6">Transparent pricing for when we move beyond beta testing</p>

        <!-- Current Status Banner -->
        <div class="bg-green-100 border border-green-300 rounded-lg p-6 max-w-2xl mx-auto mb-8">
          <div class="flex items-center justify-center gap-3 text-green-800 mb-4">
            <span class="material-icon text-2xl">check_circle</span>
            <h2 class="text-2xl font-bold">Currently FREE</h2>
          </div>
          <p class="text-green-700 text-lg mb-4">
            Snappy is in beta testing and all features are completely free to use right now.
          </p>
          <div class="bg-green-200 rounded-lg p-4">
            <p class="text-green-900 font-semibold text-center">
              ✓ No registration fees<br>
              ✓ No usage charges<br>
              ✓ No percentage of repair costs<br>
              ✓ Full access to all current features
            </p>
          </div>
        </div>
      </div>

      <!-- Future Pricing Structure -->
      <div class="mb-12">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Future Subscription Model</h2>
        <p class="text-xl text-gray-600 text-center mb-12 max-w-3xl mx-auto">
          When we move beyond beta testing, subscriptions may be introduced to support platform development and premium features. Here's what we're planning:
        </p>
      </div>

      <!-- Pricing Comparison -->
      <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto mb-16">

        <!-- Essential Plan -->
        <div class="pricing-card card relative">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-blue-600 text-3xl">build</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Essential</h3>
            <p class="text-gray-600">Basic fault reporting and tracking</p>
          </div>

          <div class="text-center mb-6">
            <div class="text-3xl font-bold text-gray-900 mb-1">[TBD]</div>
            <p class="text-gray-600">per user/month (estimated)</p>
          </div>

          <ul class="space-y-3 mb-8">
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>QR code fault reporting</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Photo attachments</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Basic service provider matching</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Email notifications</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-gray-400">close</span>
              <span class="text-gray-400">Advanced analytics</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-gray-400">close</span>
              <span class="text-gray-400">Priority support</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-gray-400">close</span>
              <span class="text-gray-400">API access</span>
            </li>
          </ul>

          <div class="text-center">
            <div class="text-sm text-gray-500 bg-gray-100 rounded-lg px-3 py-2">
              Coming when beta ends
            </div>
          </div>
        </div>

        <!-- Professional Plan -->
        <div class="pricing-card card relative border-2 border-blue-500">
          <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
            <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-medium">Most Popular</span>
          </div>

          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-blue-600 text-3xl">business_center</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
            <p class="text-gray-600">Advanced features for growing businesses</p>
          </div>

          <div class="text-center mb-6">
            <div class="text-3xl font-bold text-gray-900 mb-1">[TBD]</div>
            <p class="text-gray-600">per user/month (estimated)</p>
          </div>

          <ul class="space-y-3 mb-8">
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Everything in Essential</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Advanced reporting & analytics</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Custom report templates</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Priority email/phone support</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Multi-location support</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-gray-400">close</span>
              <span class="text-gray-400">API access</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-gray-400">close</span>
              <span class="text-gray-400">White-label options</span>
            </li>
          </ul>

          <div class="text-center">
            <div class="text-sm text-blue-500 bg-blue-50 rounded-lg px-3 py-2 font-medium">
              Recommended for most businesses
            </div>
          </div>
        </div>

        <!-- Enterprise Plan -->
        <div class="pricing-card card relative">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-purple-600 text-3xl">domain</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
            <p class="text-gray-600">Full customization for large organizations</p>
          </div>

          <div class="text-center mb-6">
            <div class="text-3xl font-bold text-gray-900 mb-1">[Custom]</div>
            <p class="text-gray-600">Contact for pricing</p>
          </div>

          <ul class="space-y-3 mb-8">
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Everything in Professional</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Full API access</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>White-label branding</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Dedicated account manager</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>Custom integrations</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>On-premise deployment options</span>
            </li>
            <li class="flex items-center gap-3">
              <span class="material-icon-sm text-green-600">check_circle</span>
              <span>SLA guarantees</span>
            </li>
          </ul>

          <div class="text-center">
            <div class="text-sm text-purple-600 bg-purple-50 rounded-lg px-3 py-2 font-medium">
              Custom pricing available
            </div>
          </div>
        </div>
      </div>

      <!-- Business Model Transparency -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Our Business Model</h2>

        <div class="grid md:grid-cols-2 gap-12">
          <!-- What We Don't Do -->
          <div>
            <h3 class="text-xl font-bold text-red-900 mb-4 flex items-center gap-2">
              <span class="material-icon text-red-600">block</span>
              What We Won't Do
            </h3>
            <ul class="space-y-3">
              <li class="flex items-start gap-3">
                <span class="material-icon-sm text-red-600 mt-1">close</span>
                <div>
                  <div class="font-semibold text-gray-900">No Percentage Fees</div>
                  <div class="text-gray-600">We never take a cut of repair costs or service fees</div>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="material-icon-sm text-red-600 mt-1">close</span>
                <div>
                  <div class="font-semibold text-gray-900">No Data Monetization</div>
                  <div class="text-gray-600">We don't sell or share your business data with third parties</div>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="material-icon-sm text-red-600 mt-1">close</span>
                <div>
                  <div class="font-semibold text-gray-900">No Nickel-and-Diming</div>
                  <div class="text-gray-600">Hidden fees, usage limits, or surprise charges</div>
                </div>
              </li>
            </ul>
          </div>

          <!-- What We Do -->
          <div>
            <h3 class="text-xl font-bold text-green-900 mb-4 flex items-center gap-2">
              <span class="material-icon text-green-600">check_circle</span>
              How We Operate
            </h3>
            <ul class="space-y-3">
              <li class="flex items-start gap-3">
                <span class="material-icon-sm text-green-600 mt-1">check_circle</span>
                <div>
                  <div class="font-semibold text-gray-900">Transparent Subscription Pricing</div>
                  <div class="text-gray-600">Clear, predictable monthly fees for premium features</div>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="material-icon-sm text-green-600 mt-1">check_circle</span>
                <div>
                  <div class="font-semibold text-gray-900">Beta-to-Paid Migration</div>
                  <div class="text-gray-600">Existing beta users get priority and special terms</div>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="material-icon-sm text-green-600 mt-1">check_circle</span>
                <div>
                  <div class="font-semibold text-gray-900">Value-Based Pricing</div>
                  <div class="text-gray-600">Pay for features you use, not arbitrary limits</div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-xl p-8 mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Development Roadmap</h2>

        <div class="grid md:grid-cols-4 gap-6">
          <!-- Phase 1: Current Beta -->
          <div class="text-center">
            <div class="w-12 h-12 bg-green-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">
              ✓
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Beta Testing</h3>
            <p class="text-sm text-gray-600 mb-2">Current Phase</p>
            <p class="text-gray-700">Free access to core features, gathering feedback</p>
          </div>

          <!-- Phase 2: Growth -->
          <div class="text-center">
            <div class="w-12 h-12 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center mx-auto mb-4 text-lg font-bold">
              2
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Feature Expansion</h3>
            <p class="text-sm text-gray-600 mb-2">Next Phase</p>
            <p class="text-gray-700">Advanced analytics, mobile app, integrations</p>
          </div>

          <!-- Phase 3: Stabilization -->
          <div class="text-center">
            <div class="w-12 h-12 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center mx-auto mb-4 text-lg font-bold">
              3
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Platform Stabilization</h3>
            <p class="text-sm text-gray-600 mb-2">Future</p>
            <p class="text-gray-700">Enterprise features, white-label options</p>
          </div>

          <!-- Phase 4: Monetization -->
          <div class="text-center">
            <div class="w-12 h-12 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center mx-auto mb-4 text-lg font-bold">
              4
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Subscription Launch</h3>
            <p class="text-sm text-gray-600 mb-2">Future</p>
            <p class="text-gray-700">Optional premium features with transparent pricing</p>
          </div>
        </div>
      </div>

      <!-- Contact Section -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Questions About Pricing?</h2>
        <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
          We're committed to transparent pricing and fair business practices. Have questions about subscriptions or want to provide beta feedback?
        </p>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <router-link to="/" class="btn-filled">
            <span class="material-icon-sm mr-2">login</span>
            Start Using Snappy Free
          </router-link>
          <router-link to="/terms" class="btn-outlined">
            <span class="material-icon-sm mr-2">description</span>
            Read Terms & Conditions
          </router-link>
        </div>

        <p class="text-sm text-gray-500 mt-6">
          During beta testing, all features remain 100% free with no obligations or credit card requirements.
        </p>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: 'Subscription'
}
</script>

<style scoped>
.pricing-card {
  padding: 2rem;
  transition: all 0.3s ease;
}

.pricing-card:hover {
  transform: translateY(-4px);
}

@media (max-width: 768px) {
  .pricing-card {
    margin-bottom: 2rem;
  }
}
</style>
