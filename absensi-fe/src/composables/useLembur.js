import { useToast } from '@/composables/useToast'

export function useLembur() {
  const { toast } = useToast()

  const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    })
  }

  const formatMinutes = (minutes) => {
    if (!minutes || minutes === 0) return '0 menit'
    
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    
    if (hours > 0 && mins > 0) {
      return `${hours}j ${mins}m`
    } else if (hours > 0) {
      return `${hours} jam`
    } else {
      return `${mins} menit`
    }
  }

  const getStatusBadgeVariant = (status) => {
    const variants = {
      'waiting': 'warning',
      'approved': 'success',     // ✅ Fixed: approved bukan accepted
      'accepted': 'success',     // Fallback untuk backward compatibility
      'rejected': 'destructive'
    }
    return variants[status] || 'secondary'
  }

  const getStatusLabel = (status) => {
    const labels = {
      'waiting': 'Menunggu',
      'approved': 'Disetujui',   // ✅ Fixed: approved bukan accepted
      'accepted': 'Diterima',    // Fallback untuk backward compatibility
      'rejected': 'Ditolak'
    }
    return labels[status] || status
  }

  const showSuccessToast = (message) => {
    toast({
      title: 'Berhasil',
      description: message,
      variant: 'default'
    })
  }

  const showErrorToast = (message) => {
    toast({
      title: 'Error',
      description: message,
      variant: 'destructive'
    })
  }

  const handleApiError = (error) => {
    const message = error.response?.data?.message || 'Terjadi kesalahan'
    showErrorToast(message)
    return message
  }

  return {
    formatDate,
    formatMinutes,
    getStatusBadgeVariant,
    getStatusLabel,
    showSuccessToast,
    showErrorToast,
    handleApiError
  }
}