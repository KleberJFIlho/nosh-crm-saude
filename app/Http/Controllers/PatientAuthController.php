<?php

namespace App\Http\Controllers;

use App\Models\PatientAccount;
use App\Support\PatientPortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientAuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($account = PatientPortalSession::account($request)) {
            return redirect()->route($account->must_change_password ? 'patient.security' : 'patient.dashboard');
        }
        return view('patient-auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'patient|'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Aguarde '.RateLimiter::availableIn($key).' segundos.']);
        }

        $account = PatientAccount::with('patient')->where('email', Str::lower($data['email']))->where('active', true)->first();
        if (! $account || ! Hash::check($data['password'], $account->password) || ! $account->patient || $account->patient->status !== 'active') {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'E-mail ou palavra-passe inválidos.']);
        }

        if ($account->must_change_password) {
            if (! $account->temporary_password_expires_at || now()->greaterThan($account->temporary_password_expires_at)) {
                RateLimiter::hit($key, 60);
                throw ValidationException::withMessages(['email' => 'A palavra-passe provisória expirou. Solicite uma nova credencial à clínica.']);
            }
            if ($account->temporary_password_used_at) {
                RateLimiter::hit($key, 60);
                throw ValidationException::withMessages(['email' => 'A palavra-passe provisória já foi utilizada. Solicite uma nova credencial à clínica.']);
            }
        }

        RateLimiter::clear($key);
        PatientPortalSession::login($request, $account);
        $account->last_login_at = now();

        if ($account->must_change_password) {
            $account->temporary_password_used_at = now();
        }
        $account->save();

        PatientPortalSession::audit($request, $account, 'patient_portal.login', PatientAccount::class, $account->id, [
            'temporary_credential' => (bool) $account->must_change_password,
        ]);

        if ($account->must_change_password) {
            PatientPortalSession::audit($request, $account, 'patient_portal.temporary_password_used', PatientAccount::class, $account->id, [
                'single_use' => true,
                'must_change_password' => true,
            ]);
            return redirect()->route('patient.security')->with('warning', 'Primeiro acesso: defina agora uma nova palavra-passe para continuar.');
        }

        return redirect()->route('patient.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($account = PatientPortalSession::account($request)) {
            PatientPortalSession::audit($request, $account, 'patient_portal.logout', PatientAccount::class, $account->id);
        }
        PatientPortalSession::logout($request);
        return redirect()->route('patient.login');
    }
}
