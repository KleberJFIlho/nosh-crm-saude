<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAccount;
use App\Services\PatientCredentialDeliveryService;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PatientPortalAccessController extends Controller
{
    public function edit(Patient $patient, PatientCredentialDeliveryService $delivery): View
    {
        $this->authorizeManagement();
        $account = PatientAccount::where('patient_id', $patient->id)->first();

        return view('patient-portal.access', [
            'patient' => $patient,
            'account' => $account,
            'maskedEmail' => $this->maskEmail($patient->email),
            'maskedPhone' => $this->maskPhone($patient->phone),
            'emailAvailable' => $delivery->emailAvailable($patient),
            'smsAvailable' => $delivery->smsAvailable($patient),
            'smsHasPhone' => filled($patient->phone),
        ]);
    }

    public function update(Request $request, Patient $patient, PatientCredentialDeliveryService $delivery): RedirectResponse
    {
        $this->authorizeManagement();
        $account = PatientAccount::where('patient_id', $patient->id)->first();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190', Rule::unique('patient_accounts', 'email')->ignore($account?->id)],
            'active' => ['nullable', 'boolean'],
            'action' => ['required', Rule::in(['save', 'send'])],
            'delivery_channel' => ['nullable', Rule::in(['email', 'sms'])],
        ], [
            'email.required' => 'Informe o e-mail que o paciente utilizará para entrar no portal.',
            'email.email' => 'Informe um e-mail de acesso válido.',
            'email.unique' => 'Este e-mail de acesso já está associado a outra conta.',
        ]);

        $account ??= new PatientAccount(['patient_id' => $patient->id]);
        $account->email = Str::lower($data['email']);
        $account->active = $request->boolean('active');

        // Uma conta nova recebe um segredo aleatório inutilizável até a credencial provisória ser enviada.
        if (! $account->exists && blank($account->password)) {
            $account->password = Str::random(64);
        }
        $account->save();

        ClinicAccess::audit('patient_portal.access_updated', PatientAccount::class, $account->id, [
            'patient_id' => $patient->id,
            'active' => $account->active,
            'password_exposed_to_staff' => false,
        ]);

        if (($data['action'] ?? 'save') === 'save') {
            return back()->with('success', 'Configuração de acesso guardada.');
        }

        if (! $account->active) {
            throw ValidationException::withMessages(['active' => 'Ative o acesso antes de gerar e enviar uma credencial provisória.']);
        }

        $channel = (string) ($data['delivery_channel'] ?? '');
        if ($channel === '') {
            throw ValidationException::withMessages(['delivery_channel' => 'Selecione um canal seguro já cadastrado do paciente.']);
        }
        if ($channel === 'email' && ! $delivery->emailAvailable($patient)) {
            throw ValidationException::withMessages(['delivery_channel' => 'O paciente não possui e-mail cadastrado. Atualize o cadastro antes de enviar.']);
        }
        if ($channel === 'sms' && ! filled($patient->phone)) {
            throw ValidationException::withMessages(['delivery_channel' => 'O paciente não possui telefone cadastrado. Atualize o cadastro antes de enviar.']);
        }
        if ($channel === 'sms' && ! $delivery->smsAvailable($patient)) {
            throw ValidationException::withMessages(['delivery_channel' => 'O telefone está cadastrado, mas o gateway SMS ainda não foi configurado.']);
        }

        $limit = (int) config('noshcrm.patient_portal.max_credential_sends', 3);
        $decay = (int) config('noshcrm.patient_portal.credential_send_window_seconds', 900);
        $rateKey = 'patient-credential|'.$patient->id.'|'.$channel.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
            throw ValidationException::withMessages([
                'delivery_channel' => 'Limite de reenvios atingido. Tente novamente em '.RateLimiter::availableIn($rateKey).' segundos.',
            ]);
        }

        $minutes = max(5, (int) config('noshcrm.patient_portal.temporary_password_minutes', 30));
        $temporaryPassword = $this->generateTemporaryPassword();

        try {
            $destination = $delivery->deliver($channel, $patient, $account, $temporaryPassword, $minutes);
        } catch (Throwable $e) {
            RateLimiter::hit($rateKey, 60);
            ClinicAccess::audit('patient_portal.credential_delivery_failed', PatientAccount::class, $account->id, [
                'patient_id' => $patient->id,
                'channel' => $channel,
                'destination' => $channel === 'email' ? $this->maskEmail($patient->email) : $this->maskPhone($patient->phone),
                'password_exposed_to_staff' => false,
                'error_class' => $e::class,
            ]);
            throw ValidationException::withMessages(['delivery_channel' => 'Não foi possível enviar a credencial: '.$e->getMessage()]);
        }

        // A palavra-passe em texto simples existe somente em memória até este ponto.
        // O cast "hashed" do model garante que apenas o hash é persistido.
        $account->password = $temporaryPassword;
        $account->must_change_password = true;
        $account->temporary_password_expires_at = now()->addMinutes($minutes);
        $account->temporary_password_used_at = null;
        $account->credentials_sent_at = now();
        $account->credentials_sent_via = $channel;
        $account->save();

        RateLimiter::hit($rateKey, $decay);
        ClinicAccess::audit('patient_portal.credential_sent', PatientAccount::class, $account->id, [
            'patient_id' => $patient->id,
            'channel' => $channel,
            'destination' => $channel === 'email' ? $this->maskEmail($destination) : $this->maskPhone($destination),
            'expires_at' => $account->temporary_password_expires_at?->toIso8601String(),
            'temporary' => true,
            'single_use' => true,
            'must_change_password' => true,
            'password_exposed_to_staff' => false,
        ]);

        return back()->with('success', 'Credencial provisória enviada com segurança para '.($channel === 'email' ? $this->maskEmail($destination) : $this->maskPhone($destination)).'. Validade: '.$minutes.' minutos.');
    }

    private function authorizeManagement(): void
    {
        ClinicAccess::allow('patients.edit');
        abort_unless(in_array(auth()->user()->role, ['admin', 'manager'], true), 403, 'Apenas Administrador ou Gestor pode gerir o acesso do paciente.');
    }

    private function generateTemporaryPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '@#$%&*!?';
        $all = $upper.$lower.$digits.$symbols;
        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];
        while (count($chars) < 16) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }

    private function maskEmail(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) return null;
        [$local, $domain] = explode('@', $email, 2);
        $first = mb_substr($local, 0, 1);
        return $first.str_repeat('*', max(3, min(8, mb_strlen($local) - 1))).'@'.$domain;
    }

    private function maskPhone(?string $phone): ?string
    {
        if (blank($phone)) return null;
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') return null;
        $last = substr($digits, -3);
        $prefix = str_starts_with(trim($phone), '+') && strlen($digits) > 9 ? '+'.substr($digits, 0, strlen($digits) - 9).' ' : '';
        return trim($prefix.'*** *** '.$last);
    }
}
