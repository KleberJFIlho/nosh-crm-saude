<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory, BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'name', 'email', 'phone', 'interest', 'source', 'status', 'score',
        'estimated_value', 'next_contact_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'next_contact_at' => 'datetime',
        ];
    }
}
