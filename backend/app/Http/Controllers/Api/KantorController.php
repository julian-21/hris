<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kantor;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\NotificationHelper; // ✅ TAMBAHKAN INI

class KantorController extends Controller
{
    /**
     * Get all kantors
     */
    public function index(Request $request)
    {
        $query = Kantor::query();

        // Filter active only
        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Sort
        $query->orderBy('nama', 'asc');

        // Return all or paginated
        if ($request->has('per_page')) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    /**
     * Get single kantor
     */
    public function show($id)
    {
        $kantor = Kantor::findOrFail($id);
        return response()->json($kantor);
    }

    /**
     * Create new kantor
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:1',
            'is_active' => 'boolean',
        ]);

        $kantor = Kantor::create($request->all());

        // ✅ NOTIFIKASI: Kirim ke semua user (opsional, atau hanya ke HR/Admin)
        $hrAdmins = User::role(['Admin', 'HR', 'Direktur'])->pluck('id')->toArray();
        NotificationHelper::createForMultiple(
            $hrAdmins,
            'kantor_created',
            'Kantor Baru Ditambahkan',
            "Kantor baru '{$kantor->nama}' telah ditambahkan ke sistem.",
            ['kantor_id' => $kantor->id, 'kantor_nama' => $kantor->nama]
        );

        return response()->json([
            'message' => 'Kantor berhasil ditambahkan',
            'data' => $kantor
        ], 201);
    }

    /**
     * Update kantor
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'alamat' => 'sometimes|required|string',
            'latitude' => 'sometimes|required|numeric',
            'longitude' => 'sometimes|required|numeric',
            'radius' => 'sometimes|required|numeric|min:1',
            'is_active' => 'boolean',
        ]);

        $kantor = Kantor::findOrFail($id);
        $oldData = [
            'nama' => $kantor->nama,
            'alamat' => $kantor->alamat,
            'radius' => $kantor->radius,
            'is_active' => $kantor->is_active
        ];
        
        $kantor->update($request->all());

        // ✅ NOTIFIKASI: Kirim ke karyawan yang terdaftar di kantor ini (jika ada perubahan penting)
        $changes = [];
        if (isset($request->nama) && $oldData['nama'] !== $request->nama) {
            $changes[] = "nama kantor dari '{$oldData['nama']}' ke '{$request->nama}'";
        }
        if (isset($request->alamat) && $oldData['alamat'] !== $request->alamat) {
            $changes[] = "alamat";
        }
        if (isset($request->radius) && $oldData['radius'] !== $request->radius) {
            $changes[] = "radius dari {$oldData['radius']}m ke {$request->radius}m";
        }
        if (isset($request->is_active) && $oldData['is_active'] !== $request->is_active) {
            $statusText = $request->is_active ? 'diaktifkan' : 'dinonaktifkan';
            $changes[] = "status kantor {$statusText}";
        }

        if (!empty($changes)) {
            // Kirim notifikasi ke semua karyawan yang bekerja di kantor ini
            $employeesAtOffice = User::where('kantor_id', $kantor->id)->pluck('id')->toArray();
            
            if (!empty($employeesAtOffice)) {
                NotificationHelper::createForMultiple(
                    $employeesAtOffice,
                    'kantor_updated',
                    'Informasi Kantor Diperbarui',
                    "Informasi kantor '{$kantor->nama}' telah diperbarui: " . implode(', ', $changes) . ".",
                    ['kantor_id' => $kantor->id, 'changes' => $changes]
                );
            }
        }

        return response()->json([
            'message' => 'Kantor berhasil diupdate',
            'data' => $kantor
        ]);
    }

    /**
     * Delete kantor
     */
    public function destroy($id)
    {
        $kantor = Kantor::findOrFail($id);
        
        // ✅ Cek apakah ada karyawan yang terdaftar di kantor ini
        $employeesCount = User::where('kantor_id', $kantor->id)->count();
        
        if ($employeesCount > 0) {
            return response()->json([
                'message' => "Tidak dapat menghapus kantor karena masih ada {$employeesCount} karyawan yang terdaftar di kantor ini."
            ], 422);
        }

        $kantorNama = $kantor->nama;
        $kantor->delete();

        // ✅ NOTIFIKASI: Kirim ke HR/Admin
        $hrAdmins = User::role(['Admin', 'HR', 'Direktur'])->pluck('id')->toArray();
        NotificationHelper::createForMultiple(
            $hrAdmins,
            'kantor_deleted',
            'Kantor Dihapus',
            "Kantor '{$kantorNama}' telah dihapus dari sistem.",
            ['kantor_nama' => $kantorNama]
        );

        return response()->json([
            'message' => 'Kantor berhasil dihapus'
        ]);
    }

    /**
     * Check distance between user location and office
     */
    public function checkDistance(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $kantor = Kantor::findOrFail($id);

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $kantor->latitude,
            $kantor->longitude
        );

        $isWithinRadius = $distance <= $kantor->radius;

        $message = $isWithinRadius 
            ? 'Anda berada dalam radius kantor' 
            : 'Anda berada di luar radius kantor';

        return response()->json([
            'distance' => round($distance, 2),
            'is_within_radius' => $isWithinRadius,
            'message' => $message,
            'kantor' => [
                'id' => $kantor->id,
                'nama' => $kantor->nama,
                'radius' => $kantor->radius,
                'latitude' => $kantor->latitude,
                'longitude' => $kantor->longitude,
            ]
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in meters
     */
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