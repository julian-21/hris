<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'jatah_cuti_tambahan',
        'picture',
        'company',
        'posisi',
        'tanggal_bergabung',
        'is_active',
        'atasan_id',
        'kantor_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['role_name', 'kantor_name', 'atasan_name'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'jatah_cuti_tambahan' => 'decimal:2',
            'tanggal_bergabung' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    // Constants untuk company options
    const COMPANIES = [
        'PT Lintas Karya Sejahtera',
        'PT Karya Sukses Bersama',
        'PT Mitra Solusi Indonesia',
        'PT Teknologi Nusantara'
    ];

    // Constants untuk posisi/jabatan berdasarkan data yang Anda berikan
    const POSITIONS = [
        'WELDER STAINLESS STEEL',
        'STAINLESS STEEL WELDER',
        'STAINLESS STEEL',
        'Stainless Welder',
        'Welder',
        'Staff Welder',
        'POCOKAN WELDER',
        'POCOKAN WELDER PRODUKSI',
        'Magang Admin',
        'Magang Administrasi',
        'Magang Inteligent Marketing',
        'Magang Markom',
        'Magang Bisdev',
        'Magang CSO',
        'Human Capital',
        'Staff Engineer',
        'Staff GA',
        'GA (IT SUPPORT)',
        'Staff IT SUPPORT',
        'IT SUPPORT',
        'PC',
        'Project Consultant',
        'PROJECT CONSULTANT',
        'Staff PC',
    ];

    // Relationships
    public function atasan()
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function bawahan()
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    public function kantor()
    {
        return $this->belongsTo(Kantor::class, 'kantor_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Accessors
    public function getRoleNameAttribute()
    {
        return $this->roles->first()?->name;
    }

    public function getKantorNameAttribute()
    {
        return $this->kantor?->nama;
    }

    public function getAtasanNameAttribute()
    {
        return $this->atasan?->name;
    }

    public function getPictureUrlAttribute()
    {
        if ($this->picture) {
            return asset('storage/' . $this->picture);
        }
        return $this->getDefaultAvatar();
    }

    public function getDefaultAvatar()
    {
        // Generate avatar berdasarkan initial nama
        $initials = collect(explode(' ', $this->name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->join('');
        
        return "https://ui-avatars.com/api/?name={$initials}&background=random&color=fff&size=200";
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->hasRole('Admin');
    }

    public function isHR()
    {
        return $this->hasRole('HR');
    }

    public function isDirektur()
    {
        return $this->hasRole('Direktur');
    }

    public function isKaryawan()
    {
        return $this->hasRole('Karyawan');
    }

    public function canApprove()
    {
        return $this->hasAnyRole(['Admin', 'HR', 'Direktur', 'Digital Lead']) || 
               $this->bawahan()->exists();
    }

    public function getMasaKerja()
    {
        if (!$this->tanggal_bergabung) {
            return null;
        }

        $bergabung = $this->tanggal_bergabung;
        $now = now();

        $years = $bergabung->diffInYears($now);
        $months = $bergabung->copy()->addYears($years)->diffInMonths($now);

        if ($years > 0) {
            return $years . ' tahun ' . ($months > 0 ? $months . ' bulan' : '');
        }
        
        return $months . ' bulan';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByCompany($query, $company)
    {
        return $query->where('company', $company);
    }

    public function scopeByPosition($query, $posisi)
    {
        return $query->where('posisi', $posisi);
    }

    public function lemburs()
    {
        return $this->hasMany(Lembur::class);
    }
}