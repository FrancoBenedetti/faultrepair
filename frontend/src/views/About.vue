<template>
  <div class="min-h-screen bg-gray-50 py-12 px-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <router-link to="/" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
          <span class="material-icon">arrow_back</span>
          <span class="ml-2">Back to Home</span>
        </router-link>

        <!-- Hero Section -->
        <div class="mb-8">
          <div class="flex items-center justify-center mb-6">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-green-500 rounded-full flex items-center justify-center shadow-lg">
              <span class="material-icon text-white text-4xl">handyman</span>
            </div>
          </div>
          <h1 class="text-5xl font-bold text-gray-900 mb-4">About Snappy</h1>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Simplifying equipment maintenance workflows by connecting asset owners with service providers through QR code-enabled reporting.
          </p>
        </div>

        <!-- Beta Status -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 max-w-2xl mx-auto mb-8">
          <div class="flex items-center justify-center gap-2 text-blue-800 mb-3">
            <span class="material-icon-sm">science</span>
            <span class="font-semibold">Beta Testing Phase</span>
          </div>
          <p class="text-blue-700">
            We're currently in beta testing with <strong>free access to all features</strong>.
            Your feedback helps us build the best platform for equipment maintenance management.
          </p>
        </div>
      </div>

      <!-- The Problem Section -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
        <div class="grid md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-6">The Problem We Solve</h2>

            <div class="space-y-4 mb-6">
              <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                  <span class="material-icon text-red-600 text-lg">warning</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">Paperwork Delays</h3>
                  <p class="text-gray-600">Repair requests stuck in filing cabinets or forgotten emails</p>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                  <span class="material-icon text-red-600 text-lg">location_off</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">Poor Communication</h3>
                  <p class="text-gray-600">Service providers can't find equipment or understand the problem</p>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                  <span class="material-icon text-red-600 text-lg">schedule</span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">Response Delays</h3>
                  <p class="text-gray-600">Weeks to start repairs because notifications never reach the right people</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-xl p-6">
            <div class="text-center mb-4">
              <span class="material-icon text-gray-400 text-6xl">description</span>
            </div>
            <p class="text-gray-600 italic">
              "We had a critical HVAC unit fail during peak season. It took 3 days just to get the service request paperwork processed and approved before the technician could even start assessing the problem."
            </p>
            <p class="text-gray-500 text-sm mt-3 text-center">
              — Facility Manager, Manufacturing Company
            </p>
          </div>
        </div>
      </div>

      <!-- Our Solution Section -->
      <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-xl p-8 mb-12">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Solution</h2>
          <p class="text-xl text-gray-600">
            QR code-powered maintenance reporting that eliminates paperwork and gets repairs started instantly
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Step 1 -->
          <div class="bg-white rounded-xl p-6 text-center shadow-lg border border-gray-200">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-blue-600 text-3xl">qr_code_scanner</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">1. Scan QR Code</h3>
            <p class="text-gray-600">
              Use any smartphone camera to scan the equipment's QR code sticker and instantly identify the asset.
            </p>
          </div>

          <!-- Step 2 -->
          <div class="bg-white rounded-xl p-6 text-center shadow-lg border border-gray-200">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-green-600 text-3xl">add_photo_alternate</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">2. Report & Photo</h3>
            <p class="text-gray-600">
              Take photos, describe the problem, and submit the report instantly from anywhere.
            </p>
          </div>

          <!-- Step 3 -->
          <div class="bg-white rounded-xl p-6 text-center shadow-lg border border-gray-200">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-purple-600 text-3xl">engineering</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">3. Service Instantly</h3>
            <p class="text-gray-600">
              Approved service providers receive instant notifications and can begin repairs immediately.
            </p>
          </div>
        </div>
      </div>

      <!-- Who We Serve -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Who We Serve</h2>

        <div class="grid md:grid-cols-2 gap-12">
          <!-- Asset Owners -->
          <div>
            <div class="flex items-center gap-4 mb-6">
              <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center">
                <span class="material-icon text-blue-600 text-3xl">business</span>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900">Asset Owners</h3>
                <p class="text-gray-600">Businesses that own and maintain equipment</p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Manufacturing facilities</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Property management companies</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Retail chains with equipment</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Healthcare facilities</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Restaurants and hospitality</span>
              </div>
            </div>
          </div>

          <!-- Service Providers -->
          <div>
            <div class="flex items-center gap-4 mb-6">
              <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center">
                <span class="material-icon text-green-600 text-3xl">engineering</span>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-gray-900">Service Providers</h3>
                <p class="text-gray-600">Companies that repair and maintain equipment</p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">HVAC technicians</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Electrical contractors</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Plumbing services</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">General maintenance providers</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span class="text-gray-700">Equipment manufacturers</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Key Benefits -->
      <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Key Benefits</h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Benefit Cards -->
          <div class="text-center p-6 border border-gray-200 rounded-lg">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-green-600">schedule</span>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Faster Repairs</h3>
            <p class="text-gray-600 text-sm">Get equipment back online 3-5x faster than traditional methods</p>
          </div>

          <div class="text-center p-6 border border-gray-200 rounded-lg">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-blue-600">attach_money</span>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Cost Savings</h3>
            <p class="text-gray-600 text-sm">Reduce downtime costs and eliminate paperwork overhead</p>
          </div>

          <div class="text-center p-6 border border-gray-200 rounded-lg">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-purple-600">smartphone</span>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Mobile First</h3>
            <p class="text-gray-600 text-sm">Works on any smartphone, anywhere, anytime</p>
          </div>

          <div class="text-center p-6 border border-gray-200 rounded-lg">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-orange-600">visibility</span>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Full Visibility</h3>
            <p class="text-gray-600 text-sm">Track every repair from request to completion</p>
          </div>

          <div class="text-center p-6 border border-gray-200 rounded-lg">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-red-600">handshake</span>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Better Communication</h3>
            <p class="text-gray-600 text-sm">Clear information flow between all stakeholders</p>
          </div>

          <div class="text-center p-6 border border-gray-200 rounded-lg">
            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="material-icon text-teal-600">description</span>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Digital Records</h3>
            <p class="text-gray-600 text-sm">Complete maintenance history and documentation</p>
          </div>
        </div>
      </div>

      <!-- Beta Testing Section -->
      <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-8 mb-12">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Join Our Beta Program</h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Help us build the best maintenance management platform while getting free access to all features
          </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
          <!-- What We Get -->
          <div class="bg-white rounded-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4 text-center">What We Get from You</h3>
            <ul class="space-y-3">
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-blue-600">feedback</span>
                <span>Beta testing feedback and suggestions</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-blue-600">bug_report</span>
                <span>Bug reports and improvement ideas</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-blue-600">recommend</span>
                <span>Referrals to other potential users</span>
              </li>
            </ul>
          </div>

          <!-- What You Get -->
          <div class="bg-white rounded-xl p-6 border-2 border-green-300">
            <h3 class="text-xl font-bold text-gray-900 mb-4 text-center">What You Get</h3>
            <ul class="space-y-3">
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span><strong>100% free access</strong> to all features</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Early access to new features</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Direct influence on product development</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="material-icon-sm text-green-600">check_circle</span>
                <span>Priority support during beta</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Call to Action -->
      <div class="bg-gradient-to-r from-blue-600 to-green-600 rounded-xl p-8 text-white text-center">
        <h2 class="text-3xl font-bold mb-4">Ready to Try Snappy?</h2>
        <p class="text-xl mb-8 opacity-90">
          Join our beta program and start streamlining your maintenance workflows today
        </p>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <router-link to="/register" class="bg-white text-blue-600 hover:bg-gray-50 font-bold px-8 py-3 rounded-lg transition-colors">
            <span class="material-icon-sm mr-2">person_add</span>
            Sign Up Free
          </router-link>
          <router-link to="/" class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg transition-colors">
            <span class="material-icon-sm mr-2">login</span>
            Sign In
          </router-link>
        </div>

        <p class="text-sm mt-6 opacity-75">
          No credit card required • Free during beta testing • No obligations
        </p>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: 'About'
}
</script>
