<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientTask;
use App\Models\PatientTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->active && $user->clinic_id, 403);

        $clinicId = (int) $user->clinic_id;
        $items = collect();
        $counts = [
            'transfers' => 0,
            'overdue_tasks' => 0,
            'upcoming_tasks' => 0,
            'appointments' => 0,
        ];

        if ($user->canAccess('transfers.view')) {
            $transferQuery = PatientTransfer::query()
                ->where('to_clinic_id', $clinicId)
                ->where('status', 'pending');

            $counts['transfers'] = (clone $transferQuery)->count();
            $transfers = (clone $transferQuery)
                ->with('fromClinic')
                ->latest()
                ->limit(5)
                ->get();

            $patientIds = $transfers->pluck('patient_id')->filter()->unique();
            $patients = Patient::withoutGlobalScope('clinic')
                ->whereIn('id', $patientIds)
                ->get()
                ->keyBy('id');

            foreach ($transfers as $transfer) {
                $patient = $patients->get($transfer->patient_id);
                $patientName = $patient?->full_name ?: 'Paciente';
                $origin = $transfer->fromClinic?->city ?: $transfer->fromClinic?->name ?: 'outra clínica';

                $items->push([
                    'key' => 'transfer-'.$transfer->id,
                    'type' => 'transfer',
                    'priority' => 'high',
                    'icon' => '⇄',
                    'title' => 'Transferência pendente',
                    'message' => $patientName.' · origem: '.$origin,
                    'time' => optional($transfer->created_at)->diffForHumans(),
                    'timestamp' => optional($transfer->created_at)?->timestamp ?? now()->timestamp,
                    'url' => route('transfers.index'),
                ]);
            }
        }

        if ($user->canAccess('tasks.view')) {
            $taskBase = PatientTask::query()
                ->with('patient')
                ->where('status', 'pending')
                ->whereNotNull('due_at');

            if (! in_array($user->role, ['admin', 'manager'], true)) {
                $taskBase->where(function ($query) use ($user): void {
                    $query->where('assigned_to', $user->id)
                        ->orWhereNull('assigned_to');
                });
            }

            $overdueQuery = (clone $taskBase)->where('due_at', '<', now());
            $upcomingQuery = (clone $taskBase)->whereBetween('due_at', [now(), now()->addDay()]);
            $counts['overdue_tasks'] = (clone $overdueQuery)->count();
            $counts['upcoming_tasks'] = (clone $upcomingQuery)->count();

            foreach ((clone $overdueQuery)->orderBy('due_at')->limit(5)->get() as $task) {
                $items->push($this->taskItem($task, 'urgent', 'Follow-up em atraso'));
            }

            foreach ((clone $upcomingQuery)->orderBy('due_at')->limit(4)->get() as $task) {
                $items->push($this->taskItem($task, 'normal', 'Follow-up próximo'));
            }
        }

        if ($user->canAccess('appointments.view')) {
            $appointmentQuery = Appointment::query()
                ->with('patient')
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->whereBetween('scheduled_at', [now(), now()->addDay()]);

            $counts['appointments'] = (clone $appointmentQuery)->count();

            foreach ((clone $appointmentQuery)->orderBy('scheduled_at')->limit(5)->get() as $appointment) {
                $patientName = $appointment->patient?->full_name ?: 'Paciente';
                $items->push([
                    'key' => 'appointment-'.$appointment->id,
                    'type' => 'appointment',
                    'priority' => 'normal',
                    'icon' => '□',
                    'title' => 'Consulta nas próximas 24h',
                    'message' => $patientName.' · '.$appointment->scheduled_at->format('d/m H:i'),
                    'time' => $appointment->scheduled_at->diffForHumans(),
                    'timestamp' => $appointment->scheduled_at->timestamp,
                    'url' => route('appointments.index'),
                ]);
            }
        }

        $priorityWeight = ['urgent' => 0, 'high' => 1, 'normal' => 2];
        $items = $items
            ->sortBy(fn (array $item): string => str_pad((string) ($priorityWeight[$item['priority']] ?? 9), 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $item['timestamp'], 12, '0', STR_PAD_LEFT))
            ->take(12)
            ->values()
            ->map(fn (array $item): array => collect($item)->except('timestamp')->all());

        return response()->json([
            'count' => min(99, array_sum($counts)),
            'counts' => $counts,
            'items' => $items,
            'clinic_id' => $clinicId,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function taskItem(PatientTask $task, string $priority, string $title): array
    {
        $patientName = $task->patient?->full_name ?: 'Paciente';
        $url = $task->patient ? route('patients.show', $task->patient) : route('patients.index');

        return [
            'key' => 'task-'.$task->id.'-'.$priority,
            'type' => 'task',
            'priority' => $priority,
            'icon' => '✓',
            'title' => $title,
            'message' => $patientName.' · '.$task->title,
            'time' => optional($task->due_at)->diffForHumans(),
            'timestamp' => optional($task->due_at)?->timestamp ?? now()->timestamp,
            'url' => $url,
        ];
    }
}
