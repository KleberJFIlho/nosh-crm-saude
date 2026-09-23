<?php
namespace App\Http\Controllers;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class PatientAllergyController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.write');
        $data=$request->validate([
            'substance'=>['required','string','max:190'],'reaction'=>['nullable','string','max:500'],
            'severity'=>['required',Rule::in(['unknown','mild','moderate','severe'])],'identified_at'=>['nullable','date'],
        ]);
        $allergy=PatientAllergy::create($data+['clinic_id'=>ClinicAccess::clinicId(),'patient_id'=>$patient->id,'status'=>'active','created_by'=>auth()->id(),'author_name_snapshot'=>auth()->user()->name]);
        ClinicAccess::audit('clinical.allergy.created',PatientAllergy::class,$allergy->id,['patient_id'=>$patient->id]);
        return back()->with('success','Alergia registada.');
    }
    public function update(Request $request, Patient $patient, PatientAllergy $allergy): RedirectResponse
    {
        ClinicAccess::allow('clinical.write');
        abort_unless((int)$allergy->patient_id===(int)$patient->id,404);
        $data=$request->validate(['status'=>['required',Rule::in(['active','inactive'])]]);
        $allergy->update($data);
        ClinicAccess::audit('clinical.allergy.status_updated',PatientAllergy::class,$allergy->id,['patient_id'=>$patient->id,'status'=>$allergy->status]);
        return back()->with('success','Estado da alergia atualizado.');
    }
}
