<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientInteraction;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientInteractionController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('interactions.create');
        $data = $request->validate([
            'type' => ['required', 'in:call,email,whatsapp,visit,note'],
            'direction' => ['nullable', 'in:inbound,outbound'],
            'subject' => ['nullable', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ]);

        if (in_array($data['type'], ['note', 'visit'], true)) {
            $data['direction'] = null;
        }

        $interaction = $patient->interactions()->create($data + [
            'clinic_id' => ClinicAccess::clinicId(),
            'user_id' => auth()->id(),
        ]);

        $patient->forceFill(['last_contact_at' => $interaction->occurred_at])->saveQuietly();
        ClinicAccess::audit('patient.interaction.created', PatientInteraction::class, $interaction->id, ['patient_id' => $patient->id, 'type' => $interaction->type]);

        return back()->with('success', 'Interação adicionada à timeline do paciente.');
    }

    public function destroy(Patient $patient, PatientInteraction $interaction): RedirectResponse
    {
        ClinicAccess::allow('interactions.delete');
        abort_unless((int) $interaction->patient_id === (int) $patient->id, 404);
        ClinicAccess::audit('patient.interaction.deleted', PatientInteraction::class, $interaction->id, ['patient_id' => $patient->id]);
        $interaction->delete();
        return back()->with('success', 'Interação removida da timeline.');
    }
}
