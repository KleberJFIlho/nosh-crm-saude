<?php

namespace App\Http\Controllers;

use App\Models\HealthProfessional;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Support\ClinicAccess;
use App\Support\ClinicalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function index(Request $request): View
    {
        ClinicAccess::allow('clinical.view');
        $search = trim((string)$request->query('q'));

        $patients = Patient::query()
            ->withCount([
                'medicalRecords',
                'allergies as active_allergies_count' => fn($q) => $q->where('status','active'),
                'diagnoses as active_diagnoses_count' => fn($q) => $q->where('status','active'),
                'prescriptions as active_prescriptions_count' => fn($q) => $q->where('status','active'),
            ])
            ->when($search !== '', fn($q) => $q->where(function($q) use ($search) {
                $q->where('first_name','like','%'.$search.'%')
                  ->orWhere('last_name','like','%'.$search.'%')
                  ->orWhere('email','like','%'.$search.'%')
                  ->orWhere('phone','like','%'.$search.'%');
            }))
            ->where('status','active')
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(12)->withQueryString();

        $stats = [
            'patients' => Patient::query()->where('status','active')->count(),
            'records' => MedicalRecord::query()->count(),
            'today' => MedicalRecord::query()->whereDate('recorded_at', today())->count(),
            'allergies' => \App\Models\PatientAllergy::query()->where('status','active')->count(),
        ];

        return view('medical-records.index', compact('patients','stats','search'));
    }

    public function show(Patient $patient): View
    {
        ClinicAccess::allow('clinical.view');

        $patient->load([
            'medicalRecords' => fn($q) => $q->with(['healthProfessional','author'])->latest('recorded_at')->limit(40),
            'vitalSigns' => fn($q) => $q->latest('measured_at')->limit(20),
            'allergies' => fn($q) => $q->latest(),
            'diagnoses' => fn($q) => $q->with('healthProfessional')->latest('diagnosed_at')->latest(),
            'prescriptions' => fn($q) => $q->with('healthProfessional')->latest(),
            'clinicalAttachments' => fn($q) => $q->latest(),
        ]);

        $professionals = ClinicalAccess::professionalsQuery()->orderBy('professional_type')->orderBy('full_name')->get();
        $appointments = $patient->appointments()->latest('scheduled_at')->limit(30)->get();
        $latestVitals = $patient->vitalSigns->first();

        $stats = [
            'records' => $patient->medicalRecords()->count(),
            'active_allergies' => $patient->allergies->where('status','active')->count(),
            'active_diagnoses' => $patient->diagnoses->where('status','active')->count(),
            'active_prescriptions' => $patient->prescriptions->where('status','active')->count(),
        ];

        return view('medical-records.show', compact('patient','professionals','appointments','latestVitals','stats'));
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.write');
        $clinicId = ClinicAccess::clinicId();
        $data = $request->validate([
            'health_professional_id' => ['required', Rule::exists('health_professionals','id')->where(fn($q) => $q->where('clinic_id',$clinicId)->where('active',true))],
            'appointment_id' => ['nullable', Rule::exists('appointments','id')->where(fn($q) => $q->where('clinic_id',$clinicId)->where('patient_id',$patient->id))],
            'record_type' => ['required', Rule::in(['evolution','consultation','nursing','procedure'])],
            'chief_complaint' => ['required','string','max:500'],
            'subjective' => ['required','string','max:10000'],
            'objective' => ['required','string','max:10000'],
            'assessment' => ['required','string','max:10000'],
            'plan' => ['required','string','max:10000'],
            'recorded_at' => ['required','date'],
        ], [
            'health_professional_id.required' => 'Selecione o profissional responsável.',
            'record_type.required' => 'Selecione o tipo de registo.',
            'recorded_at.required' => 'Informe a data e hora do registo.',
            'chief_complaint.required' => 'Informe a queixa principal.',
            'subjective.required' => 'Preencha o campo Subjetivo / relato.',
            'objective.required' => 'Preencha o campo Objetivo / exame.',
            'assessment.required' => 'Preencha o campo Avaliação.',
            'plan.required' => 'Preencha o campo Plano / conduta.',
        ]);

        $professional = ClinicalAccess::professional((int)$data['health_professional_id']);
        $record = MedicalRecord::create($data + [
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'created_by' => auth()->id(),
            'professional_name_snapshot' => $professional->full_name,
            'author_name_snapshot' => auth()->user()->name,
            'signed_at' => now(),
        ]);

        ClinicAccess::audit('clinical.record.created', MedicalRecord::class, $record->id, [
            'patient_id' => $patient->id,
            'record_type' => $record->record_type,
            'signed' => true,
        ]);

        return back()->with('success','Evolução clínica registada e assinada.');
    }
}
