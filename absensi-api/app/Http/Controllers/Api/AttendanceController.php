<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Kantor;
use App\Models\Leave; // Tambahkan model Leave
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['user', 'kantor']);

        // Filter berdasarkan user (jika bukan admin)
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            $query->where('user_id', $request->user()->id);
        }

        // Filter berdasarkan tanggal
        if ($request->has('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        // Filter berdasarkan user_id (untuk admin)
        if ($request->has('user_id') && $request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->orderBy('tanggal', 'desc')
            ->orderBy('check_in', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($attendances);
    }

    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'kantor_id' => 'required|exists:kantors,id',
            'out_of_office' => 'nullable|boolean',
            'out_of_office_reason' => 'required_if:out_of_office,true|nullable|string|min:10',
        ]);

        $today = Carbon::today();
        
        // ✅ VALIDASI CUTI - CEK APAKAH USER SEDANG CUTI HARI INI (KECUALI WFH)
        $cutiCheck = $this->checkCutiStatus($request->user()->id, $today, 'check_in');
        if (!$cutiCheck['allowed']) {
            return response()->json([
                'message' => $cutiCheck['message']
            ], 422);
        }

        // Cek apakah sudah check-in hari ini
        $existingAttendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in) {
            return response()->json([
                'message' => 'Anda sudah melakukan check-in hari ini'
            ], 422);
        }

        // Frontend sudah melakukan validasi jarak, jadi backend hanya perlu menyimpan
        // Tapi tetap hitung untuk logging
        $kantor = Kantor::find($request->kantor_id);
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $kantor->latitude,
            $kantor->longitude
        );

        $isWithinRadius = $distance <= $kantor->radius;

        // Buat attendance baru
        $attendance = Attendance::create([
            'user_id' => $request->user()->id,
            'tanggal' => $today,
            'check_in' => now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'kantor_id' => $request->kantor_id,
            'jadwal_checkin' => '08:00:00', // Bisa diambil dari schedule
            'jadwal_checkout' => '17:00:00',
            'out_of_office' => $request->out_of_office ?? false,
            'out_of_office_reason' => $request->out_of_office_reason,
        ]);

        $message = $request->out_of_office 
            ? 'Check-in berhasil (Di luar kantor)' 
            : 'Check-in berhasil';

        return response()->json([
            'message' => $message,
            'attendance' => $attendance->load(['user', 'kantor'])
        ], 201);
    }

    public function checkOut(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($attendance->check_out) {
            return response()->json([
                'message' => 'Anda sudah melakukan check-out'
            ], 422);
        }

        // ✅ VALIDASI CUTI - CEK APAKAH USER SEDANG CUTI HARI INI (KECUALI WFH)
        $cutiCheck = $this->checkCutiStatus($request->user()->id, $attendance->tanggal, 'check_out');
        if (!$cutiCheck['allowed']) {
            return response()->json([
                'message' => $cutiCheck['message']
            ], 422);
        }

        // ✅ TIDAK ADA VALIDASI RADIUS
        // Check-out bisa dilakukan dari mana saja (kantor, rumah, atau lokasi lain)
        // Koordinat hanya disimpan untuk tracking/logging saja
        
        $attendance->update([
            'check_out' => now(),
            // Koordinat check-in sudah ada di 'latitude' & 'longitude'
            // Jika mau simpan koordinat check-out, bisa tambah kolom latitude_out & longitude_out
        ]);

        return response()->json([
            'message' => 'Check-out berhasil',
            'attendance' => $attendance->load(['user', 'kantor'])
        ]);
    }

    public function today(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('tanggal', Carbon::today())
            ->with(['user', 'kantor'])
            ->first();

        return response()->json($attendance);
    }

    public function statistics(Request $request)
    {
        $userId = $request->user_id ?? $request->user()->id;
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth();
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth();

        $stats = [
            'total_hadir' => Attendance::where('user_id', $userId)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereNotNull('check_in')
                ->count(),
            
            'total_terlambat' => Attendance::where('user_id', $userId)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereNotNull('check_in')
                ->whereRaw('TIME(check_in) > jadwal_checkin')
                ->count(),
            
            'total_lembur' => Attendance::where('user_id', $userId)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereNotNull('lama_lembur')
                ->sum('lama_lembur'),
            
            'rata_rata_jam_kerja' => Attendance::where('user_id', $userId)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereNotNull('check_in')
                ->whereNotNull('check_out')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, check_in, check_out)) as avg_hours')
                ->value('avg_hours') ?? 0,
        ];

        return response()->json($stats);
    }

    /**
     * ✅ HELPER METHOD: Cek status cuti user pada tanggal tertentu
     * 
     * @param int $userId
     * @param Carbon $date
     * @param string $action ('check_in' atau 'check_out')
     * @return array ['allowed' => bool, 'message' => string]
     */
    private function checkCutiStatus($userId, $date, $action = 'check_in')
    {
        // Cari cuti yang sedang berlangsung pada tanggal ini
        // Status harus 'approved' dan bukan WFH
        $leave = Leave::with('leaveType') // Relasi ke tabel leave_types
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->whereHas('leaveType', function($query) {
                // Exclude WFH dari validasi (WFH tetap bisa absen)
                $query->where('nama', '!=', 'Work From Home (WFH)');
            })
            ->first();

        // Jika tidak ada cuti, boleh absen
        if (!$leave) {
            return [
                'allowed' => true,
                'message' => ''
            ];
        }

        // Jika cuti SEHARI atau LEBIH DARI SEHARI, tidak boleh absen sama sekali
        if ($leave->tipe_durasi === 'sehari' || $leave->tipe_durasi === 'lebih_dari_sehari') {
            return [
                'allowed' => false,
                'message' => "Anda sedang cuti {$leave->leaveType->nama} pada hari ini. Tidak dapat melakukan absensi."
            ];
        }

        // Jika cuti SETENGAH HARI, cek apakah boleh check-in atau check-out
        if ($leave->tipe_durasi === 'setengah_hari') {
            // Cek tipe setengah hari (pagi atau siang)
            if ($leave->setengah_hari_tipe === 'pagi') {
                // Cuti pagi: tidak bisa check-in, tapi bisa check-out
                if ($action === 'check_in') {
                    return [
                        'allowed' => false,
                        'message' => "Anda sedang cuti {$leave->leaveType->nama} setengah hari (pagi). Tidak dapat melakukan check-in."
                    ];
                }
                // Boleh check-out
                return [
                    'allowed' => true,
                    'message' => ''
                ];
            }

            if ($leave->setengah_hari_tipe === 'siang') {
                // Cuti siang: bisa check-in, tapi tidak bisa check-out
                if ($action === 'check_out') {
                    return [
                        'allowed' => false,
                        'message' => "Anda sedang cuti {$leave->leaveType->nama} setengah hari (siang). Tidak dapat melakukan check-out."
                    ];
                }
                // Boleh check-in
                return [
                    'allowed' => true,
                    'message' => ''
                ];
            }
        }

        // Default: boleh absen (jika ada kondisi durasi lain yang tidak terduga)
        return [
            'allowed' => true,
            'message' => ''
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }
}