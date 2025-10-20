<template>
  <div class="card" :class="{ clickable }" @click="clickable ? $emit('click') : null">
    <div v-if="$slots.header" class="card-header">
      <slot name="header"></slot>
    </div>

    <div v-if="$slots.content" class="card-content">
      <slot name="content"></slot>
    </div>

    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Card',
  props: {
    clickable: {
      type: Boolean,
      default: false
    }
  }
}
</script>

<style scoped>
.card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
  background: white;
}

.card.clickable {
  cursor: pointer;
}

.card.clickable:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
}

.card-header, .card-content, .card-footer {
  padding: 15px;
}

.card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-content {
  flex: 1;
}

.card-footer {
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}
</style>
