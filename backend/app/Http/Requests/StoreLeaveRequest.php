<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LeaveType;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => 'required|exists:leave_types,id',
            'tipe_durasi' => 'required|in:sehari,setengah_hari,lebih_dari_sehari',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'setengah_hari_tipe' => 'required_if:tipe_durasi,setengah_hari|nullable|in:pagi,siang',
            'alasan' => 'required|string|min:10|max:1000',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Jenis cuti harus dipilih',
            'leave_type_id.exists' => 'Jenis cuti tidak valid',
            'tipe_durasi.required' => 'Durasi cuti harus dipilih',
            'tipe_durasi.in' => 'Durasi cuti tidak valid',
            'tanggal_mulai.required' => 'Tanggal mulai harus diisi',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh kurang dari hari ini',
            'tanggal_selesai.required' => 'Tanggal selesai harus diisi',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai',
            'setengah_hari_tipe.required_if' => 'Tipe waktu harus dipilih untuk cuti setengah hari',
            'alasan.required' => 'Alasan cuti harus diisi',
            'alasan.min' => 'Alasan cuti minimal 10 karakter',
            'alasan.max' => 'Alasan cuti maksimal 1000 karakter',
            'dokumen_pendukung.mimes' => 'Dokumen harus berformat PDF, JPG, JPEG, atau PNG',
            'dokumen_pendukung.max' => 'Ukuran dokumen maksimal 2MB',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validasi tanggal untuk sehari/setengah hari
            if (in_array($this->tipe_durasi, ['sehari', 'setengah_hari'])) {
                if ($this->tanggal_mulai !== $this->tanggal_selesai) {
                    $validator->errors()->add('tanggal_selesai', 
                        'Untuk cuti sehari/setengah hari, tanggal mulai dan selesai harus sama');
                }
            }

            // Validasi dokumen untuk jenis cuti tertentu
            if ($this->leave_type_id) {
                $leaveType = LeaveType::find($this->leave_type_id);
                if ($leaveType && $leaveType->isRequiresDocument() && !$this->hasFile('dokumen_pendukung')) {
                    $validator->errors()->add('dokumen_pendukung', 
                        "Jenis cuti {$leaveType->nama} memerlukan dokumen pendukung");
                }
            }
        });
    }
}

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leave = $this->route('leave');
        return $leave && $leave->user_id === $this->user()->id && $leave->status === 'pending';
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => 'required|exists:leave_types,id',
            'tipe_durasi' => 'required|in:sehari,setengah_hari,lebih_dari_sehari',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'setengah_hari_tipe' => 'required_if:tipe_durasi,setengah_hari|nullable|in:pagi,siang',
            'alasan' => 'required|string|min:10|max:1000',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Jenis cuti harus dipilih',
            'leave_type_id.exists' => 'Jenis cuti tidak valid',
            'tipe_durasi.required' => 'Durasi cuti harus dipilih',
            'tipe_durasi.in' => 'Durasi cuti tidak valid',
            'tanggal_mulai.required' => 'Tanggal mulai harus diisi',
            'tanggal_selesai.required' => 'Tanggal selesai harus diisi',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai',
            'setengah_hari_tipe.required_if' => 'Tipe waktu harus dipilih untuk cuti setengah hari',
            'alasan.required' => 'Alasan cuti harus diisi',
            'alasan.min' => 'Alasan cuti minimal 10 karakter',
            'alasan.max' => 'Alasan cuti maksimal 1000 karakter',
            'dokumen_pendukung.mimes' => 'Dokumen harus berformat PDF, JPG, JPEG, atau PNG',
            'dokumen_pendukung.max' => 'Ukuran dokumen maksimal 2MB',
        ];
    }
}