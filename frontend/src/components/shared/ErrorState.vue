<template>
  <div class="error-state" :class="{ 'col-span-full': fullWidth }">
    <div class="error-icon material-icon-xl text-error-400 mb-4">{{ icon || 'error' }}</div>
    <h3 class="text-title-large text-on-surface mb-2">{{ title || 'Something went wrong' }}</h3>
    <p class="text-body-large text-on-surface-variant mb-4">{{ message || 'Please try refreshing the page.' }}</p>
    <slot>
      <!-- Custom error content can be slotted in -->
    </slot>
    <div v-if="showRetry" class="retry-section mt-4">
      <button @click="handleRetry" class="btn-outlined text-error-600 border-error-600 hover:bg-error-50">
        {{ retryText || 'Try Again' }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ErrorState',
  props: {
    title: {
      type: String,
      default: 'Something went wrong'
    },
    message: {
      type: String,
      default: 'Please try refreshing the page.'
    },
    icon: {
      type: String,
      default: 'error'
    },
    showRetry: {
      type: Boolean,
      default: false
    },
    retryText: {
      type: String,
      default: 'Try Again'
    },
    fullWidth: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    handleRetry() {
      this.$emit('retry')
    }
  }
}
</script>

<style scoped>
.error-state {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.error-state.col-span-full {
  grid-column: 1 / -1;
}

.retry-section {
  margin-top: 16px;
}

.btn-outlined {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 14px;
  color: #666;
  transition: all 0.2s ease;
}

.btn-outlined:hover {
  background: #f0f0f0;
}
</style>
