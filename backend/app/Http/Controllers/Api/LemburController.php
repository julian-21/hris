<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\NotificationHelper; // ✅ TAMBAHKAN INI

class LemburController extends Controller
{
    /**
     * Display a listing of lembur
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Lembur::with(['user:id,name,email,posisi,company']);

            // Jika bukan admin/HR, hanya tampilkan lembur sendiri
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                $query->where('user_id', $user->id);
            }

            // Filter by specific user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by final_status
            if ($request->has('final_status') && $request->final_status !== 'all') {
                $query->where('final_status', $request->final_status);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('tanggal_lembur', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            // Filter by month/year
            if ($request->has('month') && $request->has('year')) {
                $query->whereMonth('tanggal_lembur', $request->month)
                      ->whereYear('tanggal_lembur', $request->year);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('alasan_lembur', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'tanggal_lembur');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $lemburs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data lembur berhasil diambil',
                'data' => $lemburs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created lembur
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_lembur' => 'required|date',
            'lama_lembur' => 'required|integer|min:1',
            'alasan_lembur' => 'required|string|max:1000',
        ], [
            'tanggal_lembur.required' => 'Tanggal lembur wajib diisi',
            'tanggal_lembur.date' => 'Format tanggal tidak valid',
            'lama_lembur.required' => 'Durasi lembur wajib diisi',
            'lama_lembur.integer' => 'Durasi lembur harus berupa angka',
            'lama_lembur.min' => 'Durasi lembur minimal 1 menit',
            'alasan_lembur.required' => 'Alasan lembur wajib diisi',
            'alasan_lembur.max' => 'Alasan lembur maksimal 1000 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $lembur = Lembur::create([
                'tanggal_lembur' => $request->tanggal_lembur,
                'lama_lembur' => $request->lama_lembur,
                'alasan_lembur' => $request->alasan_lembur,
                'user_id' => $user->id,
                'status' => Lembur::STATUS_WAITING,
                'sisa_waktu_claim' => $request->lama_lembur, 
                'final_status' => Lembur::STATUS_WAITING,
            ]);

            $lembur->load('user:id,name,email,posisi,company');

            // ✅ NOTIFIKASI: Kirim ke approver
            $approvers = User::role(['Admin', 'HR', 'Direktur'])->pluck('id')->toArray();
            NotificationHelper::createForMultiple(
                $approvers,
                'lembur_submitted',
                'Pengajuan Lembur Baru',
                "{$user->name} mengajukan lembur selama {$request->lama_lembur} menit pada {$request->tanggal_lembur}.",
                [
                    'lembur_id' => $lembur->id,
                    'employee_name' => $user->name,
                    'lama_lembur' => $request->lama_lembur
                ]
            );

            // ✅ NOTIFIKASI: Konfirmasi ke user
            NotificationHelper::create(
                $user->id,
                'lembur_submitted',
                'Pengajuan Lembur Berhasil',
                "Pengajuan lembur Anda telah diterima dan menunggu persetujuan.",
                ['lembur_id' => $lembur->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil dibuat',
                'data' => $lembur
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengajuan lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified lembur
     */
    public function show($id)
    {
        try {
            $lembur = Lembur::with(['user:id,name,email,posisi,company,phone'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detail lembur berhasil diambil',
                'data' => $lembur
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lembur tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified lembur
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_lembur' => 'sometimes|date',
            'lama_lembur' => 'sometimes|integer|min:1',
            'alasan_lembur' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lembur = Lembur::findOrFail($id);
            $user = Auth::user();

            // Check ownership
            if ($lembur->user_id !== $user->id && !$user->hasAnyRole(['Admin', 'HR'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah lembur ini'
                ], 403);
            }

            // Check if can be edited
            if (!$lembur->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lembur yang sudah disetujui/ditolak tidak dapat diubah'
                ], 403);
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'tanggal_lembur',
                'lama_lembur',
                'alasan_lembur',
            ]);

            $lembur->update($updateData);

            // Update sisa_waktu_claim jika lama_lembur berubah
            if ($request->has('lama_lembur')) {
                $lembur->sisa_waktu_claim = $request->lama_lembur;
                $lembur->save();
            }

            $lembur->load('user:id,name,email,posisi,company');

            // ✅ NOTIFIKASI: Kirim ke approver
            $approvers = User::role(['Admin', 'HR', 'Direktur'])->pluck('id')->toArray();
            NotificationHelper::createForMultiple(
                $approvers,
                'lembur_updated',
                'Pengajuan Lembur Diperbarui',
                "{$lembur->user->name} memperbarui pengajuan lembur.",
                ['lembur_id' => $lembur->id, 'employee_name' => $lembur->user->name]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lembur berhasil diupdate',
                'data' => $lembur
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified lembur
     */
    public function destroy($id)
    {
        try {
            $lembur = Lembur::findOrFail($id);
            $user = Auth::user();

            // Check ownership
            if ($lembur->user_id !== $user->id && !$user->hasAnyRole(['Admin', 'HR'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus lembur ini'
                ], 403);
            }

            // Check if can be deleted
            if (!$lembur->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lembur yang sudah disetujui/ditolak tidak dapat dihapus'
                ], 403);
            }

            $lembur->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lembur berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve lembur (first approval)
     */
    public function approve(Request $request, $id)
    {
        try {
            $lembur = Lembur::findOrFail($id);
            $user = Auth::user();

            // Check permission
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur']) && !$user->canApprove()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menyetujui lembur'
                ], 403);
            }

            if ($lembur->status !== Lembur::STATUS_WAITING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lembur sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            $lembur->update([
                'status' => Lembur::STATUS_ACCEPTED
            ]);

            $lembur->load('user:id,name,email,posisi');

            // ✅ NOTIFIKASI: Kirim ke employee
            NotificationHelper::create(
                $lembur->user_id,
                'lembur_approved',
                'Lembur Disetujui (Tahap 1)',
                "Pengajuan lembur Anda pada {$lembur->tanggal_lembur} telah disetujui. Menunggu final approval.",
                ['lembur_id' => $lembur->id, 'approved_by' => $user->name]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lembur berhasil disetujui',
                'data' => $lembur
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject lembur (first approval)
     */
    public function reject(Request $request, $id)
    {
        try {
            $lembur = Lembur::findOrFail($id);
            $user = Auth::user();

            // Check permission
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur']) && !$user->canApprove()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menolak lembur'
                ], 403);
            }

            if ($lembur->status !== Lembur::STATUS_WAITING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lembur sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            $lembur->update([
                'status' => Lembur::STATUS_REJECTED,
                'final_status' => Lembur::STATUS_REJECTED,
                'sisa_waktu_claim' => 0
            ]);

            $lembur->load('user:id,name,email,posisi');

            // ✅ NOTIFIKASI: Kirim ke employee
            NotificationHelper::create(
                $lembur->user_id,
                'lembur_rejected',
                'Lembur Ditolak',
                "Pengajuan lembur Anda pada {$lembur->tanggal_lembur} telah ditolak.",
                ['lembur_id' => $lembur->id, 'rejected_by' => $user->name]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lembur berhasil ditolak',
                'data' => $lembur
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Final approve lembur
     */
    public function finalApprove(Request $request, $id)
    {
        try {
            $lembur = Lembur::findOrFail($id);
            $user = Auth::user();

            // Check permission - hanya Admin/HR/Direktur
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk final approve'
                ], 403);
            }

            if ($lembur->status !== Lembur::STATUS_ACCEPTED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lembur harus disetujui terlebih dahulu'
                ], 400);
            }

            if ($lembur->final_status !== Lembur::STATUS_WAITING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Final status sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            $lembur->update([
                'final_status' => Lembur::STATUS_ACCEPTED
            ]);

            $lembur->load('user:id,name,email,posisi');

            // ✅ NOTIFIKASI: Kirim ke employee
            NotificationHelper::create(
                $lembur->user_id,
                'lembur_final_approved',
                'Lembur Final Approved',
                "Pengajuan lembur Anda pada {$lembur->tanggal_lembur} telah final approved. Anda dapat mengajukan claim.",
                [
                    'lembur_id' => $lembur->id, 
                    'final_approved_by' => $user->name,
                    'sisa_waktu_claim' => $lembur->sisa_waktu_claim
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lembur berhasil final approved',
                'data' => $lembur
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal final approve lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Final reject lembur
     */
    public function finalReject(Request $request, $id)
    {
        try {
            $lembur = Lembur::findOrFail($id);
            $user = Auth::user();

            // Check permission
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk final reject'
                ], 403);
            }

            if ($lembur->final_status !== Lembur::STATUS_WAITING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Final status sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            $lembur->update([
                'final_status' => Lembur::STATUS_REJECTED,
                'sisa_waktu_claim' => 0
            ]);

            $lembur->load('user:id,name,email,posisi');

            // ✅ NOTIFIKASI: Kirim ke employee
            NotificationHelper::create(
                $lembur->user_id,
                'lembur_final_rejected',
                'Lembur Final Rejected',
                "Pengajuan lembur Anda pada {$lembur->tanggal_lembur} telah final rejected.",
                ['lembur_id' => $lembur->id, 'final_rejected_by' => $user->name]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lembur berhasil final rejected',
                'data' => $lembur
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal final reject lembur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        try {
            $user = Auth::user();
            $userId = $request->get('user_id', $user->id);

            // Jika bukan admin/HR, hanya bisa lihat statistik sendiri
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur']) && $userId != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya dapat melihat statistik sendiri'
                ], 403);
            }

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $stats = Lembur::getLemburStatistics($userId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Statistik lembur berhasil diambil',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}