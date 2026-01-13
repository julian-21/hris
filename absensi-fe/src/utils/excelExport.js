// utils/excelExport.js
import * as XLSX from 'xlsx'

export function exportAttendanceReport(reportData) {
  if (!reportData || !reportData.data) {
    throw new Error('No data to export')
  }

  // Prepare data for Excel
  const excelData = reportData.data.map(item => ({
    'Tanggal': item.tanggal,
    'Hari': item.hari,
    'Status': item.status_label,
    'Check In': item.check_in || '-',
    'Check Out': item.check_out || '-',
    'Jadwal Masuk': item.jadwal_checkin || '-',
    'Jadwal Pulang': item.jadwal_checkout || '-',
    'Kantor': item.kantor_nama || '-',
    'Terlambat': item.is_late ? `Ya (${item.durasi_terlambat} menit)` : 'Tidak',
    'Out of Office': item.out_of_office ? 'Ya' : 'Tidak',
    'Keterangan': item.keterangan,
    'Jenis Cuti': item.leave_status?.leave_type_nama || '-',
    'Alasan Cuti': item.leave_status?.alasan || '-'
  }))

  // Add summary at the top
  const summaryData = [
    ['REKAP KEHADIRAN'],
    [''],
    ['Periode', `${reportData.start_date} s/d ${reportData.end_date}`],
    ['Karyawan', reportData.user?.name || 'Semua Karyawan'],
    [''],
    ['RINGKASAN'],
    ['Total Hari', reportData.summary.total_hari],
    ['Total Hadir', reportData.summary.total_hadir],
    ['Total Terlambat', reportData.summary.total_terlambat],
    ['Total Cuti', reportData.summary.total_cuti],
    ['Total Alpha', reportData.summary.total_alpha],
    ['Total Weekend', reportData.summary.total_weekend],
    ['Persentase Kehadiran', `${reportData.summary.persentase_kehadiran}%`],
    [''],
    ['DETAIL KEHADIRAN']
  ]

  // Create worksheet
  const ws = XLSX.utils.aoa_to_sheet(summaryData)
  XLSX.utils.sheet_add_json(ws, excelData, { origin: -1, skipHeader: false })

  // Set column widths
  ws['!cols'] = [
    { wch: 12 }, // Tanggal
    { wch: 10 }, // Hari
    { wch: 20 }, // Status
    { wch: 10 }, // Check In
    { wch: 10 }, // Check Out
    { wch: 12 }, // Jadwal Masuk
    { wch: 12 }, // Jadwal Pulang
    { wch: 15 }, // Kantor
    { wch: 15 }, // Terlambat
    { wch: 12 }, // Out of Office
    { wch: 30 }, // Keterangan
    { wch: 15 }, // Jenis Cuti
    { wch: 30 }  // Alasan Cuti
  ]

  // Create workbook
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Rekap Kehadiran')

  // Generate filename
  const filename = `Rekap_Kehadiran_${reportData.start_date}_${reportData.end_date}.xlsx`

  // Download
  XLSX.writeFile(wb, filename)
}

export function exportMonthlyReport(data) {
  // Implementation for monthly report export
  // Similar to exportAttendanceReport but with different structure
}