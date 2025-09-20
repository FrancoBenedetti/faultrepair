<template>
  <div class="signin">
    <h2>Service Provider Sign In</h2>

    <div v-if="showSwitchUser" class="switch-user-notice">
      <p>You are currently signed in as a client. To sign in as a service provider, you need to sign out first.</p>
      <button @click="signOut" class="btn-secondary">Sign Out & Continue</button>
    </div>

    <form v-else @submit.prevent="signin">
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" v-model="form.username" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" v-model="form.password" required>
      </div>
      <button type="submit">Sign In</button>
    </form>

    <p v-if="!showSwitchUser">Don't have an account? <router-link to="/service-provider-registration">Register</router-link></p>
  </div>
</template>

<script>
export default {
  name: 'ServiceProviderSignin',
  data() {
    return {
      form: {
        username: '',
        password: ''
      },
      showSwitchUser: false
    }
  },
  mounted() {
    // Check if user is already authenticated
    const token = localStorage.getItem('token')
    if (token) {
      try {
        const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
        if (payload.entity_type === 'service_provider') {
          // Already signed in as service provider, redirect to dashboard
          this.$router.push('/service-provider-dashboard')
        } else {
          // Signed in as different user type, offer to sign out first
          this.showSwitchUser = true
        }
      } catch (error) {
        // Invalid token, remove it
        localStorage.removeItem('token')
      }
    }
  },
  methods: {
    signOut() {
      localStorage.removeItem('token')
      this.showSwitchUser = false
    },

    async signin() {
      try {
        const response = await fetch('/backend/api/auth.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        const data = await response.json();
        if (response.ok) {
          localStorage.setItem('token', data.token);
          this.$router.push('/service-provider-dashboard');
        } else {
          alert(data.error);
        }
      } catch (error) {
        alert('Sign in failed');
      }
    }
  }
}
</script>

<style scoped>
.signin {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px;
}

.switch-user-notice {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 4px;
  padding: 15px;
  margin-bottom: 20px;
  text-align: center;
}

.switch-user-notice p {
  margin: 0 0 10px 0;
  color: #856404;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary:hover {
  background-color: #545b62;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
}

input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

button {
  background-color: #007bff;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:hover {
  background-color: #0056b3;
}
</style>
