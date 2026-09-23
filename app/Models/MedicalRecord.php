<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'patient_id', 'appointment_id', 'health_professional_id', 'created_by',
        'professional_name_snapshot', 'author_name_snapshot', 'record_type', 'chief_complaint',
        'subjective', 'objective', 'assessment', 'plan', 'recorded_at', 'signed_at',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'signed_at' => 'datetime'];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function healthProfessional(): BelongsTo { return $this->belongsTo(HealthProfessional::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function vitalSigns(): HasMany { return $this->hasMany(PatientVitalSign::class); }
    public function diagnoses(): HasMany { return $this->hasMany(PatientDiagnosis::class); }
    public function prescriptions(): HasMany { return $this->hasMany(PatientPrescription::class); }
    public function attachments(): HasMany { return $this->hasMany(ClinicalAttachment::class); }
}
