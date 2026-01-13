<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    /**
     * Get badge counts untuk menu sidebar
     */
    public function getBadges(Request $request)
    {
        $user = $request->user();
        $isAdminOrHR = $user->hasAnyRole(['Admin', 'HR', 'Direktur']);
        
        $badges = [
            'employees' => 0,
            'leave' => 0,
            'overtime' => 0,
        ];

        if ($isAdminOrHR) {
            // UNTUK ADMIN/HR/DIREKTUR: Hitung semua pending
            
            // Total karyawan aktif (exclude Admin)
            $badges['employees'] = User::whereHas('roles', function($q) {
                $q->where('name', '!=', 'Admin');
            })->count();
            
            // Cuti pending approval
            $badges['leave'] = Leave::where('status', 'pending')->count();
            
            // Lembur pending (jika ada field status di tabel overtime)
            // $badges['overtime'] = Overtime::where('status', 'pending')->count();
            
        } else {
            // UNTUK KARYAWAN: Hitung data pribadi
            
            // Leave pending milik user
            $badges['leave'] = Leave::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count();
            
            // Overtime pending milik user
            // $badges['overtime'] = Overtime::where('user_id', $user->id)
            //     ->where('status', 'pending')
            //     ->count();
        }

        return response()->json([
            'success' => true,
            'badges' => $badges
        ]);
    }
}