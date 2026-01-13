<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    /**
     * Get rekap kehadiran dengan integrasi data cuti
     */
    public function index(Request $request)
    {
        // Only Admin, HR, Direktur can access
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $userId = $request->user_id;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Get user info
        $user = $userId ? User::find($userId) : null;

        // Generate all dates in range
        $dates = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // Get all attendance records
        $attendanceQuery = Attendance::with(['kantor'])
            ->whereBetween('tanggal', [$startDate, $endDate]);
        
        if ($userId) {
            $attendanceQuery->where('user_id', $userId);
        }
        
        $attendances = $attendanceQuery->get()->keyBy(function($item) {
            return $item->user_id . '-' . Carbon::parse($item->tanggal)->format('Y-m-d');
        });

        // Get all approved leaves
        $leaveQuery = Leave::with(['leaveType'])
            ->where('status', 'approved')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_mulai', [$startDate, $endDate])
                    ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                    ->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('tanggal_mulai', '<=', $startDate)
                          ->where('tanggal_selesai', '>=', $endDate);
                    });
            });

        if ($userId) {
            $leaveQuery->where('user_id', $userId);
        }

        $leaves = $leaveQuery->get();

            // Ambil user
        $users = $userId
            ? User::where('id', $userId)->get()
            : User::orderBy('name')->get();

        $reportData = [];

        foreach ($users as $u) {
            foreach ($dates as $date) {

                $carbonDate = Carbon::parse($date);

                $attendance = $attendances->get($u->id . '-' . $date);
                $leave = $this->getLeaveForDate($leaves, $u->id, $date);

                $status = $this->determineStatus($attendance, $leave, $carbonDate);

                $reportData[] = [
                    'user_id' => $u->id,
                    'user_name' => $u->name,

                    'tanggal' => $date,
                    'hari' => $carbonDate->locale('id')->dayName,
                    'is_weekend' => $carbonDate->isWeekend(),

                    'check_in' => $attendance?->check_in?->format('H:i:s'),
                    'check_out' => $attendance?->check_out?->format('H:i:s'),
                    'jadwal_checkin' => $attendance?->jadwal_checkin,
                    'jadwal_checkout' => $attendance?->jadwal_checkout,
                    'kantor_nama' => $attendance?->kantor?->nama,
                    'out_of_office' => $attendance?->out_of_office ?? false,
                    'out_of_office_reason' => $attendance?->out_of_office_reason,

                    'leave_status' => $leave ? [
                        'leave_type_nama' => $leave->leaveType->nama,
                        'alasan' => $leave->alasan,
                    ] : null,

                    'status' => $status['status'],
                    'status_label' => $status['label'],
                    'status_color' => $status['color'],
                    'keterangan' => $status['keterangan'],

                    'is_late' => $this->isLate($attendance),
                    'durasi_terlambat' => $this->getLateDuration($attendance),
                ];
            }
        }


        return response()->json([
            'user' => $user,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => count($dates),
            'data' => $reportData,
            'summary' => $this->generateSummary($reportData),
        ]);
    }

    /**
     * Get summary statistics
     */
    public function summary(Request $request)
    {
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $userId = $request->user_id;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $query = Attendance::whereBetween('tanggal', [$startDate, $endDate]);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        $leaveQuery = Leave::where('status', 'approved')
            ->whereBetween('tanggal_mulai', [$startDate, $endDate]);
        if ($userId) {
            $leaveQuery->where('user_id', $userId);
        }
        
        $approvedLeaves = $leaveQuery->sum('jumlah_hari');

        return response()->json([
            'total_hadir' => $attendances->whereNotNull('check_in')->count(),
            'total_terlambat' => $attendances->filter(function($att) {
                return $this->isLate($att);
            })->count(),
            'total_tidak_checkout' => $attendances->whereNotNull('check_in')->whereNull('check_out')->count(),
            'total_cuti' => $approvedLeaves,
            'total_out_of_office' => $attendances->where('out_of_office', true)->count(),
        ]);
    }

    /**
     * Get users list for filter
     */
    public function users(Request $request)
    {
        if (!$request->user()->hasAnyRole(['Admin', 'HR', 'Direktur'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Helper: Get leave for specific date
     */
    private function getLeaveForDate($leaves, $userId, $date)
    {
        return $leaves->first(function($leave) use ($userId, $date) {
            if ($userId && $leave->user_id != $userId) {
                return false;
            }
            
            $carbonDate = Carbon::parse($date);
            $startDate = Carbon::parse($leave->tanggal_mulai);
            $endDate = Carbon::parse($leave->tanggal_selesai);
            
            return $carbonDate->between($startDate, $endDate);
        });
    }

    /**
     * Helper: Determine attendance status
     */
    private function determineStatus($attendance, $leave, $carbonDate)
    {
        // Weekend
        if ($carbonDate->isWeekend()) {
            if ($attendance && $attendance->check_in) {
                return [
                    'status' => 'hadir_weekend',
                    'label' => 'Masuk (Weekend)',
                    'color' => 'blue',
                    'keterangan' => 'Masuk di hari libur'
                ];
            }
            return [
                'status' => 'weekend',
                'label' => 'Libur',
                'color' => 'gray',
                'keterangan' => 'Hari libur (weekend)'
            ];
        }

        // Has approved leave
        if ($leave) {
            // Cuti penuh
            if ($leave->tipe_durasi === 'sehari' || $leave->tipe_durasi === 'lebih_dari_sehari') {
                return [
                    'status' => 'cuti_penuh',
                    'label' => 'Cuti',
                    'color' => 'yellow',
                    'keterangan' => $leave->leaveType->nama
                ];
            }
            
            // Cuti setengah hari
            if ($leave->tipe_durasi === 'setengah_hari') {
                $tipe = $leave->setengah_hari_tipe;
                
                if (!$attendance || !$attendance->check_in) {
                    return [
                        'status' => 'alpha_with_half_leave',
                        'label' => 'Alpha',
                        'color' => 'red',
                        'keterangan' => "Cuti setengah hari ($tipe) tapi tidak check-in"
                    ];
                }
                
                $checkInTime = Carbon::parse($attendance->check_in);
                
                // Cuti pagi - harus masuk siang (setelah jam 12:00)
                if ($tipe === 'pagi') {
                    if ($checkInTime->hour >= 12) {
                        return [
                            'status' => 'hadir_half_leave',
                            'label' => 'Hadir (Setengah Hari)',
                            'color' => 'green',
                            'keterangan' => 'Cuti setengah hari pagi, masuk siang'
                        ];
                    } else {
                        return [
                            'status' => 'hadir_but_should_be_leave',
                            'label' => 'Hadir (Anomali)',
                            'color' => 'orange',
                            'keterangan' => 'Masuk pagi padahal cuti setengah hari pagi'
                        ];
                    }
                }
                
                // Cuti siang - harus masuk pagi (sebelum jam 12:00)
                if ($tipe === 'siang') {
                    if ($checkInTime->hour < 12) {
                        return [
                            'status' => 'hadir_half_leave',
                            'label' => 'Hadir (Setengah Hari)',
                            'color' => 'green',
                            'keterangan' => 'Cuti setengah hari siang, masuk pagi'
                        ];
                    } else {
                        return [
                            'status' => 'hadir_but_should_be_leave',
                            'label' => 'Hadir (Anomali)',
                            'color' => 'orange',
                            'keterangan' => 'Masuk siang padahal cuti setengah hari siang'
                        ];
                    }
                }
            }
        }

        // No leave - check attendance
        if ($attendance && $attendance->check_in) {
            if ($attendance->out_of_office) {
                return [
                    'status' => 'hadir_out_of_office',
                    'label' => 'Hadir (Luar Kantor)',
                    'color' => 'blue',
                    'keterangan' => 'Check-in di luar kantor'
                ];
            }
            
            return [
                'status' => 'hadir',
                'label' => 'Hadir',
                'color' => 'green',
                'keterangan' => $this->isLate($attendance) ? 'Hadir (Terlambat)' : 'Hadir tepat waktu'
            ];
        }

        // No attendance, no leave = Alpha
        return [
            'status' => 'alpha',
            'label' => 'Alpha',
            'color' => 'red',
            'keterangan' => 'Tidak hadir tanpa keterangan'
        ];
    }

    /**
     * Helper: Check if late
     */
    private function isLate($attendance)
    {
        if (!$attendance || !$attendance->check_in || !$attendance->jadwal_checkin) {
            return false;
        }

        $checkIn = Carbon::parse($attendance->check_in);
        
        // Parse jadwal_checkin - cek apakah sudah full datetime atau time only
        if (strlen($attendance->jadwal_checkin) <= 8) {
            // Kalau cuma time (HH:MM:SS), gabungin dengan tanggal
            $scheduled = Carbon::parse($attendance->tanggal)->setTimeFromTimeString($attendance->jadwal_checkin);
        } else {
            // Kalau udah full datetime
            $scheduled = Carbon::parse($attendance->jadwal_checkin);
        }

        return $checkIn->gt($scheduled);
    }

    /**
     * Helper: Get late duration in minutes
     */
    private function getLateDuration($attendance)
    {
        if (!$this->isLate($attendance)) {
            return null;
        }

        $checkIn = Carbon::parse($attendance->check_in);
        
        // Parse jadwal_checkin - cek apakah sudah full datetime atau time only
        if (strlen($attendance->jadwal_checkin) <= 8) {
            // Kalau cuma time (HH:MM:SS), gabungin dengan tanggal
            $scheduled = Carbon::parse($attendance->tanggal)->setTimeFromTimeString($attendance->jadwal_checkin);
        } else {
            // Kalau udah full datetime
            $scheduled = Carbon::parse($attendance->jadwal_checkin);
        }

        return $checkIn->diffInMinutes($scheduled);
    }

    /**
     * Helper: Generate summary
     */
    private function generateSummary($reportData)
    {
        $total = count($reportData);
        $hadir = 0;
        $terlambat = 0;
        $cuti = 0;
        $alpha = 0;
        $weekend = 0;

        foreach ($reportData as $item) {
            switch ($item['status']) {
                case 'hadir':
                case 'hadir_half_leave':
                case 'hadir_out_of_office':
                    $hadir++;
                    if ($item['is_late']) {
                        $terlambat++;
                    }
                    break;
                case 'cuti_penuh':
                    $cuti++;
                    break;
                case 'alpha':
                case 'alpha_with_half_leave':
                    $alpha++;
                    break;
                case 'weekend':
                case 'hadir_weekend':
                    $weekend++;
                    break;
            }
        }

        return [
            'total_hari' => $total,
            'total_hadir' => $hadir,
            'total_terlambat' => $terlambat,
            'total_cuti' => $cuti,
            'total_alpha' => $alpha,
            'total_weekend' => $weekend,
            'persentase_kehadiran' => $total > 0 ? round(($hadir / ($total - $weekend)) * 100, 2) : 0,
        ];
    }
}