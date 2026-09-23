<?php
namespace App\Models;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PatientPrescription extends Model
{
    use BelongsToClinic;
    protected $fillable=['clinic_id','patient_id','medical_record_id','health_professional_id','created_by','professional_name_snapshot','author_name_snapshot','medication','dosage','route','frequency','duration','instructions','starts_at','ends_at','status'];
    protected function casts(): array { return ['starts_at'=>'date','ends_at'=>'date']; }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function medicalRecord(): BelongsTo { return $this->belongsTo(MedicalRecord::class); }
    public function healthProfessional(): BelongsTo { return $this->belongsTo(HealthProfessional::class); }
}
