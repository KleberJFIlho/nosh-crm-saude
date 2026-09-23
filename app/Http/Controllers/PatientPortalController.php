<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ExamResult;
use App\Models\HealthProfessional;
use App\Models\PatientAccount;
use App\Services\PatientAppointmentPolicyService;
use App\Support\PatientPortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientPortalController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        if ($redirect = $this->forcePasswordChange($account)) return $redirect;

        $patient = $account->patient;
        $appointments = $patient->appointments()->withoutGlobalScope('clinic')->orderBy('scheduled_at')->get();
        $upcoming = $patient->appointments()->withoutGlobalScope('clinic')->where('scheduled_at', '>=', now())->whereNotIn('status', ['cancelled', 'completed', 'no_show'])->orderBy('scheduled_at')->first();
        $releasedExams = ExamResult::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->where('status', 'released')->whereNotNull('released_at')->latest('released_at')->limit(4)->get();
        return view('patient-portal.dashboard', compact('account', 'patient', 'upcoming', 'appointments', 'releasedExams'));
    }

    public function appointments(Request $request, PatientAppointmentPolicyService $policy): View|RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        if ($redirect = $this->forcePasswordChange($account)) return $redirect;

        $patient = $account->patient;
        $appointments = $patient->appointments()
            ->withoutGlobalScope('clinic')
            ->with('healthProfessional')
            ->orderByDesc('scheduled_at')
            ->get();

        $appointmentPolicies = $appointments->mapWithKeys(fn (Appointment $appointment) => [
            $appointment->id => [
                'can_cancel' => $policy->canCancelDirectly($appointment),
                'can_reschedule' => $policy->canReschedule($appointment),
                'cancel_deadline' => $policy->deadlineLabel($policy->directCancellationDeadline($appointment)),
                'reschedule_deadline' => $policy->deadlineLabel($policy->rescheduleDeadline($appointment)),
            ],
        ]);

        return view('patient-portal.appointments', compact('account', 'patient', 'appointments', 'appointmentPolicies'));
    }

    public function manageAppointment(Request $request, int $appointment, PatientAppointmentPolicyService $policy): View|RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        if ($redirect = $this->forcePasswordChange($account)) return $redirect;

        $item = $this->patientAppointment($account, $appointment);
        if (! $policy->canReschedule($item) && ! $policy->canCancelDirectly($item)) {
            return redirect()->route('patient.appointments')->with('error', 'O prazo para alterar esta consulta já terminou.');
        }

        $availability = $policy->canReschedule($item) ? $policy->availability($item) : [
            'mode' => 'none',
            'same_professional' => collect(),
            'same_specialty' => collect(),
            'has_availability' => false,
        ];

        $canCancelDirectly = $policy->canCancelDirectly($item);
        $canReschedule = $policy->canReschedule($item);
        $canExceptionalCancel = $policy->canCancelAfterFailedReschedule($item);
        $cancelDeadline = $policy->directCancellationDeadline($item);
        $rescheduleDeadline = $policy->rescheduleDeadline($item);

        return view('patient-portal/appointment-manage', [
            'account' => $account,
            'patient' => $account->patient,
            'appointment' => $item,
            'availability' => $availability,
            'canCancelDirectly' => $canCancelDirectly,
            'canReschedule' => $canReschedule,
            'canExceptionalCancel' => $canExceptionalCancel,
            'cancelDeadline' => $cancelDeadline,
            'rescheduleDeadline' => $rescheduleDeadline,
        ]);
    }

    public function rescheduleAppointment(Request $request, int $appointment, PatientAppointmentPolicyService $policy): RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        if ($redirect = $this->forcePasswordChange($account)) return $redirect;

        $item = $this->patientAppointment($account, $appointment);
        if (! $policy->canReschedule($item)) {
            throw ValidationException::withMessages(['appointment' => 'O prazo de reagendamento terminou. O reagendamento é permitido até 1 dia útil antes da consulta.']);
        }

        $data = $request->validate([
            'health_professional_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $slot = $policy->validateSelectedSlot($item, (int) $data['health_professional_id'], $data['scheduled_at']);
        if (! $slot) {
            throw ValidationException::withMessages(['scheduled_at' => 'Esta vaga já não está disponível ou não respeita a prioridade do mesmo profissional/especialidade. Atualize a página e escolha outra vaga.']);
        }

        $professional = HealthProfessional::withoutGlobalScope('clinic')
            ->where('clinic_id', $item->clinic_id)
            ->where('active', true)
            ->findOrFail((int) $slot['professional_id']);

        $old = [
            'scheduled_at' => $item->scheduled_at->toDateTimeString(),
            'health_professional_id' => $item->health_professional_id,
            'professional' => $item->professional,
            'status' => $item->status,
        ];

        DB::transaction(function () use ($item, $slot, $professional): void {
            $item->scheduled_at = $slot['scheduled_at'];
            $item->health_professional_id = $professional->id;
            $item->professional = $professional->full_name;
            $item->status = 'scheduled';
            $item->patient_rescheduled_at = now();
            $item->patient_reschedule_count = ((int) $item->patient_reschedule_count) + 1;
            $item->save();
        });

        PatientPortalSession::audit($request, $account, 'patient_portal.appointment_rescheduled', Appointment::class, $item->id, [
            'old' => $old,
            'new' => [
                'scheduled_at' => $item->scheduled_at->toDateTimeString(),
                'health_professional_id' => $item->health_professional_id,
                'professional' => $item->professional,
            ],
            'same_professional' => (bool) $slot['same_professional'],
            'specialty' => $slot['specialty'],
        ]);

        return redirect()->route('patient.appointments')->with('success', 'Consulta reagendada com sucesso.');
    }

    public function cancelAppointment(Request $request, int $appointment, PatientAppointmentPolicyService $policy): RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        if ($redirect = $this->forcePasswordChange($account)) return $redirect;

        $item = $this->patientAppointment($account, $appointment);
        $direct = $policy->canCancelDirectly($item);
        $exceptional = $policy->canCancelAfterFailedReschedule($item);

        if (! $direct && ! $exceptional) {
            throw ValidationException::withMessages([
                'appointment' => 'O cancelamento normal exige 2 dias úteis de antecedência. No último dia útil, o cancelamento só é permitido se o paciente tentou reagendar e não existe vaga com o mesmo profissional nem com profissional da mesma especialidade.',
            ]);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'confirm_cancel' => ['accepted'],
        ], [
            'confirm_cancel.accepted' => 'Confirme que deseja cancelar a consulta.',
        ]);

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            $reason = $exceptional
                ? 'Cancelamento após tentativa de reagendamento sem disponibilidade.'
                : 'Cancelamento solicitado pelo paciente no portal.';
        }

        $item->status = 'cancelled';
        $item->patient_cancelled_at = now();
        $item->patient_cancellation_reason = $reason;
        $item->save();

        PatientPortalSession::audit($request, $account, 'patient_portal.appointment_cancelled', Appointment::class, $item->id, [
            'scheduled_at' => $item->scheduled_at->toDateTimeString(),
            'professional' => $item->professional,
            'exception_after_failed_reschedule' => $exceptional,
            'reason' => $reason,
        ]);

        return redirect()->route('patient.appointments')->with('success', 'Consulta cancelada com sucesso.');
    }

    public function exams(Request $request): View|RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        if ($redirect = $this->forcePasswordChange($account)) return $redirect;

        $patient = $account->patient;
        $exams = ExamResult::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->where('status', 'released')->whereNotNull('released_at')->orderByDesc('exam_date')->get();
        return view('patient-portal.exams', compact('account', 'patient', 'exams'));
    }

    public function security(Request $request): View|RedirectResponse
    {
        $account = $this->account($request);
        if ($account instanceof RedirectResponse) return $account;
        return view('patient-portal.security', ['account' => $account, 'patient' => $account->patient]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $account = PatientPortalSession::account($request);
        if (! $account) return redirect()->route('patient.login');

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'A nova palavra-passe deve ser diferente da palavra-passe provisória/atual.',
        ]);

        if (! Hash::check($data['current_password'], $account->password)) {
            throw ValidationException::withMessages(['current_password' => 'A palavra-passe atual não confere.']);
        }

        $wasTemporary = (bool) $account->must_change_password;
        $account->password = $data['password'];
        $account->must_change_password = false;
        $account->temporary_password_expires_at = null;
        $account->temporary_password_used_at = null;
        $account->password_changed_at = now();
        $account->save();

        PatientPortalSession::audit($request, $account, 'patient_portal.password_changed', PatientAccount::class, $account->id, [
            'first_access_completed' => $wasTemporary,
        ]);

        if ($wasTemporary) {
            return redirect()->route('patient.dashboard')->with('success', 'Palavra-passe definida com sucesso. O seu acesso está agora ativo.');
        }

        return back()->with('success', 'Palavra-passe atualizada com sucesso.');
    }

    private function patientAppointment(PatientAccount $account, int $appointment): Appointment
    {
        return Appointment::withoutGlobalScope('clinic')
            ->with('healthProfessional')
            ->where('patient_id', $account->patient_id)
            ->where('clinic_id', $account->patient->clinic_id)
            ->findOrFail($appointment);
    }

    private function account(Request $request): PatientAccount|RedirectResponse
    {
        $account = PatientPortalSession::account($request);
        return $account ?: redirect()->route('patient.login');
    }

    private function forcePasswordChange(PatientAccount $account): ?RedirectResponse
    {
        if (! $account->must_change_password) return null;
        return redirect()->route('patient.security')->with('warning', 'Defina uma nova palavra-passe antes de aceder às restantes áreas do portal.');
    }
}
