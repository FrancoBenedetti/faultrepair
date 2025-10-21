<template>
  <div class="collapsible-section card">
    <div class="section-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 pb-4 border-b border-neutral-200" @click="$emit('toggle')" style="cursor: pointer;">
      <div class="section-title flex items-center gap-3">
        <button class="expand-btn" :class="{ expanded }" @click="$emit('toggle')">
          <span class="material-icon-sm">expand_more</span>
        </button>
        <h2 class="text-title-large text-on-surface mb-0 flex items-center gap-3">
          <span v-if="icon" class="material-icon" :class="iconClass">{{ icon }}</span>
          {{ title }}
          <slot name="title-suffix">
            <!-- Custom content after title -->
          </slot>
        </h2>
      </div>

      <div class="section-header-actions flex items-center gap-4" @click.stop>
        <slot name="header-actions">
          <!-- Custom header actions -->
        </slot>
      </div>
    </div>

    <transition name="section-content" @enter="onEnter" @after-enter="onAfterEnter" @leave="onLeave">
      <div v-show="expanded" class="section-content transition-all duration-300 ease-in-out">
        <slot>
          <!-- Section content goes here -->
        </slot>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'CollapsibleSection',
  props: {
    title: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      default: null
    },
    iconClass: {
      type: String,
      default: 'text-blue-600'
    },
    expanded: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    onEnter(el) {
      el.style.height = '0px'
      el.style.overflow = 'hidden'
    },
    onAfterEnter(el) {
      el.style.height = 'auto'
      el.style.overflow = 'visible'
    },
    onLeave(el) {
      el.style.height = el.offsetHeight + 'px'
      el.style.overflow = 'hidden'
      // Force reflow
      el.offsetHeight
      el.style.height = '0px'
    }
  }
}
</script>

<style scoped>
.collapsible-section {
  background: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
  cursor: pointer;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.expand-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  transition: transform 0.3s ease, background-color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.expand-btn:hover {
  background-color: #f0f0f0;
}

.expand-btn.expanded {
  transform: rotate(180deg);
}

.expand-btn .material-icon-sm {
  font-size: 20px;
  color: #666;
}

.section-content {
  overflow: hidden;
  /* Styles handled by transition methods for smooth animation */
}

/* Vue transition classes */
.section-content-enter-active, .section-content-leave-active {
  transition: height 0.3s ease-in-out;
}

.section-content-enter-from, .section-content-leave-to {
  height: 0px !important;
  opacity: 0;
}

.section-content-enter-to, .section-content-leave-from {
  height: auto;
  opacity: 1;
}

@media (max-width: 768px) {
  .collapsible-section {
    padding: 20px;
  }

  .section-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .section-header-actions {
    align-self: flex-start;
  }
}
</style>
