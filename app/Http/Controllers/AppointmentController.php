<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\HealthProfessional;
use App\Models\Patient;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        ClinicAccess::allow('appointments.view');
        $clinicId = ClinicAccess::clinicId();

        $query = Appointment::query()
            ->with(['patient', 'healthProfessional'])
            ->orderBy('scheduled_at');

        if (in_array($request->query('status'), ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'], true)) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('professional_id')) {
            $professionalId = (int) $request->query('professional_id');
            $validProfessional = HealthProfessional::query()->whereKey($professionalId)->where('active', true)->exists();
            if ($validProfessional) {
                $query->where('health_professional_id', $professionalId);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->query('date'));
        }

        $professionals = HealthProfessional::query()
            ->where('active', true)
            ->orderBy('professional_type')
            ->orderBy('full_name')
            ->get();

        $stats = [
            'today' => Appointment::query()->whereDate('scheduled_at', today())->count(),
            'upcoming' => Appointment::query()->where('scheduled_at', '>=', now())->whereIn('status', ['scheduled', 'confirmed'])->count(),
            'confirmed' => Appointment::query()->where('scheduled_at', '>=', now())->where('status', 'confirmed')->count(),
            'completed_month' => Appointment::query()->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])->where('status', 'completed')->count(),
        ];

        return view('appointments.index', [
            'appointments' => $query->paginate(12)->withQueryString(),
            'patients' => Patient::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get(),
            'professionals' => $professionals,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicAccess::allow('appointments.create');
        $clinicId = ClinicAccess::clinicId();

        $data = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where(fn ($q) => $q->where('clinic_id', $clinicId))],
            'health_professional_id' => [
                'required',
                Rule::exists('health_professionals', 'id')->where(
                    fn ($q) => $q->where('clinic_id', $clinicId)->where('active', true)
                ),
            ],
            'scheduled_at' => ['required', 'date'],
            'type' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $professional = HealthProfessional::query()->whereKey($data['health_professional_id'])->firstOrFail();

        $appointment = Appointment::create($data + [
            'status' => 'scheduled',
            'clinic_id' => $clinicId,
            // Snapshot para preservar o histórico mesmo se o profissional for removido futuramente.
            'professional' => $professional->full_name,
        ]);

        ClinicAccess::audit('appointment.created', Appointment::class, $appointment->id, [
            'health_professional_id' => $professional->id,
        ]);

        return back()->with('success', 'Consulta agendada com profissional cadastrado nesta clínica.');
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        ClinicAccess::allow('appointments.edit');
        $appointment->update($request->validate([
            'status' => ['required', 'in:scheduled,confirmed,completed,cancelled,no_show'],
        ]));

        ClinicAccess::audit('appointment.status_updated', Appointment::class, $appointment->id, [
            'status' => $appointment->status,
        ]);

        return back()->with('success', 'Estado da consulta atualizado.');
    }
}
