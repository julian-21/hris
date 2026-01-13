<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LemburClaim;
use App\Models\Lembur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LemburClaimController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = LemburClaim::with(['user:id,name,email,posisi,company']);

            // 🔐 Role filter
            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                $query->where('user_id', $user->id);
            }

            // 👤 Filter by user_id
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // 📌 Status filter
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // 📅 Month & Year filter (FIX UTAMA)
            if ($request->filled('month') && $request->filled('year')) {
                $query->whereMonth('date', (int) $request->month)
                    ->whereYear('date', (int) $request->year);
            }

            $perPage = (int) $request->get('per_page', 15);

            $claims = $query
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // 🎨 Status label
            $claims->getCollection()->transform(function ($claim) {
                $claim->status_label = match($claim->status) {
                    LemburClaim::STATUS_WAITING => 'Menunggu',
                    LemburClaim::STATUS_APPROVED => 'Disetujui',
                    LemburClaim::STATUS_REJECTED => 'Ditolak',
                    default => $claim->status
                };
                return $claim;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data claim berhasil diambil',
                'data' => $claims
            ]);
        } catch (\Exception $e) {
            \Log::error('Lembur Claim Index Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data claim',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $user = Auth::user();
            $claim = LemburClaim::with(['user:id,name,email,posisi,company'])->findOrFail($id);

            // Check access
            if ($claim->user_id !== $user->id && !$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data claim berhasil diambil',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data claim',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|integer|min:1',
            'date' => 'required|date',
        ], [
            'time.required' => 'Waktu claim wajib diisi',
            'time.min' => 'Waktu claim minimal 1 menit',
            'date.required' => 'Tanggal claim wajib diisi',
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

            // Check if columns exist
            if (!Schema::hasColumn('lemburs', 'sisa_waktu_claim')) {
                throw new \Exception('Kolom sisa_waktu_claim tidak ditemukan. Silakan jalankan migration terlebih dahulu.');
            }

            // Cek sisa waktu claim yang tersedia
            $sisaClaim = Lembur::where('user_id', $user->id)
                ->where('is_expired', false)
                ->where('final_status', Lembur::STATUS_ACCEPTED)
                ->sum('sisa_waktu_claim');

            if ($sisaClaim < $request->time) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Sisa waktu claim tidak mencukupi. Tersedia: {$sisaClaim} menit, diminta: {$request->time} menit"
                ], 400);
            }

            $claim = LemburClaim::create([
                'time' => $request->time,
                'date' => $request->date,
                'user_id' => $user->id,
                'status' => LemburClaim::STATUS_WAITING,
            ]);

            $claim->load('user:id,name,email,posisi,company');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Claim lembur berhasil diajukan',
                'data' => $claim
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lembur Claim Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengajukan claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve($id)
    {
        try {
            $claim = LemburClaim::findOrFail($id);
            $user = Auth::user();

            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            if ($claim->status !== LemburClaim::STATUS_WAITING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Claim sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            // Update status claim
            $claim->approve();

            // Kurangi sisa_waktu_claim dari lembur user
            $this->deductClaimTime($claim->user_id, $claim->time);

            DB::commit();

            $claim->load('user:id,name,email,posisi,company');

            return response()->json([
                'success' => true,
                'message' => 'Claim berhasil disetujui',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lembur Claim Approve Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reject_reason' => 'required|string|max:500',
        ], [
            'reject_reason.required' => 'Alasan penolakan wajib diisi'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $claim = LemburClaim::findOrFail($id);
            $user = Auth::user();

            if (!$user->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            if ($claim->status !== LemburClaim::STATUS_WAITING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Claim sudah diproses sebelumnya'
                ], 400);
            }

            $claim->reject($request->reject_reason);
            $claim->load('user:id,name,email,posisi,company');

            return response()->json([
                'success' => true,
                'message' => 'Claim berhasil ditolak',
                'data' => $claim
            ]);
        } catch (\Exception $e) {
            \Log::error('Lembur Claim Reject Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $claim = LemburClaim::findOrFail($id);
            $user = Auth::user();

            // Check access
            if ($claim->user_id !== $user->id && !$user->hasAnyRole(['Admin', 'HR'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses'
                ], 403);
            }

            // Check if can be deleted
            if (!$claim->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Claim yang sudah diproses tidak dapat dihapus'
                ], 403);
            }

            $claim->delete();

            return response()->json([
                'success' => true,
                'message' => 'Claim berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            \Log::error('Lembur Claim Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function deductClaimTime($userId, $claimTime)
    {
        $lemburs = Lembur::where('user_id', $userId)
            ->where('is_expired', false)
            ->where('final_status', Lembur::STATUS_ACCEPTED)
            ->where('sisa_waktu_claim', '>', 0)
            ->orderBy('tanggal_lembur', 'asc')
            ->get();

        $remaining = $claimTime;

        foreach ($lemburs as $lembur) {
            if ($remaining <= 0) break;

            if ($lembur->sisa_waktu_claim >= $remaining) {
                $lembur->sisa_waktu_claim -= $remaining;
                $lembur->save();
                $remaining = 0;
            } else {
                $remaining -= $lembur->sisa_waktu_claim;
                $lembur->sisa_waktu_claim = 0;
                $lembur->save();
            }
        }

        if ($remaining > 0) {
            \Log::warning("Masih ada sisa waktu yang belum terpotong: {$remaining} menit untuk user {$userId}");
        }
    }

    public function availableTime()
    {
        try {
            $user = Auth::user();

            // Check if required columns exist
            if (!Schema::hasColumn('lemburs', 'sisa_waktu_claim') || 
                !Schema::hasColumn('lemburs', 'is_expired')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Struktur database belum lengkap. Silakan jalankan migration.',
                    'data' => [
                        'available_time' => 0,
                        'expiring_soon' => []
                    ]
                ], 500);
            }

            // Get available claim time
            $sisaClaim = Lembur::where('user_id', $user->id)
                ->where('is_expired', false)
                ->where('final_status', Lembur::STATUS_ACCEPTED)
                ->sum('sisa_waktu_claim') ?? 0;

            // Get expiring soon lemburs
            $expiringSoon = Lembur::where('user_id', $user->id)
                ->where('is_expired', false)
                ->where('final_status', Lembur::STATUS_ACCEPTED)
                ->whereNotNull('expire_at')
                ->where('sisa_waktu_claim', '>', 0)
                ->where('expire_at', '<=', now()->addDays(30))
                ->where('expire_at', '>', now())
                ->orderBy('expire_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'available_time' => (int) $sisaClaim,
                    'expiring_soon' => $expiringSoon->map(function($lembur) {
                        return [
                            'id' => $lembur->id,
                            'tanggal_lembur' => $lembur->tanggal_lembur,
                            'sisa_waktu_claim' => $lembur->sisa_waktu_claim ?? 0,
                            'expire_at' => $lembur->expire_at,
                            'days_until_expire' => $lembur->expire_at 
                                ? now()->diffInDays($lembur->expire_at) 
                                : 0,
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Available Time Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data waktu tersedia',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'data' => [
                    'available_time' => 0,
                    'expiring_soon' => []
                ]
            ], 500);
        }
    }
}