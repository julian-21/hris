import { ref } from 'vue'

export function useGeolocation() {
  const coordinates = ref(null)
  const error = ref(null)
  const loading = ref(false)

  const getCurrentPosition = () => {
    return new Promise((resolve, reject) => {
      loading.value = true
      error.value = null

      if (!navigator.geolocation) {
        const err = new Error('Geolocation tidak didukung oleh browser Anda')
        error.value = err.message
        loading.value = false
        reject(err)
        return
      }

      // Try with high accuracy first
      let highAccuracyAttempted = false
      
      const options = {
        enableHighAccuracy: true,
        timeout: 30000, // Increased to 30 seconds
        maximumAge: 0
      }

      const successCallback = (position) => {
        coordinates.value = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy
        }
        loading.value = false
        console.log('✅ Location obtained with accuracy:', position.coords.accuracy, 'meters')
        resolve(coordinates.value)
      }

      const errorCallback = (err) => {
        console.error('❌ Geolocation error:', err)
        
        // If high accuracy failed and we haven't tried low accuracy yet
        if (options.enableHighAccuracy && !highAccuracyAttempted) {
          console.log('🔄 Retrying with low accuracy mode...')
          highAccuracyAttempted = true
          
          // Retry with low accuracy
          navigator.geolocation.getCurrentPosition(
            successCallback,
            (lowAccErr) => {
              handleFinalError(lowAccErr)
            },
            {
              enableHighAccuracy: false,
              timeout: 15000,
              maximumAge: 5000 // Accept cached location up to 5 seconds old
            }
          )
          return
        }
        
        handleFinalError(err)
      }

      const handleFinalError = (err) => {
        let message = 'Tidak dapat mengambil lokasi Anda'
        
        switch (err.code) {
          case err.PERMISSION_DENIED:
            message = 'Izin lokasi ditolak. Mohon aktifkan izin lokasi di pengaturan browser.'
            break
          case err.POSITION_UNAVAILABLE:
            message = 'Informasi lokasi tidak tersedia. Pastikan GPS/Location Services aktif.'
            break
          case err.TIMEOUT:
            message = 'Permintaan lokasi timeout. Pastikan Anda berada di area dengan sinyal GPS yang baik, atau coba lagi.'
            break
          default:
            message = `Error: ${err.message}`
        }
        
        error.value = message
        loading.value = false
        reject(new Error(message))
      }

      // Start with high accuracy attempt
      navigator.geolocation.getCurrentPosition(
        successCallback,
        errorCallback,
        options
      )
    })
  }

  const watchPosition = (callback) => {
    if (!navigator.geolocation) {
      error.value = 'Geolocation tidak didukung'
      return null
    }

    return navigator.geolocation.watchPosition(
      (position) => {
        coordinates.value = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy
        }
        callback(coordinates.value)
      },
      (err) => {
        error.value = err.message
      },
      {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 0
      }
    )
  }

  const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371000 // Radius bumi dalam meter
    const φ1 = lat1 * Math.PI / 180
    const φ2 = lat2 * Math.PI / 180
    const Δφ = (lat2 - lat1) * Math.PI / 180
    const Δλ = (lon2 - lon1) * Math.PI / 180

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

    return R * c // Jarak dalam meter
  }

  return {
    coordinates,
    error,
    loading,
    getCurrentPosition,
    watchPosition,
    calculateDistance
  }
}