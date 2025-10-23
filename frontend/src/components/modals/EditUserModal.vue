<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Edit User{{ editingUser ? `: ${editingUser.first_name} ${editingUser.last_name}` : '' }}</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form v-if="editingUser" @submit.prevent="handleSubmit" class="user-form">
        <div class="form-row">
          <div class="form-group">
            <label for="edit-first-name">First Name</label>
            <input type="text" id="edit-first-name" v-model="editingUser.first_name" placeholder="Enter first name">
          </div>
          <div class="form-group">
            <label for="edit-last-name">Last Name</label>
            <input type="text" id="edit-last-name" v-model="editingUser.last_name" placeholder="Enter last name">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit-email">Email Address *</label>
            <input type="email" id="edit-email" v-model="editingUser.email" required>
          </div>
          <div class="form-group">
            <label for="edit-phone">Phone Number</label>
            <input type="tel" id="edit-phone" v-model="editingUser.phone"
                   placeholder="+27 12 345 6789">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit-role">Role *</label>
            <select id="edit-role" v-model="editingUser.role_id" required>
              <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                {{ role.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="edit-password">New Password (leave blank to keep current)</label>
          <input type="password" id="edit-password" v-model="editingUser.newPassword"
                 minlength="6">
          <small class="form-help">Minimum 6 characters</small>
        </div>

        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="updatingUser">
            {{ updatingUser ? 'Updating...' : 'Update User' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EditUserModal',
  props: {
    editingUser: {
      type: Object,
      required: true
    },
    availableRoles: {
      type: Array,
      default: () => []
    },
    updatingUser: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit'],
  methods: {
    handleSubmit() {
      this.$emit('submit', this.editingUser)
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

.form-help {
  display: block;
  margin-top: 3px;
  font-size: 0.8em;
  color: #666;
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
