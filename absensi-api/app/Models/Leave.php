<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'tipe_durasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'setengah_hari_tipe',
        'jumlah_hari',
        'alasan',
        'dokumen_pendukung',
        'status',
        'approved_by',
        'catatan_approval',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'approved_at' => 'datetime',
        'jumlah_hari' => 'decimal:1',
    ];

    protected $appends = ['status_label', 'tipe_durasi_label'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => $this->status
        };
    }

    public function getTipeDurasiLabelAttribute()
    {
        $label = match($this->tipe_durasi) {
            'sehari' => 'Sehari',
            'setengah_hari' => 'Setengah Hari',
            'lebih_dari_sehari' => 'Lebih dari Sehari',
            default => $this->tipe_durasi
        };

        if ($this->tipe_durasi === 'setengah_hari' && $this->setengah_hari_tipe) {
            $label .= ' (' . ucfirst($this->setengah_hari_tipe) . ')';
        }

        return $label;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
              ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('tanggal_mulai', '<=', $startDate)
                     ->where('tanggal_selesai', '>=', $endDate);
              });
        });
    }

    public function scopeByLeaveType($query, $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }

    // Static Methods
    public static function hitungJumlahHari($tanggalMulai, $tanggalSelesai, $tipeDurasi, $setengahHariTipe = null)
    {
        if ($tipeDurasi === 'setengah_hari') {
            return 0.5;
        }

        if ($tipeDurasi === 'sehari') {
            return 1;
        }

        // Hitung hari kerja (exclude weekend)
        $start = Carbon::parse($tanggalMulai);
        $end = Carbon::parse($tanggalSelesai);
        $jumlahHari = 0;

        while ($start->lte($end)) {
            if (!$start->isWeekend()) {
                $jumlahHari++;
            }
            $start->addDay();
        }

        return $jumlahHari;
    }

    // Instance Methods
    public function isOverlapping($userId, $tanggalMulai, $tanggalSelesai, $excludeId = null)
    {
        $query = self::where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->where(function($q) use ($tanggalMulai, $tanggalSelesai) {
                $q->whereBetween('tanggal_mulai', [$tanggalMulai, $tanggalSelesai])
                  ->orWhereBetween('tanggal_selesai', [$tanggalMulai, $tanggalSelesai])
                  ->orWhere(function($q2) use ($tanggalMulai, $tanggalSelesai) {
                      $q2->where('tanggal_mulai', '<=', $tanggalMulai)
                         ->where('tanggal_selesai', '>=', $tanggalSelesai);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function canBeEdited()
    {
        return $this->status === 'pending';
    }

    public function canBeDeleted()
    {
        return $this->status === 'pending';
    }
}