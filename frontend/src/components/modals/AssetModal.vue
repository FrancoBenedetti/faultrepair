<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center" @click.self="close">
    <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
      <div class="modal-header flex justify-between items-center pb-3 border-b">
        <h3 class="text-lg font-medium">{{ formTitle }}</h3>
        <button @click="close" class="modal-close-btn">&times;</button>
      </div>
      <div class="modal-body py-4">
        <form @submit.prevent="submitForm">
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label for="asset_no" class="block text-sm font-medium text-gray-700">Asset Number</label>
              <input type="text" id="asset_no" v-model="form.asset_no" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
            </div>
            <div>
              <label for="item" class="block text-sm font-medium text-gray-700">Item</label>
              <input type="text" id="item" v-model="form.item" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
            </div>
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea id="description" v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            </div>
            <div>
              <label for="location_id" class="block text-sm font-medium text-gray-700">Location</label>
              <select id="location_id" v-model="form.location_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select Location</option>
                <option v-for="location in locations" :key="location.id" :value="location.id">{{ location.name }}</option>
              </select>
            </div>
            <div>
              <label for="manager_id" class="block text-sm font-medium text-gray-700">Manager</label>
              <select id="manager_id" v-model="form.manager_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select Manager</option>
                <option v-for="manager in managers" :key="manager.id" :value="manager.id">{{ manager.first_name }} {{ manager.last_name }}</option>
              </select>
            </div>
            <div>
              <label for="sp_id" class="block text-sm font-medium text-gray-700">Service Provider</label>
              <select id="sp_id" v-model="form.sp_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select Service Provider</option>
                <option v-for="provider in serviceProviders" :key="provider.id" :value="provider.id">{{ provider.name }}</option>
              </select>
            </div>
            <div>
              <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
              <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="unavailable">Unavailable</option>
                <option value="decommissioned">Decommissioned</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer flex justify-end pt-4 border-t">
        <button @click="close" type="button" class="btn-secondary mr-2">Cancel</button>
        <button @click="submitForm" type="submit" class="btn-primary">Save</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AssetModal',
  props: {
    asset: {
      type: Object,
      default: null,
    },
    serviceProviders: {
      type: Array,
      default: () => [],
    },
    locations: {
      type: Array,
      default: () => [],
    },
    managers: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      form: {
        id: null,
        asset_no: '',
        item: '',
        description: '',
        location_id: '',
        manager_id: '',
        sp_id: '',
        status: 'active',
      },
    };
  },
  computed: {
    formTitle() {
      return this.asset ? 'Edit Asset' : 'Add Asset';
    },
  },
  watch: {
    asset: {
      handler(newVal) {
        if (newVal) {
          this.form = { ...newVal };
        } else {
          this.resetForm();
        }
      },
      immediate: true,
    },
  },
  methods: {
    close() {
      this.$emit('close');
    },
    submitForm() {
      this.$emit('save', this.form);
    },
    resetForm() {
      this.form = {
        id: null,
        asset_no: '',
        item: '',
        description: '',
        location_id: '',
        manager_id: '',
        sp_id: '',
        status: 'active',
      };
    },
  },
};
</script>

<style scoped>
.modal-close-btn {
    font-size: 1.5rem;
    font-weight: bold;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    opacity: .5;
    background: transparent;
    border: 0;
}
</style>
