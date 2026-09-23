<?php

namespace Database\Seeders;

use App\Models\HealthProfessional;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientDiagnosis;
use App\Models\PatientPrescription;
use App\Models\PatientVitalSign;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoshCrmClinicalSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::withoutGlobalScopes()->where('status','active')->orderBy('clinic_id')->orderBy('id')->get()->groupBy('clinic_id');

        foreach ($patients as $clinicId => $clinicPatients) {
            $patient = $clinicPatients->first();
            $professional = HealthProfessional::withoutGlobalScopes()->where('clinic_id',$clinicId)->where('active',true)->first();
            $user = User::query()->where('clinic_id',$clinicId)->where('active',true)->orderByRaw("role = 'health_professional' desc")->first();
            if (!$patient || !$professional) continue;

            MedicalRecord::withoutGlobalScopes()->firstOrCreate(
                ['clinic_id'=>$clinicId,'patient_id'=>$patient->id,'chief_complaint'=>'Avaliação clínica inicial demonstrativa'],
                [
                    'health_professional_id'=>$professional->id,'created_by'=>$user?->id,
                    'professional_name_snapshot'=>$professional->full_name,'author_name_snapshot'=>$user?->name ?? 'Sistema demo',
                    'record_type'=>'consultation','subjective'=>'Paciente refere bom estado geral no registo demonstrativo.',
                    'objective'=>'Sem alterações relevantes documentadas neste exemplo de portfólio.',
                    'assessment'=>'Avaliação demonstrativa sem diagnóstico clínico real.','plan'=>'Manter acompanhamento conforme necessidade.',
                    'recorded_at'=>now()->subDays(2),'signed_at'=>now()->subDays(2),
                ]
            );

            PatientVitalSign::withoutGlobalScopes()->firstOrCreate(
                ['clinic_id'=>$clinicId,'patient_id'=>$patient->id,'measured_at'=>now()->subDays(2)->startOfHour()],
                ['created_by'=>$user?->id,'author_name_snapshot'=>$user?->name ?? 'Sistema demo','weight_kg'=>72.4,'height_cm'=>171,'temperature_c'=>36.6,'systolic_bp'=>122,'diastolic_bp'=>78,'heart_rate'=>72,'oxygen_saturation'=>98]
            );

            PatientAllergy::withoutGlobalScopes()->firstOrCreate(
                ['clinic_id'=>$clinicId,'patient_id'=>$patient->id,'substance'=>'Exemplo demonstrativo'],
                ['created_by'=>$user?->id,'author_name_snapshot'=>$user?->name ?? 'Sistema demo','reaction'=>'Registo fictício para demonstração','severity'=>'mild','status'=>'inactive','identified_at'=>today()->subYear()]
            );

            PatientDiagnosis::withoutGlobalScopes()->firstOrCreate(
                ['clinic_id'=>$clinicId,'patient_id'=>$patient->id,'description'=>'Diagnóstico demonstrativo'],
                ['health_professional_id'=>$professional->id,'created_by'=>$user?->id,'professional_name_snapshot'=>$professional->full_name,'author_name_snapshot'=>$user?->name ?? 'Sistema demo','diagnosis_type'=>'history','status'=>'history','diagnosed_at'=>today()->subMonth()]
            );

            PatientPrescription::withoutGlobalScopes()->firstOrCreate(
                ['clinic_id'=>$clinicId,'patient_id'=>$patient->id,'medication'=>'Medicação demonstrativa'],
                ['health_professional_id'=>$professional->id,'created_by'=>$user?->id,'professional_name_snapshot'=>$professional->full_name,'author_name_snapshot'=>$user?->name ?? 'Sistema demo','dosage'=>'Exemplo','frequency'=>'Exemplo','status'=>'completed','instructions'=>'Conteúdo fictício para apresentação do portfólio.']
            );
        }
    }
}
