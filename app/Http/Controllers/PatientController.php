<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        ClinicAccess::allow('patients.view');
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $patients = Patient::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when(in_array($status, ['active', 'inactive', 'prospect'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Patient::query()->count(),
            'active' => Patient::query()->where('status', 'active')->count(),
            'prospects' => Patient::query()->where('status', 'prospect')->count(),
            'followups' => Patient::query()->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->addDays(7))->count(),
        ];

        return view('patients.index', compact('patients', 'search', 'status', 'stats'));
    }

    public function show(Patient $patient): View
    {
        ClinicAccess::allow('patients.view');

        $patient->load([
            'appointments' => fn ($query) => $query->orderByDesc('scheduled_at')->limit(20),
            'interactions.user',
            'tasks.assignee',
        ]);

        $timeline = collect();
        foreach ($patient->interactions as $interaction) {
            $timeline->push([
                'kind' => 'interaction',
                'at' => $interaction->occurred_at,
                'type' => $interaction->type,
                'title' => $interaction->subject ?: $this->interactionLabel($interaction->type),
                'content' => $interaction->content,
                'meta' => trim(($interaction->user?->name ?: 'Sistema').($interaction->direction ? ' · '.$this->directionLabel($interaction->direction) : '')),
                'record' => $interaction,
            ]);
        }
        foreach ($patient->appointments as $appointment) {
            $timeline->push([
                'kind' => 'appointment',
                'at' => $appointment->scheduled_at,
                'type' => 'appointment',
                'title' => $appointment->type,
                'content' => $appointment->notes ?: 'Atendimento registado na agenda.',
                'meta' => trim(($appointment->professional ?: 'Profissional não definido').' · '.($appointment->location ?: 'Local não definido')),
                'record' => $appointment,
            ]);
        }

        $timeline = $timeline->sortByDesc('at')->values();
        $tasks = $patient->tasks->sortBy(fn ($task) => sprintf(
            '%d-%020d',
            $task->status === 'pending' ? 0 : 1,
            $task->due_at?->timestamp ?? PHP_INT_MAX
        ));

        $clinicUsers = User::query()
            ->where('clinic_id', ClinicAccess::clinicId())
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('patients.show', compact('patient', 'timeline', 'tasks', 'clinicUsers'));
    }

    public function create(): View
    {
        ClinicAccess::allow('patients.create');
        return view('patients.form', ['patient' => new Patient()]);
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicAccess::allow('patients.create');
        $patient = Patient::create($this->validated($request) + ['clinic_id' => ClinicAccess::clinicId()]);
        ClinicAccess::audit('patient.created', Patient::class, $patient->id);
        return redirect()->route('patients.show', $patient)->with('success', 'Paciente registado nesta clínica.');
    }

    public function edit(Patient $patient): View
    {
        ClinicAccess::allow('patients.edit');
        return view('patients.form', compact('patient'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('patients.edit');
        $patient->update($this->validated($request));
        ClinicAccess::audit('patient.updated', Patient::class, $patient->id);
        return redirect()->route('patients.show', $patient)->with('success', 'Paciente atualizado com sucesso.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('patients.delete');
        ClinicAccess::audit('patient.deleted', Patient::class, $patient->id);
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Paciente removido.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:female,male,other,prefer_not_to_say'],
            'status' => ['required', 'in:active,inactive,prospect'],
            'source' => ['nullable', 'string', 'max:100'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function interactionLabel(string $type): string
    {
        return match ($type) {
            'call' => 'Chamada telefónica',
            'email' => 'E-mail',
            'whatsapp' => 'WhatsApp',
            'visit' => 'Visita / contacto presencial',
            default => 'Nota de acompanhamento',
        };
    }

    private function directionLabel(string $direction): string
    {
        return $direction === 'inbound' ? 'Recebido' : 'Enviado';
    }
}
