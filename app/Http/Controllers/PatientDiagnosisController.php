<?php
namespace App\Http\Controllers;
use App\Models\HealthProfessional;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Support\ClinicAccess;
use App\Support\ClinicalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class PatientDiagnosisController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.write'); $clinicId=ClinicAccess::clinicId();
        $data=$request->validate([
            'health_professional_id'=>['required',Rule::exists('health_professionals','id')->where(fn($q)=>$q->where('clinic_id',$clinicId)->where('active',true))],
            'code'=>['nullable','string','max:40'],'description'=>['required','string','max:500'],
            'diagnosis_type'=>['required',Rule::in(['working','confirmed','history'])],
            'diagnosed_at'=>['nullable','date'],'notes'=>['nullable','string','max:3000'],
        ]);
        $professional=ClinicalAccess::professional((int)$data['health_professional_id']);
        $diagnosis=PatientDiagnosis::create($data+[
            'clinic_id'=>$clinicId,'patient_id'=>$patient->id,'status'=>'active','created_by'=>auth()->id(),
            'professional_name_snapshot'=>$professional->full_name,'author_name_snapshot'=>auth()->user()->name,
        ]);
        ClinicAccess::audit('clinical.diagnosis.created',PatientDiagnosis::class,$diagnosis->id,['patient_id'=>$patient->id]);
        return back()->with('success','Diagnóstico registado.');
    }
    public function update(Request $request, Patient $patient, PatientDiagnosis $diagnosis): RedirectResponse
    {
        ClinicAccess::allow('clinical.write'); abort_unless((int)$diagnosis->patient_id===(int)$patient->id,404);
        $data=$request->validate(['status'=>['required',Rule::in(['active','resolved','history'])]]); $diagnosis->update($data);
        ClinicAccess::audit('clinical.diagnosis.status_updated',PatientDiagnosis::class,$diagnosis->id,['patient_id'=>$patient->id,'status'=>$diagnosis->status]);
        return back()->with('success','Estado do diagnóstico atualizado.');
    }
}
