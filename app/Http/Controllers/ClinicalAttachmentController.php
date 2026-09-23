<?php
namespace App\Http\Controllers;
use App\Models\ClinicalAttachment;
use App\Models\Patient;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ClinicalAttachmentController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.attachments'); $clinicId=ClinicAccess::clinicId();
        $data=$request->validate([
            'file'=>['required','file','mimes:pdf,jpg,jpeg,png,webp','max:10240'],
            'description'=>['nullable','string','max:500'],
        ]);
        $file=$request->file('file');
        $path=$file->store('clinical/clinic-'.$clinicId.'/patient-'.$patient->id,'local');
        $attachment=ClinicalAttachment::create([
            'clinic_id'=>$clinicId,'patient_id'=>$patient->id,'uploaded_by'=>auth()->id(),'uploader_name_snapshot'=>auth()->user()->name,
            'original_name'=>$file->getClientOriginalName(),'path'=>$path,'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),
            'description'=>$data['description']??null,
        ]);
        ClinicAccess::audit('clinical.attachment.created',ClinicalAttachment::class,$attachment->id,['patient_id'=>$patient->id,'mime_type'=>$attachment->mime_type,'size_bytes'=>$attachment->size_bytes]);
        return back()->with('success','Anexo clínico guardado em armazenamento privado.');
    }
    public function download(Patient $patient, ClinicalAttachment $attachment): StreamedResponse
    {
        ClinicAccess::allow('clinical.view'); abort_unless((int)$attachment->patient_id===(int)$patient->id,404);
        abort_unless(Storage::disk('local')->exists($attachment->path),404,'Ficheiro não encontrado.');
        ClinicAccess::audit('clinical.attachment.downloaded',ClinicalAttachment::class,$attachment->id,['patient_id'=>$patient->id]);
        return Storage::disk('local')->download($attachment->path,$attachment->original_name);
    }
}
