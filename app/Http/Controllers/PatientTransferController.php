<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClinicalAttachment;
use App\Models\ExamResult;
use App\Models\Clinic;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientDiagnosis;
use App\Models\PatientInteraction;
use App\Models\PatientPrescription;
use App\Models\PatientTask;
use App\Models\PatientTransfer;
use App\Models\PatientVitalSign;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientTransferController extends Controller
{
    public function index(): View
    {
        ClinicAccess::allow('transfers.view');
        $clinicId = ClinicAccess::clinicId();

        $outgoing = PatientTransfer::with(['fromClinic', 'toClinic', 'requestedBy', 'respondedBy'])
            ->where('from_clinic_id', $clinicId)->latest()->limit(30)->get();
        $incoming = PatientTransfer::with(['fromClinic', 'toClinic', 'requestedBy', 'respondedBy'])
            ->where('to_clinic_id', $clinicId)->latest()->limit(30)->get();

        $visiblePatientIds = $outgoing->where('status', 'pending')->pluck('patient_id')
            ->merge($incoming->where('status', 'pending')->pluck('patient_id'))->unique();
        $visiblePatients = Patient::withoutGlobalScope('clinic')->whereIn('id', $visiblePatientIds)->get()->keyBy('id');

        $stats = [
            'incoming_pending' => $incoming->where('status', 'pending')->count(),
            'outgoing_pending' => $outgoing->where('status', 'pending')->count(),
            'accepted' => $incoming->where('status', 'accepted')->count() + $outgoing->where('status', 'accepted')->count(),
            'rejected' => $incoming->where('status', 'rejected')->count() + $outgoing->where('status', 'rejected')->count(),
        ];

        return view('transfers.index', [
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'visiblePatients' => $visiblePatients,
            'patients' => Patient::where('status', 'active')->orderBy('first_name')->get(),
            'clinics' => Clinic::where('active', true)->where('id', '!=', $clinicId)->orderBy('city')->get(),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicAccess::allow('transfers.request');
        $clinicId = ClinicAccess::clinicId();
        $data = $request->validate([
            'patient_id' => ['required', 'integer'],
            'to_clinic_id' => ['required', 'integer', 'different:from_clinic_id'],
            'patient_consent' => ['accepted'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $patient = Patient::whereKey($data['patient_id'])->firstOrFail();
        $target = Clinic::where('active', true)->whereKey($data['to_clinic_id'])->where('id', '!=', $clinicId)->firstOrFail();
        $pendingExists = PatientTransfer::where('patient_id', $patient->id)->where('status', 'pending')->exists();
        abort_if($pendingExists, 422, 'Já existe uma transferência pendente para este paciente.');

        $transfer = PatientTransfer::create([
            'patient_id' => $patient->id,
            'from_clinic_id' => $clinicId,
            'to_clinic_id' => $target->id,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'patient_consent_at' => now(),
            'reason' => $data['reason'],
        ]);
        ClinicAccess::audit('patient_transfer.requested', PatientTransfer::class, $transfer->id, ['to_clinic_id' => $target->id, 'patient_id' => $patient->id]);

        return back()->with('success', 'Pedido de transferência enviado à clínica de destino.');
    }

    public function accept(Request $request, PatientTransfer $transfer): RedirectResponse
    {
        ClinicAccess::allow('transfers.respond');
        $clinicId = ClinicAccess::clinicId();
        abort_unless($transfer->status === 'pending' && (int) $transfer->to_clinic_id === $clinicId, 404);
        $data = $request->validate(['response_notes' => ['nullable', 'string', 'max:2000']]);

        DB::transaction(function () use ($transfer, $clinicId, $data): void {
            $patient = Patient::withoutGlobalScope('clinic')->lockForUpdate()->findOrFail($transfer->patient_id);
            abort_unless((int) $patient->clinic_id === (int) $transfer->from_clinic_id, 409, 'O paciente já não pertence à clínica de origem.');

            $patient->clinic_id = $clinicId;
            $patient->saveQuietly();

            Appointment::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'health_professional_id' => null]);
            PatientInteraction::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'user_id' => null]);
            PatientTask::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'assigned_to' => null, 'created_by' => null]);

            // O prontuário acompanha o paciente apenas porque este fluxo exige consentimento explícito.
            // Referências a utilizadores/profissionais da clínica anterior são limpas, preservando snapshots históricos.
            MedicalRecord::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'health_professional_id' => null, 'created_by' => null]);
            PatientVitalSign::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'created_by' => null]);
            PatientAllergy::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'created_by' => null]);
            PatientDiagnosis::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'health_professional_id' => null, 'created_by' => null]);
            PatientPrescription::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'health_professional_id' => null, 'created_by' => null]);
            ClinicalAttachment::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'uploaded_by' => null]);
            ExamResult::withoutGlobalScope('clinic')->where('patient_id', $patient->id)->update(['clinic_id' => $clinicId, 'uploaded_by' => null]);

            $transfer->update([
                'status' => 'accepted',
                'responded_by' => auth()->id(),
                'response_notes' => $data['response_notes'] ?? null,
                'responded_at' => now(),
            ]);
        });

        ClinicAccess::audit('patient_transfer.accepted', PatientTransfer::class, $transfer->id, [
            'from_clinic_id' => $transfer->from_clinic_id,
            'patient_id' => $transfer->patient_id,
            'crm_history_transferred' => true,
            'clinical_record_transferred' => true,
            'professional_assignment_cleared' => true,
        ]);
        return back()->with('success', 'Transferência aceite. O paciente e o prontuário autorizado passaram para esta clínica.');
    }

    public function reject(Request $request, PatientTransfer $transfer): RedirectResponse
    {
        ClinicAccess::allow('transfers.respond');
        $clinicId = ClinicAccess::clinicId();
        abort_unless($transfer->status === 'pending' && (int) $transfer->to_clinic_id === $clinicId, 404);
        $data = $request->validate(['response_notes' => ['required', 'string', 'max:2000']]);

        $transfer->update([
            'status' => 'rejected',
            'responded_by' => auth()->id(),
            'response_notes' => $data['response_notes'],
            'responded_at' => now(),
        ]);
        ClinicAccess::audit('patient_transfer.rejected', PatientTransfer::class, $transfer->id, ['from_clinic_id' => $transfer->from_clinic_id]);
        return back()->with('success', 'Transferência recusada. O paciente permanece na clínica de origem.');
    }
}
