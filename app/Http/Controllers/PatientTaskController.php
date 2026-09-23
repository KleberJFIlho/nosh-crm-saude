<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientTask;
use App\Models\User;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientTaskController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        ClinicAccess::allow('tasks.create');
        $data = $request->validate([
            'type' => ['required', 'in:follow_up,call,email,document,other'],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'integer'],
            'due_at' => ['nullable', 'date'],
        ]);

        if (! empty($data['assigned_to'])) {
            User::query()->where('clinic_id', ClinicAccess::clinicId())->where('active', true)->findOrFail($data['assigned_to']);
        }

        $task = $patient->tasks()->create($data + [
            'clinic_id' => ClinicAccess::clinicId(),
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        $this->syncNextFollowUp($patient);
        ClinicAccess::audit('patient.task.created', PatientTask::class, $task->id, ['patient_id' => $patient->id]);
        return back()->with('success', 'Tarefa criada para o paciente.');
    }

    public function update(Request $request, Patient $patient, PatientTask $task): RedirectResponse
    {
        ClinicAccess::allow('tasks.edit');
        abort_unless((int) $task->patient_id === (int) $patient->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,done,cancelled'],
        ]);

        $task->status = $data['status'];
        $task->completed_at = $data['status'] === 'done' ? now() : null;
        $task->save();

        $this->syncNextFollowUp($patient);
        ClinicAccess::audit('patient.task.status_changed', PatientTask::class, $task->id, ['patient_id' => $patient->id, 'status' => $task->status]);
        return back()->with('success', $task->status === 'done' ? 'Tarefa concluída.' : 'Estado da tarefa atualizado.');
    }

    public function destroy(Patient $patient, PatientTask $task): RedirectResponse
    {
        ClinicAccess::allow('tasks.delete');
        abort_unless((int) $task->patient_id === (int) $patient->id, 404);
        ClinicAccess::audit('patient.task.deleted', PatientTask::class, $task->id, ['patient_id' => $patient->id]);
        $task->delete();
        $this->syncNextFollowUp($patient);
        return back()->with('success', 'Tarefa removida.');
    }

    private function syncNextFollowUp(Patient $patient): void
    {
        $next = $patient->tasks()
            ->where('type', 'follow_up')
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->value('due_at');

        $patient->forceFill(['next_follow_up_at' => $next])->saveQuietly();
    }
}
