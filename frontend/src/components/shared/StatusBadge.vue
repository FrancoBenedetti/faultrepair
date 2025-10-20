<template>
  <span :class="badgeClass" class="status-badge">
    {{ displayText }}
  </span>
</template>

<script>
export default {
  name: 'StatusBadge',
  props: {
    status: {
      type: String,
      required: true
    },
    /* eslint-disable vue/prop-name-casing */
    type: {
      type: String,
      default: 'status',
      validator: (value) => ['status', 'role'].includes(value)
    }
  },
  computed: {
    badgeClass() {
      if (this.type === 'role') {
        switch (this.status?.toLowerCase()) {
          case 'reporting employee':
            return 'role-reporting'
          case 'site budget controller':
            return 'role-controller'
          default:
            return 'role-default'
        }
      } else {
        // Status badges for job statuses
        const statusMap = {
          'reported': 'reported',
          'assigned': 'assigned',
          'in progress': 'in-progress',
          'completed': 'completed',
          'confirmed': 'confirmed',
          'incomplete': 'incomplete',
          'cannot repair': 'cannot-repair',
          'declined': 'declined',
          'quote requested': 'quote-requested',
          'quote provided': 'quote-provided',
          'quote accepted': 'quote-accepted',
          'quote rejected': 'quote-rejected'
        }
        return statusMap[this.status?.toLowerCase()] || 'reported'
      }
    },
    displayText() {
      if (this.type === 'role') {
        const roleMap = {
          'reporting employee': 'Reporting Employee',
          'site budget controller': 'Site Budget Controller'
        }
        return roleMap[this.status?.toLowerCase()] || this.status
      }
      return this.status
    }
  }
}
</script>

<style scoped>
.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 500;
  text-transform: uppercase;
  display: inline-block;
}

.status-badge.reported {
  background: #ffc107;
  color: #212529;
}

.status-badge.assigned {
  background: #17a2b8;
  color: white;
}

.status-badge.in-progress {
  background: #007bff;
  color: white;
}

.status-badge.completed {
  background: #28a745;
  color: white;
}

.status-badge.confirmed {
  background: #20c997;
  color: white;
}

.status-badge.incomplete {
  background: #fd7e14;
  color: white;
}

.status-badge.cannot-repair {
  background: #6c757d;
  color: white;
}

.status-badge.declined {
  background: #dc3545;
  color: white;
}

.status-badge.quote-requested {
  background: #8e44ad;
  color: white;
}

.status-badge.quote-provided {
  background: #f39c12;
  color: white;
}

.status-badge.quote-accepted {
  background: #27ae60;
  color: white;
}

.status-badge.quote-rejected {
  background: #e74c3c;
  color: white;
}

.status-badge.role-reporting {
  background: #17a2b8;
  color: white;
}

.status-badge.role-controller {
  background: #28a745;
  color: white;
}

.status-badge.role-default {
  background: #6c757d;
  color: white;
}
</style>
