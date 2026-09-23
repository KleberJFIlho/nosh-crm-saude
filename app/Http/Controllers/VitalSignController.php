<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientVitalSign;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VitalSignController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.write');
        $data=$request->validate([
            'weight_kg'=>['nullable','numeric','between:1,500'],
            'height_cm'=>['nullable','numeric','between:30,260'],
            'temperature_c'=>['nullable','numeric','between:25,50'],
            'systolic_bp'=>['nullable','integer','between:40,300'],
            'diastolic_bp'=>['nullable','integer','between:20,200'],
            'heart_rate'=>['nullable','integer','between:20,250'],
            'respiratory_rate'=>['nullable','integer','between:5,80'],
            'oxygen_saturation'=>['nullable','integer','between:50,100'],
            'glucose_mg_dl'=>['nullable','integer','between:20,1000'],
            'notes'=>['nullable','string','max:2000'],
            'measured_at'=>['required','date'],
        ]);
        abort_unless(collect($data)->except(['measured_at','notes'])->filter(fn($v)=>$v!==null && $v!=='')->isNotEmpty(),422,'Informe pelo menos um sinal vital.');
        $vital=PatientVitalSign::create($data+[
            'clinic_id'=>ClinicAccess::clinicId(),'patient_id'=>$patient->id,'created_by'=>auth()->id(),'author_name_snapshot'=>auth()->user()->name,
        ]);
        ClinicAccess::audit('clinical.vitals.created',PatientVitalSign::class,$vital->id,['patient_id'=>$patient->id]);
        return back()->with('success','Sinais vitais registados.');
    }
}
