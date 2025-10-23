<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Provider Details</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <div class="provider-details-modal" v-if="provider">
        <div class="provider-info-section">
          <div class="info-grid">
            <div class="info-item">
              <label>Provider Name</label>
              <span>{{ provider.name }}</span>
            </div>
            <div class="info-item">
              <label>Address</label>
              <span>{{ provider.address }}</span>
            </div>
            <div class="info-item">
              <label>Approval Status</label>
              <span class="status-approved">Approved</span>
            </div>
            <div class="info-item">
              <label>Approved Date</label>
              <span>{{ formatDate(provider.approved_at) }}</span>
            </div>
          </div>
        </div>

        <div class="provider-stats-section">
          <h4>Service Statistics</h4>
          <div class="stats-grid">
            <div class="stat-box">
              <div class="stat-number">{{ provider.total_jobs }}</div>
              <div class="stat-label">Total Jobs</div>
            </div>
            <div class="stat-box">
              <div class="stat-number">{{ provider.active_jobs }}</div>
              <div class="stat-label">Active Jobs</div>
            </div>
            <div class="stat-box">
              <div class="stat-number">{{ provider.completed_jobs }}</div>
              <div class="stat-label">Completed Jobs</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProviderDetailsModal',
  props: {
    provider: {
      type: Object,
      default: () => null
    }
  },
  emits: ['close'],
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.provider-details-modal {
  padding: 20px;
}

.provider-info-section {
  margin-bottom: 30px;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #e0e0e0;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item span {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.status-approved {
  color: #28a745;
  font-weight: 600;
}

.provider-stats-section h4 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 16px;
  border-bottom: 2px solid #007bff;
  padding-bottom: 8px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 15px;
}

.stat-box {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  transition: box-shadow 0.2s;
}

.stat-box:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-number {
  font-size: 2em;
  font-weight: bold;
  color: #333;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 0.9em;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

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
  max-width: 700px;
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

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
