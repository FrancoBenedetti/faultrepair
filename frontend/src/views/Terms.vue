<template>
  <div class="min-h-screen bg-gray-50 py-12 px-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
          <span class="material-icon">arrow_back</span>
          <span class="ml-2">Back to Home</span>
        </router-link>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Terms & Conditions</h1>
        <p class="text-lg text-gray-600">Last updated: {{ new Date().toLocaleDateString() }}</p>
      </div>

      <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 space-y-8">

        <!-- Beta Testing Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <div class="flex items-start gap-3">
            <span class="material-icon text-blue-600 mt-1">info</span>
            <div>
              <h3 class="text-lg font-semibold text-blue-900 mb-2">Beta Testing Period</h3>
              <p class="text-blue-800">
                <strong>This service is currently in beta testing and is offered at no charge.</strong>
                During this period, access to all features is completely free. We plan to introduce subscription tiers
                in the future, but your business data remains entirely yours with no percentage-based fees.
              </p>
            </div>
          </div>
        </div>

        <!-- Section 1: Service Overview -->
        <section>
          <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Service Description</h2>
          <p class="text-gray-700 mb-4">
            Snappy (Fault Reporter) is a platform that streamlines equipment maintenance and repair workflows by connecting
            asset owners with service providers through QR code-enabled reporting and instant notification systems.
          </p>
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">For Asset Owners:</h3>
              <ul class="text-gray-700 space-y-1">
                <li>• QR code fault reporting</li>
                <li>• Photo attachments</li>
                <li>• Service provider matching</li>
                <li>• Real-time progress tracking</li>
              </ul>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">For Service Providers:</h3>
              <ul class="text-gray-700 space-y-1">
                <li>• Instant service notifications</li>
                <li>• Detailed fault reports</li>
                <li>• Job management tools</li>
                <li>• Completion confirmation</li>
              </ul>
            </div>
          </div>
        </section>

        <!-- Section 2: Free Beta Access -->
        <section>
          <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Free Beta Access</h2>
          <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-green-900 mb-3">Currently No Charge</h3>
            <p class="text-green-800 mb-4">
              During beta testing, all platform functionality is offered at no charge. This includes
              unlimited reports, users, and full feature access.
            </p>
            <p class="text-green-800 mb-4">
              <strong>No fees, no subscriptions, no hidden costs.</strong>
            </p>
            <div class="bg-green-200 rounded-lg p-4">
              <p class="text-green-900 text-sm">
                Future subscription options may be introduced for advanced features,
                but beta users will receive priority access and migration support.
              </p>
            </div>
          </div>
        </section>

        <!-- Section 3: Data Usage -->
        <section>
          <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Information Collection and Use</h2>
          <div class="space-y-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">What We Collect:</h3>
              <ul class="text-gray-700 space-y-1 ml-4">
                <li>• Company and account information</li>
                <li>• Equipment fault reports and photos</li>
                <li>• Service request communications</li>
                <li>• Usage data and system logs</li>
              </ul>
            </div>

            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Data Purpose:</h3>
              <ul class="text-gray-700 space-y-1 ml-4">
                <li>• Facilitate service connections</li>
                <li>• Maintain repair records</li>
                <li>• Provide platform functionality</li>
                <li>• Improve service quality</li>
              </ul>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p class="text-yellow-800 text-sm">
                <strong>Privacy:</strong> Your business data is used solely for platform functionality.
                We do not sell data or share it with third parties except as needed for service delivery.
              </p>
            </div>
          </div>
        </section>

        <!-- Section 4: Liability Waiver -->
        <section>
          <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Limitation of Liability</h2>
          <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <p class="text-red-800 mb-4">
              <strong>The platform is provided "as is" without warranties or guarantees.</strong>
              We are not liable for equipment damage, service delays, or repair quality.
            </p>
            <ul class="text-red-800 space-y-2">
              <li>• No warranty of service availability</li>
              <li>• No responsibility for service provider performance</li>
              <li>• No liability for indirect damages</li>
              <li>• Beta testing may include service interruptions</li>
            </ul>
          </div>
        </section>

        <!-- Section 5: Account Termination -->
        <section>
          <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Account Termination</h2>
          <p class="text-gray-700">
            Either party may terminate service at any time. Upon termination, accounts and data
            may be deleted after a reasonable period.
          </p>
        </section>

        <!-- Contact Section -->
        <div class="bg-gray-50 -m-8 mt-8 p-8 rounded-b-xl border-t border-gray-200">
          <div class="text-center">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Questions?</h2>
            <router-link to="/" class="btn-filled">
              Return to Home
            </router-link>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Terms'
}
</script>
