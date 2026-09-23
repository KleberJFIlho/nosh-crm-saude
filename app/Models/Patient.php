<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory, BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'first_name', 'last_name', 'email', 'phone', 'birth_date', 'gender',
        'status', 'source', 'last_contact_at', 'next_follow_up_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_contact_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function interactions(): HasMany { return $this->hasMany(PatientInteraction::class); }
    public function tasks(): HasMany { return $this->hasMany(PatientTask::class); }
    public function medicalRecords(): HasMany { return $this->hasMany(MedicalRecord::class); }
    public function vitalSigns(): HasMany { return $this->hasMany(PatientVitalSign::class); }
    public function allergies(): HasMany { return $this->hasMany(PatientAllergy::class); }
    public function diagnoses(): HasMany { return $this->hasMany(PatientDiagnosis::class); }
    public function prescriptions(): HasMany { return $this->hasMany(PatientPrescription::class); }
    public function clinicalAttachments(): HasMany { return $this->hasMany(ClinicalAttachment::class); }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
