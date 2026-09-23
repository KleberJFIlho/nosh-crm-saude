<?php
namespace App\Models;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PatientDiagnosis extends Model
{
    use BelongsToClinic;
    protected $fillable=['clinic_id','patient_id','medical_record_id','health_professional_id','created_by','professional_name_snapshot','author_name_snapshot','code','description','diagnosis_type','status','diagnosed_at','notes'];
    protected function casts(): array { return ['diagnosed_at'=>'date']; }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function medicalRecord(): BelongsTo { return $this->belongsTo(MedicalRecord::class); }
    public function healthProfessional(): BelongsTo { return $this->belongsTo(HealthProfessional::class); }
}
