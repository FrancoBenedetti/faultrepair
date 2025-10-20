<template>
  <div class="loading-state" :class="{ 'col-span-full': fullWidth }">
    <div class="loading-spinner w-10 h-10 border-4 border-neutral-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
    <p class="text-body-large text-on-surface-variant">{{ message || 'Loading...' }}</p>
    <slot>
      <!-- Additional content can be slotted in -->
    </slot>
  </div>
</template>

<script>
export default {
  name: 'LoadingState',
  props: {
    message: {
      type: String,
      default: 'Loading...'
    },
    fullWidth: {
      type: Boolean,
      default: true
    }
  }
}
</script>

<style scoped>
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.loading-state.col-span-full {
  grid-column: 1 / -1;
}

/* Remove scoped styles if any global loading styles are needed */
</style>
