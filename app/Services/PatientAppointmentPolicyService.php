<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\HealthProfessional;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PatientAppointmentPolicyService
{
    public function directCancellationDeadline(Appointment $appointment): Carbon
    {
        return $this->businessDeadline($appointment->scheduled_at, 2);
    }

    public function rescheduleDeadline(Appointment $appointment): Carbon
    {
        return $this->businessDeadline($appointment->scheduled_at, 1);
    }

    public function canCancelDirectly(Appointment $appointment, ?CarbonInterface $now = null): bool
    {
        $now = $now ? Carbon::instance($now) : now();

        return $this->isOpenAppointment($appointment)
            && $appointment->scheduled_at->isFuture()
            && $now->lessThanOrEqualTo($this->directCancellationDeadline($appointment));
    }

    public function canReschedule(Appointment $appointment, ?CarbonInterface $now = null): bool
    {
        $now = $now ? Carbon::instance($now) : now();

        return $this->isOpenAppointment($appointment)
            && $appointment->scheduled_at->isFuture()
            && $now->lessThanOrEqualTo($this->rescheduleDeadline($appointment));
    }

    public function availability(Appointment $appointment, int $limit = 10): array
    {
        if (! $this->canReschedule($appointment)) {
            return [
                'mode' => 'none',
                'same_professional' => collect(),
                'same_specialty' => collect(),
                'has_availability' => false,
            ];
        }

        $original = $this->originalProfessional($appointment);
        if (! $original) {
            return [
                'mode' => 'none',
                'same_professional' => collect(),
                'same_specialty' => collect(),
                'has_availability' => false,
            ];
        }

        $sameProfessional = $this->slotsForProfessionals(
            $appointment,
            collect([$original]),
            $limit
        );

        if ($sameProfessional->isNotEmpty()) {
            return [
                'mode' => 'same_professional',
                'same_professional' => $sameProfessional,
                'same_specialty' => collect(),
                'has_availability' => true,
            ];
        }

        $sameSpecialtyProfessionals = collect();
        if (filled($original->specialty)) {
            $sameSpecialtyProfessionals = HealthProfessional::withoutGlobalScope('clinic')
                ->where('clinic_id', $appointment->clinic_id)
                ->where('active', true)
                ->where('professional_type', $original->professional_type)
                ->where('specialty', $original->specialty)
                ->whereKeyNot($original->id)
                ->orderBy('full_name')
                ->get();
        }

        $sameSpecialty = $this->slotsForProfessionals(
            $appointment,
            $sameSpecialtyProfessionals,
            $limit
        );

        return [
            'mode' => $sameSpecialty->isNotEmpty() ? 'same_specialty' : 'none',
            'same_professional' => collect(),
            'same_specialty' => $sameSpecialty,
            'has_availability' => $sameSpecialty->isNotEmpty(),
        ];
    }

    public function canCancelAfterFailedReschedule(Appointment $appointment): bool
    {
        if (! $this->canReschedule($appointment) || $this->canCancelDirectly($appointment)) {
            return false;
        }

        return ! $this->availability($appointment)['has_availability'];
    }

    public function validateSelectedSlot(Appointment $appointment, int $professionalId, string $scheduledAt): ?array
    {
        $availability = $this->availability($appointment, 40);
        $slots = $availability['mode'] === 'same_professional'
            ? $availability['same_professional']
            : $availability['same_specialty'];

        $target = Carbon::parse($scheduledAt)->format('Y-m-d H:i:s');

        return $slots->first(function (array $slot) use ($professionalId, $target): bool {
            return (int) $slot['professional_id'] === $professionalId
                && Carbon::parse($slot['scheduled_at'])->format('Y-m-d H:i:s') === $target;
        });
    }

    public function businessDeadline(CarbonInterface $scheduledAt, int $businessDaysBefore): Carbon
    {
        $deadline = Carbon::instance($scheduledAt)->startOfDay();
        $remaining = $businessDaysBefore;

        while ($remaining > 0) {
            $deadline->subDay();
            if (! $deadline->isWeekend()) {
                $remaining--;
            }
        }

        return $deadline->endOfDay();
    }

    public function deadlineLabel(Carbon $deadline): string
    {
        return $deadline->translatedFormat('d/m/Y');
    }

    private function isOpenAppointment(Appointment $appointment): bool
    {
        return in_array($appointment->status, ['scheduled', 'confirmed'], true);
    }

    private function originalProfessional(Appointment $appointment): ?HealthProfessional
    {
        if (! $appointment->health_professional_id) {
            return null;
        }

        return HealthProfessional::withoutGlobalScope('clinic')
            ->where('clinic_id', $appointment->clinic_id)
            ->where('active', true)
            ->find($appointment->health_professional_id);
    }

    private function slotsForProfessionals(Appointment $appointment, Collection $professionals, int $limit): Collection
    {
        if ($professionals->isEmpty()) {
            return collect();
        }

        $slotMinutes = max(15, (int) config('noshcrm.patient_appointments.slot_minutes', 30));
        $searchDays = max(7, (int) config('noshcrm.patient_appointments.search_days', 30));
        $dayStart = (string) config('noshcrm.patient_appointments.workday_start', '09:00');
        $dayEnd = (string) config('noshcrm.patient_appointments.workday_end', '18:00');

        $startDate = now()->addDay()->startOfDay();
        while ($startDate->isWeekend()) {
            $startDate->addDay();
        }

        $endDate = $startDate->copy()->addDays($searchDays);
        $professionalIds = $professionals->pluck('id')->map(fn ($id) => (int) $id)->all();

        $busy = Appointment::withoutGlobalScope('clinic')
            ->where('clinic_id', $appointment->clinic_id)
            ->where('id', '!=', $appointment->id)
            ->whereIn('health_professional_id', $professionalIds)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereBetween('scheduled_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get(['health_professional_id', 'scheduled_at'])
            ->mapWithKeys(fn (Appointment $item) => [
                $item->health_professional_id.'|'.$item->scheduled_at->format('Y-m-d H:i') => true,
            ]);

        $slots = collect();
        $date = $startDate->copy();

        while ($date->lessThanOrEqualTo($endDate) && $slots->count() < $limit) {
            if (! $date->isWeekend()) {
                foreach ($professionals as $professional) {
                    $cursor = Carbon::parse($date->format('Y-m-d').' '.$dayStart);
                    $close = Carbon::parse($date->format('Y-m-d').' '.$dayEnd);

                    while ($cursor->lt($close) && $slots->count() < $limit) {
                        $key = $professional->id.'|'.$cursor->format('Y-m-d H:i');
                        if (! $busy->has($key)) {
                            $slots->push([
                                'professional_id' => (int) $professional->id,
                                'professional_name' => $professional->full_name,
                                'specialty' => $professional->specialty,
                                'scheduled_at' => $cursor->copy(),
                                'same_professional' => (int) $professional->id === (int) $appointment->health_professional_id,
                            ]);
                        }
                        $cursor->addMinutes($slotMinutes);
                    }
                }
            }
            $date->addDay();
        }

        return $slots;
    }
}
