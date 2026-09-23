<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory, BelongsToClinic;

    protected $fillable = [
        'clinic_id','patient_id','uploaded_by','title','category','laboratory','exam_date',
        'status','summary','file_path','original_name','mime_type','file_size','released_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'released_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function isReleased(): bool { return $this->released_at !== null && $this->status === 'released'; }
}
