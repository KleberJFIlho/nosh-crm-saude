<?php
namespace App\Models;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClinicalAttachment extends Model
{
    use BelongsToClinic;
    protected $fillable=['clinic_id','patient_id','medical_record_id','uploaded_by','uploader_name_snapshot','original_name','path','mime_type','size_bytes','description'];
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function medicalRecord(): BelongsTo { return $this->belongsTo(MedicalRecord::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class,'uploaded_by'); }
    public function humanSize(): string
    {
        $bytes=(int)$this->size_bytes;
        if ($bytes < 1024) return $bytes.' B';
        if ($bytes < 1048576) return number_format($bytes/1024,1,',','.').' KB';
        return number_format($bytes/1048576,1,',','.').' MB';
    }
}
