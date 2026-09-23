<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToClinic
{
    protected static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $builder): void {
            if (Auth::check() && Auth::user()?->clinic_id) {
                $builder->where($builder->getModel()->qualifyColumn('clinic_id'), Auth::user()->clinic_id);
            }
        });

        static::creating(function ($model): void {
            if (empty($model->clinic_id) && Auth::check() && Auth::user()?->clinic_id) {
                $model->clinic_id = Auth::user()->clinic_id;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
