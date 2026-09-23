<?php
namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientVitalSign extends Model
{
    use BelongsToClinic;
    protected $fillable = ['clinic_id','patient_id','medical_record_id','created_by','author_name_snapshot','weight_kg','height_cm','temperature_c','systolic_bp','diastolic_bp','heart_rate','respiratory_rate','oxygen_saturation','glucose_mg_dl','notes','measured_at'];
    protected function casts(): array { return ['measured_at'=>'datetime','weight_kg'=>'decimal:2','height_cm'=>'decimal:2','temperature_c'=>'decimal:1']; }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function medicalRecord(): BelongsTo { return $this->belongsTo(MedicalRecord::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function getBmiAttribute(): ?float
    {
        $heightM = $this->height_cm ? ((float)$this->height_cm / 100) : 0;
        return $this->weight_kg && $heightM > 0 ? round((float)$this->weight_kg / ($heightM * $heightM), 1) : null;
    }
}
