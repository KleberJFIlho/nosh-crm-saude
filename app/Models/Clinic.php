<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'city', 'district', 'phone', 'email', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function patients(): HasMany { return $this->hasMany(Patient::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
}
