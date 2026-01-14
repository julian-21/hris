<?php
// app/Http/Controllers/Api/SearchController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $query = $request->input('q');
            $type = $request->input('type', 'all');

            if (empty($query) || strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Query minimal 2 karakter'
                ], 400);
            }

            $results = [];
            $user = $request->user();

            // Search Employees (hanya untuk Admin, HR, Direktur)
            if (($type === 'all' || $type === 'employee') && $this->canSearchEmployees($user)) {
                try {
                    $employees = User::where(function($q) use ($query) {
                            $q->where('name', 'like', "%{$query}%")
                              ->orWhere('email', 'like', "%{$query}%");
                            
                            // Cek apakah column phone ada
                            if (DB::getSchemaBuilder()->hasColumn('users', 'phone')) {
                                $q->orWhere('phone', 'like', "%{$query}%");
                            }
                            
                            // Cek apakah column posisi ada
                            if (DB::getSchemaBuilder()->hasColumn('users', 'posisi')) {
                                $q->orWhere('posisi', 'like', "%{$query}%");
                            }
                        })
                        ->where('is_active', 1)
                        ->limit(5)
                        ->get(['id', 'name', 'email', 'phone', 'posisi', 'picture', 'company']);

                    $results['employees'] = $employees->map(function($employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->name,
                            'email' => $employee->email,
                            'phone' => $employee->phone ?? '-',
                            'posisi' => $employee->posisi ?? '-',
                            'picture' => $employee->picture,
                            'company' => $employee->company ?? '-',
                            'type' => 'employee',
                            'link' => '/employee'
                        ];
                    });
                } catch (\Exception $e) {
                    Log::error('Error searching employees: ' . $e->getMessage());
                    $results['employees'] = [];
                }
            }

            // Search Attendance (jika table attendance ada)
            if (($type === 'all' || $type === 'attendance') && DB::getSchemaBuilder()->hasTable('attendances')) {
                try {
                    $attendanceQuery = DB::table('attendances')
                        ->join('users', 'attendances.user_id', '=', 'users.id')
                        ->where(function($q) use ($query) {
                            $q->where('users.name', 'like', "%{$query}%")
                              ->orWhere('attendances.date', 'like', "%{$query}%");
                            
                            if (DB::getSchemaBuilder()->hasColumn('attendances', 'status')) {
                                $q->orWhere('attendances.status', 'like', "%{$query}%");
                            }
                        });

                    // Filter berdasarkan role
                    if (!$this->isAdminOrHR($user)) {
                        $attendanceQuery->where('attendances.user_id', $user->id);
                    }

                    $attendances = $attendanceQuery
                        ->select(
                            'attendances.id',
                            'attendances.user_id',
                            'attendances.date',
                            'attendances.status',
                            'attendances.check_in',
                            'attendances.check_out',
                            'users.name as employee_name',
                            'users.posisi as employee_posisi'
                        )
                        ->limit(5)
                        ->get();

                    $results['attendances'] = $attendances->map(function($attendance) {
                        return [
                            'id' => $attendance->id,
                            'employee_name' => $attendance->employee_name ?? 'Unknown',
                            'employee_posisi' => $attendance->employee_posisi ?? '-',
                            'date' => $attendance->date,
                            'status' => $attendance->status ?? '-',
                            'check_in' => $attendance->check_in ?? '-',
                            'check_out' => $attendance->check_out ?? '-',
                            'type' => 'attendance',
                            'link' => '/attendance?date=' . $attendance->date
                        ];
                    });
                } catch (\Exception $e) {
                    Log::error('Error searching attendances: ' . $e->getMessage());
                    $results['attendances'] = [];
                }
            }

            // Search Leave (jika table leaves ada)
            if (($type === 'all' || $type === 'leave') && DB::getSchemaBuilder()->hasTable('leaves')) {
                try {
                    $leaveQuery = DB::table('leaves')
                        ->join('users', 'leaves.user_id', '=', 'users.id')
                        ->where(function($q) use ($query) {
                            $q->where('users.name', 'like', "%{$query}%");
                            
                            if (DB::getSchemaBuilder()->hasColumn('leaves', 'reason')) {
                                $q->orWhere('leaves.reason', 'like', "%{$query}%");
                            }
                            
                            if (DB::getSchemaBuilder()->hasColumn('leaves', 'leave_type')) {
                                $q->orWhere('leaves.leave_type', 'like', "%{$query}%");
                            }
                        });

                    if (!$this->isAdminOrHR($user)) {
                        $leaveQuery->where('leaves.user_id', $user->id);
                    }

                    $leaves = $leaveQuery
                        ->select(
                            'leaves.id',
                            'leaves.user_id',
                            'leaves.start_date',
                            'leaves.end_date',
                            'leaves.leave_type',
                            'leaves.reason',
                            'leaves.status',
                            'users.name as employee_name',
                            'users.posisi as employee_posisi'
                        )
                        ->limit(5)
                        ->get();

                    $results['leaves'] = $leaves->map(function($leave) {
                        return [
                            'id' => $leave->id,
                            'employee_name' => $leave->employee_name ?? 'Unknown',
                            'employee_posisi' => $leave->employee_posisi ?? '-',
                            'start_date' => $leave->start_date,
                            'end_date' => $leave->end_date,
                            'leave_type' => $leave->leave_type ?? '-',
                            'reason' => $leave->reason ?? '-',
                            'status' => $leave->status ?? 'pending',
                            'type' => 'leave',
                            'link' => '/leave'
                        ];
                    });
                } catch (\Exception $e) {
                    Log::error('Error searching leaves: ' . $e->getMessage());
                    $results['leaves'] = [];
                }
            }

            // Search Overtime (jika table overtimes ada)
            if (($type === 'all' || $type === 'overtime') && DB::getSchemaBuilder()->hasTable('overtimes')) {
                try {
                    $overtimeQuery = DB::table('overtimes')
                        ->join('users', 'overtimes.user_id', '=', 'users.id')
                        ->where(function($q) use ($query) {
                            $q->where('users.name', 'like', "%{$query}%");
                            
                            if (DB::getSchemaBuilder()->hasColumn('overtimes', 'description')) {
                                $q->orWhere('overtimes.description', 'like', "%{$query}%");
                            }
                        });

                    if (!$this->isAdminOrHR($user)) {
                        $overtimeQuery->where('overtimes.user_id', $user->id);
                    }

                    $overtimes = $overtimeQuery
                        ->select(
                            'overtimes.id',
                            'overtimes.user_id',
                            'overtimes.date',
                            'overtimes.hours',
                            'overtimes.description',
                            'overtimes.status',
                            'users.name as employee_name',
                            'users.posisi as employee_posisi'
                        )
                        ->limit(5)
                        ->get();

                    $results['overtimes'] = $overtimes->map(function($overtime) {
                        return [
                            'id' => $overtime->id,
                            'employee_name' => $overtime->employee_name ?? 'Unknown',
                            'employee_posisi' => $overtime->employee_posisi ?? '-',
                            'date' => $overtime->date,
                            'hours' => $overtime->hours ?? 0,
                            'description' => $overtime->description ?? '-',
                            'status' => $overtime->status ?? 'pending',
                            'type' => 'overtime',
                            'link' => '/overtime'
                        ];
                    });
                } catch (\Exception $e) {
                    Log::error('Error searching overtimes: ' . $e->getMessage());
                    $results['overtimes'] = [];
                }
            }

            $totalResults = collect($results)->sum(fn($items) => count($items));

            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => $results,
                'total' => $totalResults
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat pencarian',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function canSearchEmployees($user)
    {
        if (!$user->roles) return false;
        $roles = $user->roles->pluck('name')->toArray();
        return count(array_intersect($roles, ['Admin', 'HR', 'Direktur'])) > 0;
    }

    private function isAdminOrHR($user)
    {
        if (!$user->roles) return false;
        $roles = $user->roles->pluck('name')->toArray();
        return count(array_intersect($roles, ['Admin', 'HR', 'Direktur'])) > 0;
    }
}