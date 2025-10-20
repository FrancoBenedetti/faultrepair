<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Add New User</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="user-form">
        <div class="form-row">
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" v-model="newUser.email" required>
          </div>
          <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" v-model="newUser.first_name" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="last_name">Last Name *</label>
            <input type="text" id="last_name" v-model="newUser.last_name" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" v-model="newUser.role_id" required>
              <option value="">Select Role</option>
              <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                {{ role.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="addingUser">
            {{ addingUser ? 'Adding User...' : 'Add User' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AddUserModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    newUser: {
      type: Object,
      required: true
    },
    availableRoles: {
      type: Array,
      default: () => []
    },
    addingUser: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit'],
  methods: {
    handleSubmit() {
      this.$emit('submit')
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
  user-select: none;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 5px 10px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.user-form {
  padding: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-primary,
.btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
