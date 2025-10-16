<template>
  <div class="registration">
    <h2>Service Provider Registration</h2>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state" style="text-align: center; padding: 50px;">
      <div style="border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; margin: 0 auto 15px;"></div>
      <p>Loading invitation details...</p>
    </div>

    <!-- Existing User - Auto Approved -->
    <div v-if="invitationData && invitationData.auto_approval_applied && invitationData.access_message" class="invitation-info" style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4caf50;">
      <h3 style="margin-top: 0; color: #2e7d32;">✓ Auto-Approved!</h3>
      <p>{{ invitationData.access_message }}</p>
      <p><small>Invitation expires on {{ new Date(invitationData.expires_at).toLocaleDateString() }}</small></p>
    </div>

    <!-- Existing User - Requires Authorization -->
    <div v-if="invitationData && invitationData.invitation_status === 'requires_authorization' && invitationData.access_message" class="invitation-info" style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ff9800;">
      <h3 style="margin-top: 0; color: #e65100;">Authorization Required</h3>
      <p>{{ invitationData.access_message }}</p>
      <p><small>Invitation expires on {{ new Date(invitationData.expires_at).toLocaleDateString() }}</small></p>
    </div>

    <!-- Existing User - Completed/Informational -->
    <div v-if="invitationData && (invitationData.invitation_status === 'informational' || invitationData.invitation_status === 'completed') && invitationData.access_message" class="invitation-info" style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4caf50;">
      <h3 style="margin-top: 0; color: #2e7d32;">Notice</h3>
      <p>{{ invitationData.access_message }}</p>
      <p><small>Invitation expires on {{ new Date(invitationData.expires_at).toLocaleDateString() }}</small></p>
    </div>

    <!-- New User Invitation -->
    <div v-if="invitationData && !invitationData.access_message" class="invitation-info" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
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
  created() {
    console.log('ServiceProviderRegistration component created');
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
      console.log('Loading invitation data for token:', token);
      this.loading = true;
      try {
        const response = await fetch(`/backend/api/validate-invitation.php?token=${encodeURIComponent(token)}`);
        const data = await response.json();

        console.log('API Response:', response.status, data);

        if (response.ok && data.success) {
          this.invitationData = data.invitation;
          console.log('Invitation data loaded:', this.invitationData);

          // Pre-populate form with invitation data if available
          if (this.invitationData.invitee_email) {
            this.form.email = this.invitationData.invitee_email;
          }
          if (this.invitationData.invitee_first_name) {
            this.form.username = this.invitationData.invitee_first_name + ' ' + (this.invitationData.invitee_last_name || '');
          }
          if (this.invitationData.invitee_phone) {
            // Could pre-populate phone if available, but usually not for new registrations
          }
        } else {
          alert(data.error || 'Invalid invitation link');
          this.$router.push('/');
        }
      } catch (error) {
        console.error('Failed to load invitation data:', error);
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
