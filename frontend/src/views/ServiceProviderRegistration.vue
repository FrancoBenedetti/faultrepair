<template>
  <div class="registration">
    <h2>Service Provider Registration</h2>

    <!-- Invitation Info -->
    <div v-if="invitationData" class="invitation-info" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
      <h3 style="margin-top: 0; color: #1976d2;">You've been invited!</h3>
      <p><strong>{{ invitationData.inviter_details.name }}</strong> from <strong>{{ invitationData.inviter_details.entity_name }}</strong> has invited you to join the platform.</p>
      <p><small>This invitation expires on {{ new Date(invitationData.expires_at).toLocaleDateString() }}</small></p>
    </div>

    <form @submit.prevent="register">
      <div class="form-group">
        <label for="providerName">Company Name:</label>
        <input type="text" id="providerName" v-model="form.providerName" required>
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
    <p>Already have an account? <router-link to="/">Sign In</router-link></p>
  </div>
</template>

<script>
export default {
  name: 'ServiceProviderRegistration',
  data() {
    return {
      loading: false,
      invitationData: null,
      form: {
        providerName: '',
        address: '',
        username: '',
        email: '',
        password: '',
        invitationToken: null
      }
    }
  },
  async mounted() {
    // Check for invitation token in URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (token) {
      this.form.invitationToken = token;
      await this.loadInvitationData(token);
    }
  },
  methods: {
    async loadInvitationData(token) {
      this.loading = true;
      try {
        const response = await fetch(`/backend/api/validate-invitation.php?token=${encodeURIComponent(token)}`);
        const data = await response.json();

        if (response.ok) {
          this.invitationData = data.invitation;

          // Pre-populate form with invitation data
          if (this.invitationData.registration_data?.inviter_details) {
            const inviter = this.invitationData.registration_data.inviter_details;
            // Show invitation info but don't pre-populate user fields
            // as they need to create their own account
          }
        } else {
          alert(data.error || 'Invalid invitation link');
          this.$router.push('/');
        }
      } catch (error) {
        alert('Failed to load invitation data');
        this.$router.push('/');
      } finally {
        this.loading = false;
      }
    },
    async register() {
      this.loading = true;
      try {
        const response = await fetch('/backend/api/register-service-provider.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        const data = await response.json();
        if (response.ok) {
          alert('Registration successful! Please check your email to verify your account before signing in.');
          this.$router.push('/');
        } else {
          alert(data.error);
        }
      } catch (error) {
        alert('Registration failed. Please try again.');
      } finally {
        this.loading = false;
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
