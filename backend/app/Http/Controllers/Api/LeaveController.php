<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Helpers\NotificationHelper; // ✅ TAMBAHKAN INI

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['user', 'leaveType', 'approver']);

        // ✅ ALWAYS filter by user yang login untuk non-admin
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            $query->where('user_id', $request->user()->id);
        } else {
            // ✅ Admin juga hanya lihat cuti SENDIRI di tab ini
            // Kecuali ada parameter user_id untuk keperluan khusus
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            } else {
                // Default: Admin juga lihat cuti sendiri
                $query->where('user_id', $request->user()->id);
            }
        }

        // Filter by status - handle 'all'
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by leave_type_id - handle 'all'
        if ($request->has('leave_type_id') && $request->leave_type_id !== 'all') {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inRange($request->start_date, $request->end_date);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->whereYear('tanggal_mulai', $request->year);
        }

        $leaves = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($leaves);
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'tipe_durasi' => 'required|in:sehari,setengah_hari,lebih_dari_sehari',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'setengah_hari_tipe' => 'required_if:tipe_durasi,setengah_hari|nullable|in:pagi,siang',
            'alasan' => 'required|string|min:10',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);

        // Validasi khusus untuk tipe durasi
        if (in_array($request->tipe_durasi, ['sehari', 'setengah_hari'])) {
            if ($request->tanggal_mulai !== $request->tanggal_selesai) {
                return response()->json([
                    'message' => 'Untuk cuti sehari/setengah hari, tanggal mulai dan selesai harus sama'
                ], 422);
            }
        }

        // Validasi dokumen untuk jenis cuti tertentu
        if ($leaveType->isRequiresDocument() && !$request->hasFile('dokumen_pendukung')) {
            return response()->json([
                'message' => 'Jenis cuti ini memerlukan dokumen pendukung'
            ], 422);
        }

        // Cek overlap
        $leave = new Leave();
        if ($leave->isOverlapping($request->user()->id, $request->tanggal_mulai, $request->tanggal_selesai)) {
            return response()->json([
                'message' => 'Anda sudah memiliki pengajuan cuti pada tanggal tersebut'
            ], 422);
        }

        // Hitung jumlah hari
        $jumlahHari = Leave::hitungJumlahHari(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            $request->tipe_durasi,
            $request->setengah_hari_tipe
        );

        // Cek sisa kuota cuti (optional)
        $sisaCuti = $this->getSisaCuti($request->user()->id, $request->leave_type_id);
        if ($sisaCuti !== null && $jumlahHari > $sisaCuti) {
            return response()->json([
                'message' => "Sisa kuota cuti {$leaveType->nama} Anda tidak mencukupi. Sisa: {$sisaCuti} hari"
            ], 422);
        }

        // Upload dokumen jika ada
        $dokumenPath = null;
        if ($request->hasFile('dokumen_pendukung')) {
            $dokumenPath = $request->file('dokumen_pendukung')->store('leave-documents', 'public');
        }

        $leave = Leave::create([
            'user_id' => $request->user()->id,
            'leave_type_id' => $request->leave_type_id,
            'tipe_durasi' => $request->tipe_durasi,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'setengah_hari_tipe' => $request->setengah_hari_tipe,
            'jumlah_hari' => $jumlahHari,
            'alasan' => $request->alasan,
            'dokumen_pendukung' => $dokumenPath,
            'status' => 'pending',
        ]);

        // ✅ NOTIFIKASI: Kirim ke approver (Admin/HR/Direktur)
        $approvers = User::role(['Admin', 'HR', 'Direktur'])->pluck('id')->toArray();
        NotificationHelper::createForMultiple(
            $approvers,
            'leave_submitted',
            'Pengajuan Cuti Baru',
            "{$request->user()->name} mengajukan cuti {$leaveType->nama} untuk {$jumlahHari} hari ({$request->tanggal_mulai} - {$request->tanggal_selesai}).",
            [
                'leave_id' => $leave->id, 
                'employee_name' => $request->user()->name,
                'leave_type' => $leaveType->nama,
                'jumlah_hari' => $jumlahHari
            ]
        );

        // ✅ NOTIFIKASI: Kirim ke user sendiri (konfirmasi)
        NotificationHelper::create(
            $request->user()->id,
            'leave_submitted',
            'Pengajuan Cuti Berhasil',
            "Pengajuan cuti {$leaveType->nama} Anda telah diterima dan menunggu persetujuan.",
            ['leave_id' => $leave->id]
        );

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dibuat',
            'leave' => $leave->load(['user', 'leaveType', 'approver'])
        ], 201);
    }

    public function show($id)
    {
        $leave = Leave::with(['user', 'leaveType', 'approver'])->findOrFail($id);

        // Authorization check
        if (!request()->user()->hasAnyRole(['Admin', 'HR', 'Direktur']) 
            && $leave->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($leave);
    }

    public function update(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        if (!$leave->canBeEdited()) {
            return response()->json([
                'message' => 'Hanya cuti dengan status pending yang bisa diubah'
            ], 422);
        }

        if ($leave->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'tipe_durasi' => 'required|in:sehari,setengah_hari,lebih_dari_sehari',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'setengah_hari_tipe' => 'required_if:tipe_durasi,setengah_hari|nullable|in:pagi,siang',
            'alasan' => 'required|string|min:10',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Cek overlap (exclude current leave)
        if ($leave->isOverlapping($request->user()->id, $request->tanggal_mulai, $request->tanggal_selesai, $id)) {
            return response()->json([
                'message' => 'Anda sudah memiliki pengajuan cuti pada tanggal tersebut'
            ], 422);
        }

        $leaveType = LeaveType::findOrFail($request->leave_type_id);

        $jumlahHari = Leave::hitungJumlahHari(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
            $request->tipe_durasi,
            $request->setengah_hari_tipe
        );

        // Upload dokumen baru jika ada
        $dokumenPath = $leave->dokumen_pendukung;
        if ($request->hasFile('dokumen_pendukung')) {
            if ($dokumenPath) {
                Storage::disk('public')->delete($dokumenPath);
            }
            $dokumenPath = $request->file('dokumen_pendukung')->store('leave-documents', 'public');
        }

        $leave->update([
            'leave_type_id' => $request->leave_type_id,
            'tipe_durasi' => $request->tipe_durasi,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'setengah_hari_tipe' => $request->setengah_hari_tipe,
            'jumlah_hari' => $jumlahHari,
            'alasan' => $request->alasan,
            'dokumen_pendukung' => $dokumenPath,
        ]);

        // ✅ NOTIFIKASI: Kirim ke approver
        $approvers = User::role(['Admin', 'HR', 'Direktur'])->pluck('id')->toArray();
        NotificationHelper::createForMultiple(
            $approvers,
            'leave_updated',
            'Pengajuan Cuti Diperbarui',
            "{$request->user()->name} memperbarui pengajuan cuti {$leaveType->nama}.",
            [
                'leave_id' => $leave->id, 
                'employee_name' => $request->user()->name,
                'leave_type' => $leaveType->nama
            ]
        );

        return response()->json([
            'message' => 'Pengajuan cuti berhasil diupdate',
            'leave' => $leave->load(['user', 'leaveType', 'approver'])
        ]);
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);

        if (!$leave->canBeDeleted()) {
            return response()->json([
                'message' => 'Hanya cuti dengan status pending yang bisa dihapus'
            ], 422);
        }

        if ($leave->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($leave->dokumen_pendukung) {
            Storage::disk('public')->delete($leave->dokumen_pendukung);
        }

        $leave->delete();

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dihapus'
        ]);
    }

    public function approve(Request $request, $id)
    {
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $leave = Leave::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json([
                'message' => 'Cuti ini sudah diproses sebelumnya'
            ], 422);
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $request->catatan_approval,
        ]);

        // ✅ NOTIFIKASI: Kirim ke employee
        NotificationHelper::create(
            $leave->user_id,
            'leave_approved',
            'Cuti Disetujui',
            "Pengajuan cuti {$leave->leaveType->nama} Anda dari {$leave->tanggal_mulai} sampai {$leave->tanggal_selesai} telah disetujui.",
            [
                'leave_id' => $leave->id, 
                'approved_by' => $request->user()->name,
                'catatan' => $request->catatan_approval
            ]
        );

        return response()->json([
            'message' => 'Pengajuan cuti berhasil disetujui',
            'leave' => $leave->load(['user', 'leaveType', 'approver'])
        ]);
    }

    public function reject(Request $request, $id)
    {
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'catatan_approval' => 'required|string|min:10',
        ]);

        $leave = Leave::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json([
                'message' => 'Cuti ini sudah diproses sebelumnya'
            ], 422);
        }

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $request->catatan_approval,
        ]);

        // ✅ NOTIFIKASI: Kirim ke employee
        NotificationHelper::create(
            $leave->user_id,
            'leave_rejected',
            'Cuti Ditolak',
            "Pengajuan cuti {$leave->leaveType->nama} Anda dari {$leave->tanggal_mulai} sampai {$leave->tanggal_selesai} telah ditolak. Alasan: {$request->catatan_approval}",
            [
                'leave_id' => $leave->id, 
                'rejected_by' => $request->user()->name,
                'catatan' => $request->catatan_approval
            ]
        );

        return response()->json([
            'message' => 'Pengajuan cuti ditolak',
            'leave' => $leave->load(['user', 'leaveType', 'approver'])
        ]);
    }

    public function statistics(Request $request)
    {
        $userId = $request->user_id ?? $request->user()->id;
        $year = $request->year ?? Carbon::now()->year;

        $stats = [
            'total_cuti_diajukan' => Leave::byUser($userId)
                ->whereYear('tanggal_mulai', $year)
                ->sum('jumlah_hari'),
            
            'total_cuti_disetujui' => Leave::byUser($userId)
                ->whereYear('tanggal_mulai', $year)
                ->approved()
                ->sum('jumlah_hari'),
            
            'total_cuti_pending' => Leave::byUser($userId)
                ->whereYear('tanggal_mulai', $year)
                ->pending()
                ->count(),
            
            'total_cuti_ditolak' => Leave::byUser($userId)
                ->whereYear('tanggal_mulai', $year)
                ->rejected()
                ->count(),
            
            'breakdown_jenis' => Leave::byUser($userId)
                ->whereYear('tanggal_mulai', $year)
                ->approved()
                ->with('leaveType')
                ->get()
                ->groupBy('leave_type_id')
                ->map(function($items) {
                    return [
                        'jenis' => $items->first()->leaveType->nama,
                        'total_hari' => $items->sum('jumlah_hari'),
                        'jumlah_pengajuan' => $items->count(),
                    ];
                })
                ->values(),

            'kuota_per_jenis' => $this->getKuotaPerJenis($userId, $year),
        ];

        return response()->json($stats);
    }

    public function getLeaveTypes()
    {
        $leaveTypes = LeaveType::active()->orderBy('nama')->get();
        return response()->json($leaveTypes);
    }

    private function getSisaCuti($userId, $leaveTypeId)
    {
        $leaveType = LeaveType::find($leaveTypeId);
        
        if (!$leaveType || $leaveType->jumlah_hari == 0) {
            return null; // Unlimited atau tidak ada kuota
        }

        $year = Carbon::now()->year;
        $terpakai = Leave::byUser($userId)
            ->byLeaveType($leaveTypeId)
            ->whereYear('tanggal_mulai', $year)
            ->approved()
            ->sum('jumlah_hari');

        return $leaveType->jumlah_hari - $terpakai;
    }

    private function getKuotaPerJenis($userId, $year)
    {
        $leaveTypes = LeaveType::active()->get();
        $kuota = [];

        foreach ($leaveTypes as $type) {
            if ($type->jumlah_hari > 0) {
                $terpakai = Leave::byUser($userId)
                    ->byLeaveType($type->id)
                    ->whereYear('tanggal_mulai', $year)
                    ->approved()
                    ->sum('jumlah_hari');

                $kuota[] = [
                    'leave_type_id' => $type->id,
                    'nama' => $type->nama,
                    'kuota' => $type->jumlah_hari,
                    'terpakai' => $terpakai,
                    'sisa' => $type->jumlah_hari - $terpakai,
                ];
            }
        }

        return $kuota;
    }

    public function pendingApprovals(Request $request)
    {
        // Hanya untuk approver
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = Leave::with(['user', 'leaveType'])
            ->where('status', 'pending');
            // ✅ HAPUS filter exclude user sendiri - Admin boleh approve cuti sendiri
        
        // Filter by leave_type_id - handle 'all'
        if ($request->has('leave_type_id') && $request->leave_type_id !== 'all') {
            $query->where('leave_type_id', $request->leave_type_id);
        }
        
        // Filter by year
        if ($request->has('year')) {
            $query->whereYear('tanggal_mulai', $request->year);
        }
        
        // Filter by user (search)
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        $leaves = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
        
        return response()->json($leaves);
    }
}