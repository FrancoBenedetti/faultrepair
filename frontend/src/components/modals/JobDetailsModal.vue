<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content large-modal" @click.stop>
      <div class="modal-header">
        <h3>Job Details: {{ job?.item_identifier || 'No Item ID' }}</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <div v-if="job" class="job-details-content">
        <!-- Job Information -->
        <div class="job-info-section">
          <div class="info-grid">
            <div class="info-item">
              <label>Status:</label>
              <StatusBadge :status="job.job_status" />
            </div>
            <div class="info-item">
              <label>Location:</label>
              <span>{{ job.location_name }}</span>
            </div>
            <div class="info-item">
              <label>Reported By:</label>
              <span>{{ job.reporting_user }}</span>
            </div>
            <div class="info-item">
              <label>Date Reported:</label>
              <span>{{ formatDate(job.created_at) }}</span>
            </div>
            <div v-if="job.assigned_provider_name" class="info-item">
              <label>Assigned Provider:</label>
              <span>{{ job.assigned_provider_name }}</span>
            </div>
            <div class="info-item">
              <label>Last Updated:</label>
              <span>{{ formatDate(job.updated_at) }}</span>
            </div>
          </div>

          <div class="description-section">
            <label>Fault Description:</label>
            <p class="fault-description">{{ job.fault_description }}</p>
          </div>

          <div v-if="job.contact_person" class="contact-section">
            <label>Contact Person:</label>
            <span>{{ job.contact_person }}</span>
          </div>
        </div>

        <!-- Images Gallery -->
        <div class="images-section">
          <h4>Attached Images ({{ job.images?.length || 0 }})</h4>

          <div v-if="!job.images || job.images.length === 0" class="no-images">
            <div class="no-images-icon">📷</div>
            <p>No images attached to this job</p>
          </div>

          <div v-else class="images-gallery">
            <div class="gallery-grid">
              <div v-for="image in job.images" :key="image.id" class="gallery-item" @click="$emit('open-image', image)">
                <img :src="generateImageUrl(image)"
                     :alt="image.original_filename"
                     class="gallery-image">
                <div class="image-overlay">
                  <span class="image-filename">{{ image.original_filename }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'JobDetailsModal',
  components: {
    StatusBadge
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    job: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'open-image'],
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },
    generateImageUrl(image) {
      const token = localStorage.getItem('token')
      if (!token) {
        console.warn('No JWT token found for image access')
        return ''
      }
      return `/backend/api/serve-image.php?filename=${image.filename}&token=${encodeURIComponent(token)}`
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
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 5px 10px;
}

.large-modal .modal-content {
  max-width: 900px;
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

.job-details-content {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
}

/* Job Information */
.job-info-section {
  margin-bottom: 30px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
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

.description-section {
  margin-bottom: 20px;
}

.description-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

.fault-description {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
  line-height: 1.5;
  color: #333;
  white-space: pre-wrap;
}

.contact-section {
  margin-bottom: 20px;
}

.contact-section label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}

/* Images Gallery */
.images-section h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 16px;
}

.no-images {
  text-align: center;
  padding: 40px 20px;
  background: #f8f9fa;
  border-radius: 8px;
  color: #666;
}

.no-images-icon {
  font-size: 3em;
  margin-bottom: 10px;
}

.images-gallery {
  margin-top: 15px;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.gallery-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.gallery-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.gallery-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 8px;
  font-size: 11px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.image-filename {
  font-weight: 500;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 10px;
    padding: 5px 10px;
  }

  .large-modal .modal-content {
    max-width: 95vw;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
  }

  .job-details-content {
    padding: 15px;
  }
}
</style>
