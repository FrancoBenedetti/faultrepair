<template>
  <div class="qr-scanner">
    <!-- QR Scanner Modal -->
    <div v-if="showScanner" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75" @click="closeScanner">
      <div class="w-full max-w-md mx-auto bg-white rounded-lg shadow-xl overflow-hidden" @click.stop>
        <div class="modal-header">
          <h3 class="text-xl font-semibold text-gray-900 mb-0">Scan QR Code</h3>
          <button type="button" @click="closeScanner" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div class="modal-body p-6">
          <div class="scanner-container">
            <video ref="cameraVideo" autoplay playsinline class="camera-video w-full h-64 bg-black rounded-lg"></video>
            <canvas ref="cameraCanvas" class="hidden"></canvas>
            <div class="scanner-controls mt-4 flex gap-3 justify-center">
              <button type="button" @click="captureAndScan" class="btn-filled" :disabled="!cameraStream">
                <span class="material-icon-sm mr-2">qr_code_scanner</span>
                Capture & Scan
              </button>
              <button type="button" @click="stopScanning" class="btn-outlined">
                Cancel
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" @click="closeScanner" class="btn-outlined">Close</button>
        </div>
      </div>
    </div>

    <!-- Trigger Button (to be used externally) -->
    <button type="button" @click="openScanner" class="btn-outlined btn-small">
      <span class="material-icon-sm mr-2">qr_code_scanner</span>
      Scan QR
    </button>
  </div>
</template>

<script>
import { Html5Qrcode } from 'html5-qrcode'
import jsQR from 'jsqr'

