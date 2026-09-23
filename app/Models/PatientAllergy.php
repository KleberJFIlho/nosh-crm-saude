<?php
namespace App\Models;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PatientAllergy extends Model
{
    use BelongsToClinic;
    protected $fillable=['clinic_id','patient_id','created_by','author_name_snapshot','substance','reaction','severity','status','identified_at'];
    protected function casts(): array { return ['identified_at'=>'date']; }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
