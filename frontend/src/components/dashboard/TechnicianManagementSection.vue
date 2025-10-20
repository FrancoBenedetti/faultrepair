<template>
  <CollapsibleSection
    title="Users"
    :expanded="expanded"
    :completeness="technicians?.length ? 'loaded' : null"
    @toggle="$emit('toggle')"
  >
    <template #header-actions>
      <button @click.stop="$emit('add-technician')" class="btn-filled flex items-center gap-2">
        <span class="material-icon-sm">person_add</span>
        Add User
      </button>
    </template>

    <div class="section-content transition-all duration-300 ease-in-out">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card
          v-for="technician in technicians"
          :key="technician.id"
          class="technician-card"
          @click="$emit('view-technician-jobs', technician)"
        >
          <template #header>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full flex items-center justify-center"
                   :class="technician.role_id === 3 ? 'bg-blue-600' : 'bg-purple-600'">
                <span class="material-icon text-white">{{
                  technician && technician.full_name ?
                    technician.full_name.charAt(0).toUpperCase() :
                    technician && technician.username ?
                    technician.username.charAt(0).toUpperCase() :
                    'T'
                }}</span>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-900">{{ technician.full_name || 'Unnamed User' }}</h3>
                <div class="flex gap-2">
                  <StatusBadge :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    technician.role_id === 3 ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                  ]">
                    {{ technician.role_id === 3 ? 'Administrator' : 'Technician' }}
                  </StatusBadge>
                  <StatusBadge :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    technician.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  ]">
                    {{ technician.is_active ? 'Active' : 'Inactive' }}
                  </StatusBadge>
                </div>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click.stop="$emit('edit-technician', technician)"
                      class="btn-round"
                      title="Edit User">
                <span class="material-icon-sm">edit</span>
              </button>
              <button @click.stop="$emit('delete-technician', technician)"
                      class="btn-round-filled"
                      title="Delete User">
                <span class="material-icon-sm">delete</span>
              </button>
            </div>
          </template>

          <div class="technician-info">
            <p class="text-sm text-gray-600 flex items-center gap-1 mb-2">
              <span class="material-icon-sm">email</span>
              {{ technician.email }}
            </p>
            <p v-if="technician.phone" class="text-sm text-gray-600 flex items-center gap-1">
              <span class="material-icon-sm">phone</span>
              {{ technician.phone }}
            </p>

            <!-- Technician stats (if user is admin) -->
            <div v-if="userRole === 3 && technician.role_id === 4" class="mt-3 pt-3 border-t border-gray-200">
              <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="text-center">
                  <div class="font-bold text-purple-600">{{ technician.active_jobs || 0 }}</div>
                  <div class="text-gray-500">Active</div>
                </div>
                <div class="text-center">
                  <div class="font-bold text-gray-600">{{ technician.completed_jobs || 0 }}</div>
                  <div class="text-gray-500">Completed</div>
                </div>
              </div>
            </div>
          </div>

          <template #footer>
            <p class="text-xs text-gray-500">
              Added {{ formatDate(technician.created_at) }}
            </p>
          </template>
        </Card>

        <!-- Add Technician Card -->
        <Card class="add-technician-card" @click="$emit('add-technician')">
          <div class="text-center py-8">
            <div class="mx-auto mb-4 w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <span class="material-icon text-purple-600">person_add</span>
            </div>
            <h3 class="font-medium text-gray-900 mb-2">Add User</h3>
            <p class="text-sm text-gray-600">Create a new technician account</p>
          </div>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-if="technicians.length === 0" class="text-center py-16">
        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 mb-6">
          <span class="material-icon text-gray-400">engineering</span>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Users Yet</h3>
        <p class="text-gray-600 mb-6">Add technicians to help manage your jobs and assignments.</p>
        <button @click="$emit('add-technician')" class="btn-filled flex items-center gap-2">
          <span class="material-icon-sm">person_add</span>
          Add Your First User
        </button>
      </div>
    </div>
  </CollapsibleSection>
</template>

<script>
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import Card from '@/components/shared/Card.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'TechnicianManagementSection',
  components: {
    CollapsibleSection,
    Card,
    StatusBadge
  },
  props: {
    expanded: {
      type: Boolean,
      default: false
    },
    technicians: {
      type: Array,
      default: () => []
    },
    userRole: {
      type: Number,
      required: true
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.technician-card:hover {
  transform: translateY(-1px);
}

.add-technician-card {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px dashed #d1d5db;
  background: #f9fafb;
}

.add-technician-card:hover {
  border-color: #a855f7;
  background: #faf5ff;
}

.btn-round {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid #d1d5db;
  background: white;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-round:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.btn-round-filled {
  @apply btn-round;
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.btn-round-filled:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}

.technician-info {
  padding: 1rem 0;
}
</style>
