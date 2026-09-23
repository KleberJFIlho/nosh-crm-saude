<?php
namespace App\Http\Controllers;
use App\Models\HealthProfessional;
use App\Models\Patient;
use App\Models\PatientPrescription;
use App\Support\ClinicAccess;
use App\Support\ClinicalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class PatientPrescriptionController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.write'); $clinicId=ClinicAccess::clinicId();
        $data=$request->validate([
            'health_professional_id'=>['required',Rule::exists('health_professionals','id')->where(fn($q)=>$q->where('clinic_id',$clinicId)->where('active',true))],
            'medication'=>['required','string','max:190'],'dosage'=>['nullable','string','max:120'],'route'=>['nullable','string','max:80'],
            'frequency'=>['nullable','string','max:120'],'duration'=>['nullable','string','max:120'],'instructions'=>['nullable','string','max:5000'],
            'starts_at'=>['nullable','date'],'ends_at'=>['nullable','date','after_or_equal:starts_at'],
        ]);
        $professional=ClinicalAccess::professional((int)$data['health_professional_id']);
        $prescription=PatientPrescription::create($data+[
            'clinic_id'=>$clinicId,'patient_id'=>$patient->id,'status'=>'active','created_by'=>auth()->id(),
            'professional_name_snapshot'=>$professional->full_name,'author_name_snapshot'=>auth()->user()->name,
        ]);
        ClinicAccess::audit('clinical.prescription.created',PatientPrescription::class,$prescription->id,['patient_id'=>$patient->id]);
        return back()->with('success','Prescrição registada.');
    }
    public function update(Request $request, Patient $patient, PatientPrescription $prescription): RedirectResponse
    {
        ClinicAccess::allow('clinical.write'); abort_unless((int)$prescription->patient_id===(int)$patient->id,404);
        $data=$request->validate(['status'=>['required',Rule::in(['active','completed','cancelled'])]]); $prescription->update($data);
        ClinicAccess::audit('clinical.prescription.status_updated',PatientPrescription::class,$prescription->id,['patient_id'=>$patient->id,'status'=>$prescription->status]);
        return back()->with('success','Estado da prescrição atualizado.');
    }
}
