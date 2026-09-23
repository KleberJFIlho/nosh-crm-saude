<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'email', 'password', 'active', 'email_verified_at', 'last_login_at',
        'must_change_password', 'temporary_password_expires_at', 'temporary_password_used_at',
        'credentials_sent_at', 'credentials_sent_via', 'password_changed_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'temporary_password_expires_at' => 'datetime',
            'temporary_password_used_at' => 'datetime',
            'credentials_sent_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withoutGlobalScope('clinic');
    }
}
