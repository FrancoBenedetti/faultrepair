<template>
  <CollapsibleSection
    title="User Management"
    icon="group"
    :expanded="expanded"
    @toggle="$emit('toggle')"
  >
    <template #header-actions>
      <button v-if="isAdmin" @click.stop="$emit('add-user')" class="btn-filled">
        <span class="material-icon-sm mr-2">person_add</span>
        Add New User
      </button>
    </template>

    <!-- Loading state -->
    <LoadingState v-if="!users" message="Loading users..." fullWidth />

    <!-- User cards -->
    <div v-else-if="users && users.length > 0" class="users-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <Card
        v-for="user in users"
        :key="user.id"
        clickable
        @click="$emit('view-user', user)"
      >
        <template #header>
          <div class="user-avatar w-12 h-12 bg-primary-600 rounded-full flex items-center justify-center">
            <span class="material-icon text-on-primary">{{ user.username.charAt(0).toUpperCase() }}</span>
          </div>
          <div class="user-actions flex gap-2" v-if="isAdmin">
            <button @click.stop="$emit('edit-user', user)" class="btn-outlined btn-small">
              <span class="material-icon-sm">edit</span>
            </button>
            <button v-if="canDeleteUser(user)" @click.stop="$emit('delete-user', user)" class="btn-outlined btn-small text-error-600 border-error-600 hover:bg-error-50">
              <span class="material-icon-sm">delete</span>
            </button>
          </div>
        </template>

        <template #content>
          <h3 class="user-name text-title-medium text-on-surface mb-2">{{ user.first_name }} {{ user.last_name }}</h3>
          <p class="user-email text-body-medium text-on-surface-variant mb-3">{{ user.email }}</p>
          <div class="user-role">
            <StatusBadge :status="user.role_name" type="role" />
          </div>
        </template>

        <template #footer>
          <div class="user-date text-label-medium text-on-surface-variant">
            Added {{ formatDate(user.created_at) }}
          </div>
        </template>
      </Card>
    </div>

    <!-- No users state -->
    <ErrorState
      v-else-if="users && users.length === 0"
      title="No users found"
      message="Get started by adding your first user."
      icon="group"
    />

    <!-- Error state -->
    <ErrorState
      v-else
      title="Failed to load users"
      message="Please try refreshing the page."
      icon="error"
    />
  </CollapsibleSection>
</template>

<script>
import CollapsibleSection from '@/components/shared/CollapsibleSection.vue'
import Card from '@/components/shared/Card.vue'
import LoadingState from '@/components/shared/LoadingState.vue'
import ErrorState from '@/components/shared/ErrorState.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'

export default {
  name: 'UserManagementSection',
  components: {
    CollapsibleSection,
    Card,
    LoadingState,
    ErrorState,
    StatusBadge
  },
  props: {
    expanded: {
      type: Boolean,
      default: false
    },
    users: {
      type: Array,
      default: null
    },
    isAdmin: {
      type: Boolean,
      default: false
    },
    currentUserId: {
      type: [Number, String],
      default: null
    }
  },
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString()
    },
    canDeleteUser(user) {
      // Prevent deletion of the current user and ensure user can be deleted
      return user.id !== this.currentUserId
    }
  }
}
</script>

<style scoped>
.users-grid {
  margin-top: 15px;
}

.user-avatar {
  flex-shrink: 0;
}

.user-name {
  margin: 0 0 8px 0;
}

.user-email {
  margin: 0 0 12px 0;
}

.user-role {
  margin-bottom: 0;
}

.user-date {
  margin: 0;
  text-align: left;
}

.btn-outlined {
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 0.9em;
  color: #666;
  transition: all 0.2s ease;
}

.btn-outlined:hover {
  background: #f0f0f0;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.9em;
}

@media (max-width: 768px) {
  .users-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }

  .user-avatar {
    width: 48px;
    height: 48px;
  }

  .user-name {
    font-size: 1em;
  }
}
</style>
