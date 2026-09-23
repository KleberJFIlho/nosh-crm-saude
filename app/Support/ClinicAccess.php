<?php

namespace App\Support;

use App\Models\ClinicAuditLog;
use Illuminate\Support\Facades\Auth;

class ClinicAccess
{
    public static function allow(string $permission): void
    {
        abort_unless(Auth::check() && Auth::user()->canAccess($permission), 403, 'Sem permissão para esta operação.');
    }

    public static function clinicId(): int
    {
        abort_unless(Auth::check() && Auth::user()->clinic_id, 403, 'Utilizador sem clínica associada.');
        return (int) Auth::user()->clinic_id;
    }

    public static function audit(string $action, ?string $type = null, ?int $id = null, array $metadata = []): void
    {
        if (! Auth::check() || ! Auth::user()->clinic_id) {
            return;
        }

        ClinicAuditLog::create([
            'clinic_id' => Auth::user()->clinic_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
