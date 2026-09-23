<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HealthProfessional extends Model
{
    use BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'user_id', 'professional_type', 'full_name',
        'registration_number', 'specialty', 'email', 'phone',
        'photo_path', 'active', 'notes',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match ($this->professional_type) {
            'doctor' => 'Médico',
            'nurse' => 'Enfermeiro',
            'assistant' => 'Auxiliar',
            default => ucfirst($this->professional_type),
        };
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->full_name)) ?: [];
        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'PS';
    }
}
