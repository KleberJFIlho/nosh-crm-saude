<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\Patient;
use App\Support\ClinicAccess;
use App\Support\PatientPortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultController extends Controller
{
    public function index(Patient $patient): View
    {
        ClinicAccess::allow('clinical.view');
        $exams = ExamResult::where('patient_id',$patient->id)->orderByDesc('exam_date')->get();
        return view('exam-results.index', compact('patient','exams'));
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('clinical.attachments');
        $data = $request->validate([
            'title' => ['required','string','max:190'],
            'category' => ['required','string','max:100'],
            'laboratory' => ['nullable','string','max:190'],
            'exam_date' => ['required','date'],
            'summary' => ['nullable','string','max:5000'],
            'file' => ['required','file','mimes:pdf,jpg,jpeg,png,webp','max:15360'],
            'release_now' => ['nullable','boolean'],
        ]);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = $file->storeAs('exam-results/'.auth()->user()->clinic_id.'/'.$patient->id, Str::uuid().'.'.$ext, 'local');
        $released = $request->boolean('release_now');
        $exam = ExamResult::create([
            'clinic_id'=>auth()->user()->clinic_id,'patient_id'=>$patient->id,'uploaded_by'=>auth()->id(),
            'title'=>$data['title'],'category'=>$data['category'],'laboratory'=>$data['laboratory'] ?? null,
            'exam_date'=>$data['exam_date'],'summary'=>$data['summary'] ?? null,'file_path'=>$path,
            'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),
            'status'=>$released ? 'released' : 'draft','released_at'=>$released ? now() : null,
        ]);
        ClinicAccess::audit('exam_result.created', ExamResult::class, $exam->id, ['patient_id'=>$patient->id,'released'=>$released]);
        return back()->with('success','Resultado de exame guardado'.($released ? ' e disponibilizado ao paciente.' : '.'));
    }

    public function release(Patient $patient, ExamResult $exam): RedirectResponse
    {
        ClinicAccess::allow('clinical.attachments');
        abort_unless((int)$exam->patient_id === (int)$patient->id,404);
        $exam->update(['status'=>'released','released_at'=>now()]);
        ClinicAccess::audit('exam_result.released', ExamResult::class, $exam->id, ['patient_id'=>$patient->id]);
        return back()->with('success','Resultado disponibilizado ao paciente.');
    }

    public function staffDownload(Patient $patient, ExamResult $exam): StreamedResponse
    {
        ClinicAccess::allow('clinical.view');
        abort_unless((int)$exam->patient_id === (int)$patient->id,404);
        abort_unless(Storage::disk('local')->exists($exam->file_path),404);
        ClinicAccess::audit('exam_result.downloaded', ExamResult::class, $exam->id, ['patient_id'=>$patient->id]);
        return Storage::disk('local')->download($exam->file_path,$exam->original_name);
    }

    public function patientDownload(Request $request, int $exam): StreamedResponse|RedirectResponse
    {
        $account = PatientPortalSession::account($request);
        if (! $account) return redirect()->route('patient.login');
        if ($account->must_change_password) return redirect()->route('patient.security')->with('warning', 'Defina uma nova palavra-passe antes de descarregar resultados.');
        $exam = ExamResult::withoutGlobalScope('clinic')->findOrFail($exam);
        abort_unless((int)$exam->patient_id === (int)$account->patient_id && $exam->isReleased(),404);
        abort_unless(Storage::disk('local')->exists($exam->file_path),404);
        PatientPortalSession::audit($request, $account, 'patient_portal.exam_downloaded', ExamResult::class, $exam->id);
        return Storage::disk('local')->download($exam->file_path,$exam->original_name);
    }
}
