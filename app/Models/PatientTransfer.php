<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'from_clinic_id', 'to_clinic_id', 'requested_by', 'responded_by',
        'status', 'patient_consent_at', 'reason', 'response_notes', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'patient_consent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function fromClinic(): BelongsTo { return $this->belongsTo(Clinic::class, 'from_clinic_id'); }
    public function toClinic(): BelongsTo { return $this->belongsTo(Clinic::class, 'to_clinic_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function respondedBy(): BelongsTo { return $this->belongsTo(User::class, 'responded_by'); }
}
