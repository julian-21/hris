<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\KantorController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LemburController;
use App\Http\Controllers\Api\LemburClaimController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ============================================
// PROTECTED ROUTES (Authentication Required)
// ============================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --------------------------------------------
    // AUTH ROUTES
    // --------------------------------------------
    Route::get('/badges', [BadgeController::class, 'getBadges']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // --------------------------------------------
    // LEMBUR ROUTES
    // --------------------------------------------
    Route::prefix('lemburs')->group(function () {
        Route::get('/', [LemburController::class, 'index']);
        Route::post('/', [LemburController::class, 'store']);
        Route::get('/statistics', [LemburController::class, 'statistics']);
        Route::get('/{id}', [LemburController::class, 'show']);
        Route::put('/{id}', [LemburController::class, 'update']);
        Route::delete('/{id}', [LemburController::class, 'destroy']);
        
        // Approval routes
        Route::post('/{id}/approve', [LemburController::class, 'approve']);
        Route::post('/{id}/reject', [LemburController::class, 'reject']);
        Route::post('/{id}/final-approve', [LemburController::class, 'finalApprove']);
        Route::post('/{id}/final-reject', [LemburController::class, 'finalReject']);
    });

    // --------------------------------------------
    // LEMBUR CLAIM ROUTES
    // --------------------------------------------
    Route::prefix('lembur-claims')->group(function () {
        Route::get('/', [LemburClaimController::class, 'index']);
        Route::post('/', [LemburClaimController::class, 'store']);
        Route::get('/available-time', [LemburClaimController::class, 'availableTime']);
        Route::get('/{id}', [LemburClaimController::class, 'show']);
        Route::post('/{id}/approve', [LemburClaimController::class, 'approve']);
        Route::post('/{id}/reject', [LemburClaimController::class, 'reject']);
        Route::delete('/{id}', [LemburClaimController::class, 'destroy']);
    });

    // --------------------------------------------
    // USER MANAGEMENT ROUTES
    // --------------------------------------------
    Route::middleware(['role:Admin|HR|Direktur'])->group(function () {
        // Route dengan path spesifik HARUS di atas route dengan parameter {id}
        Route::get('/employees/stats', [EmployeeController::class, 'stats']);
        Route::get('/employees/hierarchy', [EmployeeController::class, 'hierarchy']);
        Route::get('/employees/options', [EmployeeController::class, 'getOptions']);
        Route::get('/employees/export', [EmployeeController::class, 'export']);
        
        // Route CRUD standard
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{id}', [EmployeeController::class, 'show']);
        Route::put('/employees/{id}', [EmployeeController::class, 'update']);
        Route::post('/employees/{id}', [EmployeeController::class, 'update']); // Untuk FormData
        Route::patch('/employees/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
        Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    });

    // --------------------------------------------
    // KANTOR ROUTES
    // --------------------------------------------
    Route::prefix('kantors')->group(function () {
        // Public kantors routes (all authenticated users)
        Route::get('/', [KantorController::class, 'index']);
        Route::get('/{id}', [KantorController::class, 'show']);
        Route::post('/{id}/check-distance', [KantorController::class, 'checkDistance']);
        
        // Admin/HR only
        Route::middleware('role:Admin|HR')->group(function () {
            Route::post('/', [KantorController::class, 'store']);
            Route::put('/{id}', [KantorController::class, 'update']);
            Route::delete('/{id}', [KantorController::class, 'destroy']);
        });
    });
    
    // --------------------------------------------
    // ATTENDANCE ROUTES
    // --------------------------------------------
    Route::prefix('attendance')->group(function () {
        Route::middleware('permission:view attendance')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
            Route::get('/today', [AttendanceController::class, 'today']);
        });
        
        Route::middleware('permission:create attendance')->group(function () {
            Route::post('/', [AttendanceController::class, 'store']);
            Route::post('/{id}/checkout', [AttendanceController::class, 'checkOut']);
        });
        
        Route::middleware('permission:approve attendance')->group(function () {
            Route::patch('/{id}/approve', [AttendanceController::class, 'approve']);
        });
    });
    
    // --------------------------------------------
    // LEAVE ROUTES
    // --------------------------------------------
    Route::prefix('leaves')->group(function () {
        // Public routes (all authenticated users)
        Route::get('/types', [LeaveController::class, 'getLeaveTypes']);
        Route::get('/statistics', [LeaveController::class, 'statistics']);
        
        // Approval routes (Admin/HR/Direktur only)
        Route::middleware('permission:approve leave')->group(function () {
            Route::get('/pending-approvals', [LeaveController::class, 'pendingApprovals']);
            Route::post('/{id}/approve', [LeaveController::class, 'approve']);
            Route::post('/{id}/reject', [LeaveController::class, 'reject']);
        });

        // CRUD with permissions
        Route::middleware('permission:view leave')->group(function () {
            Route::get('/', [LeaveController::class, 'index']);
            Route::get('/{id}', [LeaveController::class, 'show']);
        });
        
        Route::middleware('permission:create leave')->group(function () {
            Route::post('/', [LeaveController::class, 'store']);
            Route::post('/{id}', [LeaveController::class, 'update']); // POST for file upload support
            Route::put('/{id}', [LeaveController::class, 'update']);
            Route::delete('/{id}', [LeaveController::class, 'destroy']);
        });
    });
    
    // --------------------------------------------
    // OVERTIME ROUTES
    // --------------------------------------------
    Route::prefix('overtime')->group(function () {
        Route::middleware('permission:view overtime')->group(function () {
            Route::get('/', [OvertimeController::class, 'index']);
        });
        
        Route::middleware('permission:create overtime')->group(function () {
            Route::post('/', [OvertimeController::class, 'store']);
        });
        
        Route::middleware('permission:approve overtime')->group(function () {
            Route::patch('/{id}/approve', [OvertimeController::class, 'approve']);
        });
    });
    
    // --------------------------------------------
    // REPORTS ROUTES
    // --------------------------------------------
    Route::middleware('permission:view reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index']);
    });

    // --------------------------------------------
    // ROLES & PERMISSIONS ROUTES (Admin & Direktur only)
    // --------------------------------------------
    Route::middleware('role:Admin|Direktur')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::get('/permissions', [PermissionController::class, 'index']);
    });

    // --------------------------------------------
    // ATTENDANCE REPORTS ROUTES
    // --------------------------------------------
    Route::prefix('attendance-reports')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\AttendanceReportController::class, 'index']);
        Route::get('/summary', [App\Http\Controllers\Api\AttendanceReportController::class, 'summary']);
        Route::get('/users', [App\Http\Controllers\Api\AttendanceReportController::class, 'users']);
    });

}); // ✅ Penutupan yang benar untuk middleware auth:sanctum