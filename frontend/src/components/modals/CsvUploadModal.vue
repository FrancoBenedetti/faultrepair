<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center" @click.self="close">
    <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
      <div class="modal-header flex justify-between items-center pb-3 border-b">
        <h3 class="text-lg font-medium">Upload Assets CSV</h3>
        <button @click="close" class="modal-close-btn">&times;</button>
      </div>
      <div class="modal-body py-4">
        <p class="text-sm text-gray-600 mb-4">
          Upload a CSV file to add or update assets. The CSV must contain a header row with the following columns:
          <code>asset_no</code> (required), <code>item</code> (required), <code>description</code>, <code>location_id</code> (numeric ID), <code>manager_id</code> (numeric ID), <code>sp_id</code> (numeric ID).
        </p>
        <input type="file" ref="csvFile" @change="handleFileChange" accept=".csv" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        <p v-if="fileName" class="mt-2 text-sm text-gray-700">Selected file: {{ fileName }}</p>
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
      </div>
      <div class="modal-footer flex justify-end pt-4 border-t">
        <button @click="close" type="button" class="btn-secondary mr-2">Cancel</button>
        <button @click="uploadCsv" type="button" class="btn-primary" :disabled="!selectedFile || uploading">
          <span v-if="uploading">Uploading...</span>
          <span v-else>Upload</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { apiFetch } from '@/utils/api.js';

export default {
  name: 'CsvUploadModal',
  data() {
    return {
      selectedFile: null,
      fileName: '',
      uploading: false,
      error: '',
    };
  },
  methods: {
    handleFileChange(event) {
      this.selectedFile = event.target.files[0];
      this.fileName = this.selectedFile ? this.selectedFile.name : '';
      this.error = '';
    },
    async uploadCsv() {
      if (!this.selectedFile) {
        this.error = 'Please select a CSV file to upload.';
        return;
      }

      this.uploading = true;
      this.error = '';

      const formData = new FormData();
      formData.append('csv_file', this.selectedFile);

      try {
        const response = await apiFetch('/backend/api/upload-assets-csv.php', {
          method: 'POST',
          body: formData,
          headers: {
            // Do NOT set Content-Type header for FormData, browser sets it automatically with boundary
          },
        });

        console.log('CSV Upload API Response:', response);

        if (response.ok) {
          const data = await response.json();
          console.log('CSV Upload Success Data:', data);
          alert(data.message || 'CSV uploaded successfully!');
          this.$emit('uploadSuccess');
          this.close();
        } else {
          const errorData = await response.json();
          console.error('CSV Upload Error Data:', errorData);
          this.error = errorData.error || 'Failed to upload CSV.';
        }
      } catch (error) {
        console.error('Error uploading CSV:', error);
        this.error = 'An error occurred during upload.';
      } finally {
        this.uploading = false;
      }
    },
    close() {
      this.selectedFile = null;
      this.fileName = '';
      this.uploading = false;
      this.error = '';
      this.$emit('close');
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
