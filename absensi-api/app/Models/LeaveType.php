<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'keterangan',
        'jumlah_hari',
        'is_active',
    ];

    protected $casts = [
        'jumlah_hari' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function isRequiresDocument()
    {
        // Jenis cuti yang memerlukan dokumen pendukung
        $requiresDoc = ['Sakit', 'Cuti Haji', 'Cuti Umrah', 'Cuti Melahirkan'];
        return in_array($this->nama, $requiresDoc);
    }

    public function hasQuota()
    {
        return $this->jumlah_hari > 0;
    }
}