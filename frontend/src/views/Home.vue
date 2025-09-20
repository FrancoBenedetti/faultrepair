<template>
  <div class="home max-w-2xl mx-auto px-6 py-12 text-center">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Fault Reporter</h1>
    <p class="text-lg text-gray-700 mb-12">
      Welcome to the Fault Reporter system. Please select your access type below:
    </p>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 mb-8 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900 mb-0 flex items-center justify-center gap-3">
          <span class="material-icon text-blue-600">business</span>
          For Clients
        </h2>
      </div>
      <div class="px-6 py-4">
        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <router-link to="/client-registration" class="bg-blue-600 text-white font-medium px-6 py-2.5 rounded-full shadow-lg hover:bg-blue-700 hover:shadow-xl active:bg-blue-800 active:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 ease-out">
            Register as Client
          </router-link>
          <router-link to="/client-signin" class="bg-transparent text-blue-600 border-2 border-blue-600 font-medium px-6 py-2.5 rounded-full hover:bg-blue-50 hover:shadow-lg active:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 ease-out">
            Sign In as Client
          </router-link>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900 mb-0 flex items-center justify-center gap-3">
          <span class="material-icon text-pink-600">engineering</span>
          For Service Providers
        </h2>
      </div>
      <div class="px-6 py-4">
        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <router-link to="/service-provider-registration" class="bg-blue-600 text-white font-medium px-6 py-2.5 rounded-full shadow-lg hover:bg-blue-700 hover:shadow-xl active:bg-blue-800 active:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 ease-out">
            Register as Service Provider
          </router-link>
          <router-link to="/service-provider-signin" class="bg-transparent text-blue-600 border-2 border-blue-600 font-medium px-6 py-2.5 rounded-full hover:bg-blue-50 hover:shadow-lg active:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 ease-out">
            Sign In as Service Provider
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Home'
}
</script>
