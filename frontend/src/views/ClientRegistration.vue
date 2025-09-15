<template>
  <div class="registration">
    <h2>Client Registration</h2>
    <form @submit.prevent="register">
      <div class="form-group">
        <label for="clientName">Company Name:</label>
        <input type="text" id="clientName" v-model="form.clientName" required>
      </div>
      <div class="form-group">
        <label for="address">Address:</label>
        <textarea id="address" v-model="form.address" required></textarea>
      </div>
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" v-model="form.username" required>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" v-model="form.email" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" v-model="form.password" required>
      </div>
      <button type="submit">Register</button>
    </form>
    <p>Already have an account? <router-link to="/client-signin">Sign In</router-link></p>
  </div>
</template>

<script>
export default {
  name: 'ClientRegistration',
  data() {
    return {
      form: {
        clientName: '',
        address: '',
        username: '',
        email: '',
        password: ''
      }
    }
  },
  methods: {
    async register() {
      try {
        const response = await fetch('/backend/api/register-client.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        const data = await response.json();
        if (response.ok) {
          alert('Registration successful! You can now sign in.');
          this.$router.push('/client-signin');
        } else {
          alert(data.error);
        }
      } catch (error) {
        alert('Registration failed. Please try again.');
      }
    }
  }
}
</script>

<style scoped>
.registration {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
}

input, textarea {
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
