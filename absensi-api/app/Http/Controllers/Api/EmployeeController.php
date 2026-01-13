<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Kantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'kantor', 'atasan', 'bawahan']);

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%')
                  ->orWhere('posisi', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by role
        if ($request->role) {
            $query->role($request->role);
        }

        // Filter by office
        if ($request->kantor_id) {
            $query->where('kantor_id', $request->kantor_id);
        }

        // Filter by company
        if ($request->company) {
            $query->where('company', $request->company);
        }

        // Filter by position
        if ($request->posisi) {
            $query->where('posisi', 'like', '%' . $request->posisi . '%');
        }

        // Filter by atasan
        if ($request->atasan_id) {
            $query->where('atasan_id', $request->atasan_id);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $employees = $query->orderBy('name')->paginate($request->per_page ?? 15);

        // Add additional data
        $employees->getCollection()->transform(function($employee) {
            $employee->masa_kerja = $employee->getMasaKerja();
            $employee->avatar_url = $employee->picture_url;
            return $employee;
        });

        return response()->json($employees);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'company' => 'required|in:' . implode(',', User::COMPANIES),
            'posisi' => 'required|string|max:255',
            'role' => 'required|string|exists:roles,name',
            'kantor_id' => 'nullable|exists:kantors,id',
            'atasan_id' => 'nullable|exists:users,id',
            'tanggal_bergabung' => 'required|date',
            'jatah_cuti_tambahan' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $validated = $validator->validated();

            // Handle picture upload
            if ($request->hasFile('picture')) {
                $validated['picture'] = $request->file('picture')->store('employees', 'public');
            }

            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'company' => $validated['company'],
                'posisi' => $validated['posisi'],
                'tanggal_bergabung' => $validated['tanggal_bergabung'],
                'kantor_id' => $validated['kantor_id'] ?? null,
                'atasan_id' => $validated['atasan_id'] ?? null,
                'jatah_cuti_tambahan' => $validated['jatah_cuti_tambahan'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
                'picture' => $validated['picture'] ?? null,
            ]);

            // Assign role
            $user->assignRole($validated['role']);

            DB::commit();

            return response()->json([
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => $user->load(['roles', 'kantor', 'atasan'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($validated['picture'])) {
                Storage::disk('public')->delete($validated['picture']);
            }
            return response()->json([
                'message' => 'Gagal menambahkan karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified employee
     */
    public function show($id)
    {
        $employee = User::with(['bawahan','kantor','atasan'])->findOrFail($id);

        $totalHadir = $employee->attendances()
            ->whereBetween('tanggal',[now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $terlambat = $employee->attendances()
            ->whereBetween('tanggal',[now()->startOfMonth(), now()->endOfMonth()])
            ->whereRaw('TIME(check_in) > jadwal_checkin')
            ->count();

        $outOffice = $employee->attendances()
            ->whereBetween('tanggal',[now()->startOfMonth(), now()->endOfMonth()])
            ->where('out_of_office',1)
            ->count();

        $totalCuti = $employee->attendances()
            ->where('status_cuti','approved')
            ->sum('lama_cuti');

        $sisaCuti = max(0, 12 + $employee->jatah_cuti_tambahan - $totalCuti);

       return response()->json([
        'employee' => $employee,
        'stats' => [
            'total_bawahan' => $employee->bawahan->count(),
            'sisa_cuti' => $sisaCuti,
            'total_absensi_bulan_ini' => $totalHadir,
            'keterlambatan_bulan_ini' => $terlambat,
            'masa_kerja' => $employee->getMasaKerja()
        ]
    ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'company' => 'required|in:' . implode(',', User::COMPANIES),
            'posisi' => 'required|string|max:255',
            'role' => 'required|string|exists:roles,name',
            'kantor_id' => 'nullable|exists:kantors,id',
            'atasan_id' => 'nullable|exists:users,id',
            'tanggal_bergabung' => 'required|date',
            'jatah_cuti_tambahan' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Prevent self as atasan
        if (isset($validated['atasan_id']) && $validated['atasan_id'] == $id) {
            return response()->json([
                'message' => 'Karyawan tidak bisa menjadi atasan diri sendiri'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Handle picture upload
            if ($request->hasFile('picture')) {
                // Delete old picture
                if ($user->picture) {
                    Storage::disk('public')->delete($user->picture);
                }
                $validated['picture'] = $request->file('picture')->store('employees', 'public');
            }

            // Update user
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'],
                'posisi' => $validated['posisi'],
                'tanggal_bergabung' => $validated['tanggal_bergabung'],
                'kantor_id' => $validated['kantor_id'] ?? null,
                'atasan_id' => $validated['atasan_id'] ?? null,
                'jatah_cuti_tambahan' => $validated['jatah_cuti_tambahan'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (isset($validated['picture'])) {
                $user->picture = $validated['picture'];
                $user->save();
            }

            // Update password if provided
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->save();
            }

            // Sync role
            $user->syncRoles([$validated['role']]);

            DB::commit();

            return response()->json([
                'message' => 'Karyawan berhasil diperbarui',
                'data' => $user->load(['roles', 'kantor', 'atasan'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified employee
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Check if user has bawahan
        if ($user->bawahan()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus karyawan yang memiliki bawahan. Silakan pindahkan bawahan terlebih dahulu.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete picture
            if ($user->picture) {
                Storage::disk('public')->delete($user->picture);
            }

            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Karyawan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee hierarchy
     */
    public function hierarchy()
    {
        // Get all users without atasan (top level)
        $topLevel = User::with(['roles', 'kantor'])
            ->whereNull('atasan_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Recursively load bawahan
        $hierarchy = $topLevel->map(function($user) {
            return $this->buildHierarchy($user);
        });

        return response()->json($hierarchy);
    }

    /**
     * Build hierarchy recursively
     */
    private function buildHierarchy($user)
    {
        $bawahan = $user->bawahan()
            ->with(['roles', 'kantor'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'company' => $user->company,
            'posisi' => $user->posisi,
            'picture' => $user->picture_url,
            'role' => $user->roles->first()?->name,
            'kantor' => $user->kantor,
            'masa_kerja' => $user->getMasaKerja(),
            'bawahan' => $bawahan->map(fn($b) => $this->buildHierarchy($b))
        ];
    }

    /**
     * Get statistics
     */
    public function stats()
    {
        $stats = [
            'total_employees' => User::count(),
            'active_employees' => User::where('is_active', true)->count(),
            'inactive_employees' => User::where('is_active', false)->count(),
            'by_role' => User::select('roles.name', DB::raw('count(*) as total'))
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', User::class)
                ->groupBy('roles.name')
                ->get(),
            'by_company' => User::select('company', DB::raw('count(*) as total'))
                ->whereNotNull('company')
                ->groupBy('company')
                ->get(),
            'by_office' => User::select('kantors.nama', DB::raw('count(*) as total'))
                ->leftJoin('kantors', 'users.kantor_id', '=', 'kantors.id')
                ->whereNotNull('kantors.nama')
                ->groupBy('kantors.id', 'kantors.nama')
                ->get(),
            'by_position' => User::select('posisi', DB::raw('count(*) as total'))
                ->whereNotNull('posisi')
                ->groupBy('posisi')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get(),
            'with_atasan' => User::whereNotNull('atasan_id')->count(),
            'without_atasan' => User::whereNull('atasan_id')->count(),
            'new_this_month' => User::whereMonth('tanggal_bergabung', now()->month)
                ->whereYear('tanggal_bergabung', now()->year)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get form options
     */
    public function getOptions()
    {
        return response()->json([
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'offices' => Kantor::where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'alamat']),
            'companies' => User::COMPANIES,
            'positions' => User::POSITIONS,
            'potential_atasan' => User::with('roles')
                ->where('is_active', true)
                ->whereHas('roles', function($q) {
                    $q->whereIn('name', ['Admin', 'HR', 'Direktur', 'Digital Lead']);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'posisi'])
        ]);
    }

    /**
     * Toggle employee status
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => 'Status karyawan berhasil diubah',
            'data' => $user
        ]);
    }

    /**
     * Export employees data
     */
    public function export(Request $request)
    {
        $employees = User::with(['roles', 'kantor', 'atasan'])
            ->when($request->company, fn($q) => $q->where('company', $request->company))
            ->when($request->kantor_id, fn($q) => $q->where('kantor_id', $request->kantor_id))
            ->when($request->role, fn($q) => $q->role($request->role))
            ->orderBy('name')
            ->get();

        $data = $employees->map(function($emp) {
            return [
                'Nama' => $emp->name,
                'Email' => $emp->email,
                'Telepon' => $emp->phone,
                'Perusahaan' => $emp->company,
                'Posisi' => $emp->posisi,
                'Role' => $emp->role_name,
                'Kantor' => $emp->kantor_name,
                'Atasan' => $emp->atasan_name,
                'Tanggal Bergabung' => $emp->tanggal_bergabung?->format('d/m/Y'),
                'Masa Kerja' => $emp->getMasaKerja(),
                'Status' => $emp->is_active ? 'Aktif' : 'Nonaktif'
            ];
        });

        return response()->json([
            'data' => $data,
            'filename' => 'data-karyawan-' . now()->format('Y-m-d') . '.csv'
        ]);
    }
}