<!-- /frontend/src/views/QrHandler.vue -->
<template>
  <div class="loading-container">
    <p>Processing QR Code...</p>
  </div>
</template>

<script>
import { isTokenExpired } from '@/utils/api.js';

export default {
  name: 'QrHandler',
  props: {
    query: {
      type: Object,
      default: () => ({})
    }
  },
  mounted() {
    const isAuthenticated = !isTokenExpired();
    const queryString = new URLSearchParams(this.query).toString();
    
    if (isAuthenticated) {
      // User is logged in, redirect to CreateJob with the query string.
      // CreateJob will need to be updated to read from the route query.
      this.$router.replace({ path: '/client/create-job', query: this.query });
    } else {
      // User is not logged in. Store the QR data and redirect to login.
      localStorage.setItem('qrRedirectData', queryString);
      // Redirect to the main login page.
      this.$router.replace({ path: '/' }); 
      alert('Please sign in to create a service request.');
    }
  }
}
</script>

<style scoped>
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  font-size: 1.2rem;
  color: #555;
}
</style>
