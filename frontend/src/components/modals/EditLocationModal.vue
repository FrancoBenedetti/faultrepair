<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Edit Location</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="location-form">
        <div class="form-row">
          <div class="form-group">
            <label for="edit_name">Location Name *</label>
            <input type="text" id="edit_name" v-model="editingLocation.name" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit_address">Address *</label>
            <textarea id="edit_address" v-model="editingLocation.address" rows="3" required></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit_coordinates">GPS Coordinates</label>
            <input type="text" id="edit_coordinates" v-model="editingLocation.coordinates"
                   placeholder="-26.2041,28.0473">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit_access_rules">Site Information URL</label>
            <input type="url" id="edit_access_rules" v-model="editingLocation.access_rules"
                   placeholder="https://example.com/site-info">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit_access_instructions">Access Instructions</label>
            <textarea id="edit_access_instructions" v-model="editingLocation.access_instructions" rows="2"
                      placeholder="Instructions for technicians on how to access this location..."></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="updating">
            {{ updating ? 'Updating...' : 'Update Location' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EditLocationModal',
  props: {
    editingLocation: {
      type: Object,
      default: () => ({})
    },
    updating: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit'],
  methods: {
    handleSubmit() {
      this.$emit('submit', this.editingLocation)
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

.location-form {
  padding: 20px;
}

.form-row {
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
  font-size: 14px;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group textarea {
  resize: vertical;
  min-height: 60px;
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
  }
}
</style>
