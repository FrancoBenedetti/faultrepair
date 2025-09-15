<template>
  <div class="home">
    <h1>Fault Reporter</h1>
    <p>Welcome to the Fault Reporter system. Please select your access type below:</p>

    <div class="access-section">
      <h2>For Clients</h2>
      <div class="access-links">
        <router-link to="/client-registration" class="access-link">Register as Client</router-link>
        <router-link to="/client-signin" class="access-link">Sign In as Client</router-link>
      </div>
    </div>

    <div class="access-section">
      <h2>For Service Providers</h2>
      <div class="access-links">
        <router-link to="/service-provider-registration" class="access-link">Register as Service Provider</router-link>
        <router-link to="/service-provider-signin" class="access-link">Sign In as Service Provider</router-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Home'
}
</script>

<style scoped>
.home {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  text-align: center;
}

.access-section {
  margin: 30px 0;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f9f9f9;
}

.access-section h2 {
  margin-top: 0;
  color: #333;
}

.access-links {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 15px;
}

.access-link {
  display: inline-block;
  padding: 10px 20px;
  background-color: #007bff;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.access-link:hover {
  background-color: #0056b3;
}
</style>
