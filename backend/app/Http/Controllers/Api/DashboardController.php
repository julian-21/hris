<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Cek role user
        $isAdminOrHR = $user->hasAnyRole(['Admin', 'HR', 'Direktur']);
        
        // Tentukan scope query (Global untuk Admin/HR/Direktur, Personal untuk Karyawan)
        $userId = $isAdminOrHR ? null : $user->id;

        return response()->json([
            'stats' => $this->getStats($userId, $user, $isAdminOrHR),
            'attendance_chart' => $this->getWeeklyAttendanceChart($userId),
            'recent_leaves' => $this->getRecentLeaves($userId, $isAdminOrHR),
            'recent_activities' => $this->getRecentActivities($userId),
            'employees' => $this->getEmployees($isAdminOrHR),
            'user' => [
                'name' => $user->name,
                'role' => $user->getRoleNames()->first(),
                'is_admin' => $isAdminOrHR
            ]
        ]);
    }

    private function getStats($userId, $user, $isAdminOrHR)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // ========================================
        // STAT 1: TOTAL KEHADIRAN
        // ========================================
        $attendanceQuery = Attendance::whereNotNull('check_in');
        if ($userId) {
            $attendanceQuery->where('user_id', $userId);
        }
        
        $currentAttendance = (clone $attendanceQuery)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->count();
        $lastAttendance = (clone $attendanceQuery)
            ->whereBetween('tanggal', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        
        $stat1Title = $isAdminOrHR ? 'Total Kehadiran' : 'Kehadiran Saya';
        
        // ========================================
        // STAT 2: CUTI DIAJUKAN
        // ========================================
        $leaveQuery = Leave::query();
        if ($userId) {
            $leaveQuery->where('user_id', $userId);
        }
        
        $currentLeave = (clone $leaveQuery)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
        $lastLeave = (clone $leaveQuery)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $stat2Title = $isAdminOrHR ? 'Cuti Diajukan' : 'Cuti Saya';

        // ========================================
        // STAT 3: JAM LEMBUR
        // ========================================
        $overtimeQuery = Attendance::whereNotNull('lama_lembur');
        if ($userId) {
            $overtimeQuery->where('user_id', $userId);
        }

        $currentOvertime = (clone $overtimeQuery)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('lama_lembur');
        $lastOvertime = (clone $overtimeQuery)
            ->whereBetween('tanggal', [$startOfLastMonth, $endOfLastMonth])
            ->sum('lama_lembur');

        $stat3Title = $isAdminOrHR ? 'Total Jam Lembur' : 'Lembur Saya';

        // ========================================
        // STAT 4: TOTAL KARYAWAN (Admin) atau SISA CUTI (Karyawan)
        // ========================================
        if ($isAdminOrHR) {
            // LOGIC UNTUK ADMIN/HR/DIREKTUR: Total Karyawan
            $currentUsers = User::whereHas('roles', function($q) {
                // Hitung semua karyawan kecuali Admin
                $q->whereNotIn('name', ['Admin']);
            })->count();
            
            $lastUsers = User::whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Admin']);
            })->where('created_at', '<', $startOfMonth)->count();
            
            $stat4 = [
                'title' => 'Total Karyawan',
                'value' => (string) $currentUsers,
                'change' => $this->calculatePercentageChange($currentUsers, $lastUsers),
                'changeType' => $currentUsers >= $lastUsers ? 'increase' : 'decrease',
                'icon' => 'IconUsers',
                'bgColor' => 'bg-purple-100',
                'iconColor' => 'text-purple-600'
            ];
        } else {
            // LOGIC UNTUK KARYAWAN: Sisa Cuti Tahunan
            // Ambil jatah cuti dari user (12 + jatah_cuti_tambahan)
            $jatahCuti = 12 + ($user->jatah_cuti_tambahan ?? 0);
            
            // Hitung cuti yang sudah disetujui tahun ini
            $cutiTerpakai = Leave::where('user_id', $userId)
                ->where('status', 'approved')
                ->whereYear('tanggal_mulai', $now->year)
                ->sum('jumlah_hari');
            
            $sisaCuti = $jatahCuti - $cutiTerpakai;
            
            // Untuk perbandingan bulan lalu (opsional, bisa di-set sama)
            $prevSisaCuti = $sisaCuti; // Atau bisa dihitung untuk bulan sebelumnya
            
            $stat4 = [
                'title' => 'Sisa Cuti Tahunan',
                'value' => (string) max(0, $sisaCuti), // Pastikan tidak negatif
                'change' => '0%', // Sisa cuti tidak ada perbandingan bulanan
                'changeType' => 'neutral',
                'icon' => 'IconCalendar',
                'bgColor' => 'bg-purple-100',
                'iconColor' => 'text-purple-600'
            ];
        }

        // ========================================
        // RETURN SEMUA STATS
        // ========================================
        return [
            [
                'title' => $stat1Title,
                'value' => (string) $currentAttendance,
                'change' => $this->calculatePercentageChange($currentAttendance, $lastAttendance),
                'changeType' => $currentAttendance >= $lastAttendance ? 'increase' : 'decrease',
                'icon' => 'IconCheckCircle',
                'bgColor' => 'bg-blue-100',
                'iconColor' => 'text-blue-600'
            ],
            [
                'title' => $stat2Title,
                'value' => (string) $currentLeave,
                'change' => $this->calculatePercentageChange($currentLeave, $lastLeave),
                'changeType' => $currentLeave >= $lastLeave ? 'increase' : 'decrease',
                'icon' => 'IconCalendar',
                'bgColor' => 'bg-green-100',
                'iconColor' => 'text-green-600'
            ],
            [
                'title' => $stat3Title,
                'value' => (string) round($currentOvertime, 1) . ' Jam',
                'change' => $this->calculatePercentageChange($currentOvertime, $lastOvertime),
                'changeType' => $currentOvertime >= $lastOvertime ? 'increase' : 'decrease',
                'icon' => 'IconClock',
                'bgColor' => 'bg-yellow-100',
                'iconColor' => 'text-yellow-600'
            ],
            $stat4
        ];
    }

    private function getWeeklyAttendanceChart($userId)
    {
        // Ambil 7 hari terakhir
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(Carbon::today()->subDays($i)->format('Y-m-d'));
        }

        $query = Attendance::whereIn('tanggal', $dates)->whereNotNull('check_in');
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $data = $query->select(
                DB::raw('DATE(tanggal) as date'), 
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->pluck('count', 'date');

        // Format agar sesuai chart (Isi 0 jika tidak ada data)
        return $dates->map(function ($date) use ($data) {
            $dayName = Carbon::parse($date)->locale('id')->isoFormat('ddd');
            return [
                'date' => ucfirst($dayName), // Sen, Sel, Rab, dst
                'value' => $data[$date] ?? 0
            ];
        });
    }

    private function getRecentLeaves($userId, $isAdminOrHR)
    {
        $query = Leave::with('user', 'leaveType')
            ->orderBy('created_at', 'desc')
            ->limit(5);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->map(function ($leave) use ($isAdminOrHR) {
            // Ambil inisial nama user
            $names = explode(' ', $leave->user->name);
            $initials = '';
            foreach ($names as $index => $n) {
                if ($index < 2) {
                    $initials .= strtoupper(substr($n, 0, 1));
                }
            }

            // Status text
            $statusText = match($leave->status) {
                'pending' => 'Pending',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                default => ucfirst($leave->status)
            };

            return [
                'id' => $leave->id,
                'name' => $leave->user->name,
                'initials' => $initials,
                'type' => $leave->leaveType->nama ?? 'Cuti',
                'date' => Carbon::parse($leave->tanggal_mulai)->format('d M') . 
                         ' - ' . 
                         Carbon::parse($leave->tanggal_selesai)->format('d M Y'),
                'status' => $leave->status,
                'statusText' => $statusText
            ];
        });
    }

    private function getRecentActivities($userId)
    {
        $activities = collect();

        // 1. Ambil data Absensi terbaru
        $attendances = Attendance::with('user')
            ->whereNotNull('check_in')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($attendances as $att) {
            $text = $userId 
                ? "Anda melakukan absen masuk" 
                : "{$att->user->name} melakukan absen masuk";
            
            $activities->push([
                'id' => 'att-' . $att->id,
                'text' => $text,
                'time' => Carbon::parse($att->check_in)->diffForHumans(),
                'timestamp' => Carbon::parse($att->check_in),
                'icon' => 'IconCheckCircle',
                'iconBg' => 'bg-green-100',
                'iconColor' => 'text-green-600'
            ]);
        }

        // 2. Ambil data Cuti terbaru
        $leaves = Leave::with('user')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($leaves as $leave) {
            // Tentukan icon berdasarkan status
            $iconConfig = match($leave->status) {
                'approved' => ['IconCheckCircle', 'bg-blue-100', 'text-blue-600'],
                'rejected' => ['IconX', 'bg-red-100', 'text-red-600'],
                default => ['IconPlus', 'bg-yellow-100', 'text-yellow-600']
            };

            // Tentukan text berdasarkan status dan role
            if ($userId) {
                // Untuk karyawan sendiri
                $text = match($leave->status) {
                    'approved' => "Cuti Anda disetujui",
                    'rejected' => "Cuti Anda ditolak",
                    default => "Anda mengajukan cuti"
                };
            } else {
                // Untuk admin/hr/direktur
                $text = match($leave->status) {
                    'approved' => "{$leave->user->name} - cuti disetujui",
                    'rejected' => "{$leave->user->name} - cuti ditolak",
                    default => "{$leave->user->name} mengajukan cuti"
                };
            }

            $activities->push([
                'id' => 'leave-' . $leave->id,
                'text' => $text,
                'time' => Carbon::parse($leave->updated_at)->diffForHumans(),
                'timestamp' => Carbon::parse($leave->updated_at),
                'icon' => $iconConfig[0],
                'iconBg' => $iconConfig[1],
                'iconColor' => $iconConfig[2]
            ]);
        }

        // Sort by timestamp descending dan ambil 5 teratas
        return $activities->sortByDesc('timestamp')->values()->take(5);
    }

    /**
     * Get employees for dashboard carousel
     */
    private function getEmployees($isAdminOrHR)
    {
        // Hanya tampilkan untuk Admin/HR/Direktur
        if (!$isAdminOrHR) {
            return [];
        }

        return User::with('roles')
            ->where('is_active', true)
            ->whereHas('roles', function($q) {
                // Exclude Admin role
                $q->whereNotIn('name', ['Admin']);
            })
            ->orderBy('name')
            ->limit(20) // Batasi 20 karyawan teratas
            ->get()
            ->map(function($employee) {
                // Gunakan accessor picture_url dari model User
                // atau buat URL dinamis sesuai environment
                $photoUrl = null;
                if ($employee->picture) {
                    $photoUrl = url('storage/' . $employee->picture);
                }
                
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'position' => $employee->posisi ?? 'Staff',
                    'role' => $employee->getRoleNames()->first() ?? 'Karyawan',
                    'photo_url' => $photoUrl,
                    'is_active' => $employee->is_active,
                    'company' => $employee->company
                ];
            });
    }

    private function calculatePercentageChange($current, $last)
    {
        if ($last == 0) {
            return $current > 0 ? '+100%' : '0%';
        }
        
        $diff = $current - $last;
        $percentage = ($diff / $last) * 100;
        
        return ($percentage > 0 ? '+' : '') . round($percentage, 1) . '%';
    }
}