export default {
  name: 'QrScanner',
  props: {
    clientId: {
      type: Number,
      required: true
    }
  },
  emits: ['qr-detected'],
  data() {
    return {
      showScanner: false,
      cameraStream: null,
      isScanning: false,
      lastScannedData: null
    }
  },
  beforeUnmount() {
    this.stopCamera()
  },
  methods: {
    openScanner() {
      this.showScanner = true
      this.$nextTick(() => {
        this.startCamera()
      })
    },

    closeScanner() {
      this.stopCamera()
      this.showScanner = false
    },

    async startCamera() {
      try {
        // Clear any previous errors
        // Use the exact same camera constraints as the working ImageUpload component
        let constraints = { video: { facingMode: 'environment' } }

        try {
          this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints)
        } catch (firstError) {
          console.log('Back camera not available, trying any camera:', firstError.message)
          // Fall back to any available camera
          constraints = { video: true }
          this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints)
        }

        // Show the modal first to ensure video element is rendered
        this.showScanner = true

        // Use nextTick to ensure DOM is updated before setting video source
        await this.$nextTick()

        // Now set the video source
        const video = this.$refs.cameraVideo
        if (video) {
          video.srcObject = this.cameraStream
          console.log('Camera opened successfully for QR scanning')
        } else {
          throw new Error('Video element not found')
        }
      } catch (error) {
        console.error('Error accessing camera for QR scanning:', error)

        // Provide more specific error messages
        let errorMessage = 'Unable to access camera for QR scanning.'
        if (error.name === 'NotAllowedError') {
          errorMessage += ' Camera permission was denied. Please allow camera access and try again.'
        } else if (error.name === 'NotFoundError') {
          errorMessage += ' No camera found on this device.'
        } else if (error.name === 'NotReadableError') {
          errorMessage += ' Camera is already in use by another application.'
        } else if (error.name === 'OverconstrainedError') {
          errorMessage += ' Camera constraints cannot be satisfied.'
        } else {
          errorMessage += ` ${error.message}`
        }

        alert(errorMessage)
        this.closeScanner()
      }
    },

    stopCamera() {
      if (this.cameraStream) {
        this.cameraStream.getTracks().forEach(track => track.stop())
        this.cameraStream = null
      }
    },

    async captureAndScan() {
      if (!this.cameraStream) {
        alert('Camera not available')
        return
      }

      this.isScanning = true

      try {
        const video = this.$refs.cameraVideo
        const canvas = this.$refs.cameraCanvas
        const context = canvas.getContext('2d')

        // Set canvas size to video size
        canvas.width = video.videoWidth
        canvas.height = video.videoHeight

        // Draw the current video frame to canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height)

        // Get image data directly from canvas for QR scanning
        try {
          console.log('Attempting to scan QR code from captured image...')

          // Get image data from canvas
          const imageData = context.getImageData(0, 0, canvas.width, canvas.height)

          // Use jsQR to scan the image data directly
          const qrResult = jsQR(imageData.data, imageData.width, imageData.height)

          if (qrResult) {
            console.log('QR code detected with jsQR:', qrResult.data)
            this.handleQrDetection(qrResult.data)
          } else {
            console.log('No QR code found in image data')
            alert('No QR code found in the captured image. Please ensure the QR code is clearly visible and centered in the camera view, then try again.')
          }
        } catch (scanError) {
          console.error('QR scan error:', scanError)
          alert('Could not detect QR code. Please ensure the QR code is clearly visible and try again. Error: ' + scanError.message)
        } finally {
          this.isScanning = false
        }
      } catch (error) {
        console.error('Error capturing image:', error)
        alert('Error capturing image for scanning.')
        this.isScanning = false
      }
    },

    handleQrDetection(qrData) {
      // Prevent duplicate processing of the same QR code
      if (this.lastScannedData === qrData) {
        return
      }

      this.lastScannedData = qrData

      try {
        const parsedData = this.parseQrData(qrData)

        if (parsedData) {
          // Validate client ID matches current user
          if (parsedData.clientId !== this.clientId) {
            alert(`QR code is for a different client (ID: ${parsedData.clientId}). This QR code is not valid for your account.`)
            return
          }

          // Stop camera and emit the data
          this.stopCamera()
          this.closeScanner()

          this.$emit('qr-detected', parsedData)
        } else {
          alert('Invalid QR code format. Expected format: CLIENT:{id}|ITEM:{identifier}|LOCATION:{name} or CLIENT:{id}|ITEM:{identifier}|LOCATION:{name}|URL:{siteurl}')
        }
      } catch (error) {
        console.error('Error parsing QR data:', error)
        alert('Error reading QR code. Please try again.')
      }
    },

    parseQrData(qrData) {
      // Expected formats:
      // CLIENT:{client_id}|ITEM:{item_identifier}|LOCATION:{location_name}
      // or
      // CLIENT:{client_id}|ITEM:{item_identifier}|LOCATION:{location_name}|URL:{siteurl}
      const parts = qrData.split('|')

      if (parts.length < 3 || parts.length > 4) {
        return null
      }

      const clientPart = parts[0].trim()
      const itemPart = parts[1].trim()
      const locationPart = parts[2].trim()

      if (!clientPart.startsWith('CLIENT:') ||
          !itemPart.startsWith('ITEM:') ||
          !locationPart.startsWith('LOCATION:')) {
        return null
      }

      const clientId = parseInt(clientPart.substring(7)) // Remove 'CLIENT:'
      const itemIdentifier = itemPart.substring(5) // Remove 'ITEM:'
      const locationName = locationPart.substring(9) // Remove 'LOCATION:'

      if (isNaN(clientId) || !itemIdentifier.trim()) {
        return null
      }

      // Check for optional URL field
      let siteUrl = null
      if (parts.length === 4) {
        const urlPart = parts[3].trim()
        if (urlPart.startsWith('URL:')) {
          siteUrl = urlPart.substring(4).trim() // Remove 'URL:'
        } else {
          // If there's a 4th part but it's not a URL, invalid format
          return null
        }
      }

      return {
        clientId: clientId,
        itemIdentifier: itemIdentifier.trim(),
        locationName: locationName.trim(),
        siteUrl: siteUrl // Optional field, may be null
      }
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
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: flex-end;
}

.scanner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.scanner-element {
  width: 100%;
  max-width: 400px;
}

.btn-filled, .btn-outlined {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-filled {
  background: #007bff;
  color: white;
}

.btn-filled:hover:not(:disabled) {
  background: #0056b3;
}

.btn-filled:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-outlined {
  background: white;
  color: #007bff;
  border: 1px solid #007bff;
}

.btn-outlined:hover {
  background: #f8f9fa;
}

.btn-small {
  padding: 6px 12px;
  font-size: 0.9em;
}
</style>
