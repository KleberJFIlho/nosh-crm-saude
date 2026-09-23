<?php

namespace App\Support;

use App\Models\PatientAccount;
use App\Models\ClinicAuditLog;
use Illuminate\Http\Request;

class PatientPortalSession
{
    public const KEY = 'nosh_patient_account_id';

    public static function account(Request $request): ?PatientAccount
    {
        $id = $request->session()->get(self::KEY);
        if (! $id) return null;

        $account = PatientAccount::with(['patient.clinic'])->where('active', true)->find($id);
        if (! $account || ! $account->patient || $account->patient->status !== 'active' || ! $account->patient->clinic?->active) return null;
        return $account;
    }

    public static function require(Request $request): PatientAccount
    {
        $account = self::account($request);
        abort_unless($account && $account->patient && $account->patient->status === 'active', 401, 'Sessão do paciente inválida.');
        return $account;
    }

    public static function login(Request $request, PatientAccount $account): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, $account->id);
    }

    public static function audit(Request $request, PatientAccount $account, string $action, ?string $type = null, ?int $id = null, array $metadata = []): void
    {
        $patient = $account->patient;
        if (! $patient?->clinic_id) return;
        ClinicAuditLog::create([
            'clinic_id' => $patient->clinic_id,
            'user_id' => null,
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'metadata' => array_merge(['patient_account_id' => $account->id, 'patient_id' => $account->patient_id], $metadata) ?: null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    public static function logout(Request $request): void
    {
        $request->session()->forget(self::KEY);
        $request->session()->regenerateToken();
    }
}
