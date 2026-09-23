<?php

namespace App\Support;

use App\Models\HealthProfessional;
use Illuminate\Database\Eloquent\Builder;

class ClinicalAccess
{
    public static function professionalsQuery(): Builder
    {
        $query = HealthProfessional::query()->where('active', true);

        if (auth()->user()?->role === 'health_professional') {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function professional(int $id): HealthProfessional
    {
        $professional = self::professionalsQuery()->whereKey($id)->first();
        abort_unless($professional, 403, 'O profissional selecionado não está autorizado para este utilizador/clínica.');
        return $professional;
    }
}
