<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'patient_id', 'health_professional_id', 'scheduled_at', 'type', 'status',
        'professional', 'location', 'notes', 'patient_rescheduled_at', 'patient_reschedule_count',
        'patient_cancelled_at', 'patient_cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'patient_rescheduled_at' => 'datetime',
            'patient_cancelled_at' => 'datetime',
            'patient_reschedule_count' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function healthProfessional(): BelongsTo
    {
        return $this->belongsTo(HealthProfessional::class, 'health_professional_id');
    }

    public function professionalName(): string
    {
        return $this->healthProfessional?->full_name ?: ($this->professional ?: 'Profissional não definido');
    }
}
