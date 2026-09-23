<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'clinic_id', 'user_id', 'action', 'auditable_type', 'auditable_id',
        'metadata', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo { return $this->belongsTo(Clinic::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
