<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientInteraction;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoshCrmPatientTimelineSeeder extends Seeder
{
    public function run(): void
    {
        $demos = [
            [
                'patient_email' => 'ines.braga@example.test',
                'user_email' => 'profissional.braga@noshsaude.test',
                'interaction' => [
                    'type' => 'call', 'direction' => 'outbound', 'subject' => 'Contacto pós-consulta',
                    'content' => 'Paciente contactada para acompanhamento. Refere boa evolução e confirmou disponibilidade para o próximo atendimento.',
                ],
                'task' => ['title' => 'Confirmar evolução em 7 dias', 'type' => 'follow_up', 'priority' => 'high'],
            ],
            [
                'patient_email' => 'beatriz.guimaraes@example.test',
                'user_email' => 'gestor.guimaraes@noshsaude.test',
                'interaction' => [
                    'type' => 'whatsapp', 'direction' => 'inbound', 'subject' => 'Dúvida sobre preparação',
                    'content' => 'Paciente pediu orientações sobre a preparação antes da próxima consulta. Informação enviada e confirmada.',
                ],
                'task' => ['title' => 'Enviar lembrete da consulta', 'type' => 'follow_up', 'priority' => 'normal'],
            ],
        ];

        foreach ($demos as $demo) {
            $patient = Patient::withoutGlobalScopes()->where('email', $demo['patient_email'])->first();
            $user = User::where('email', $demo['user_email'])->first();
            if (! $patient) {
                continue;
            }

            PatientInteraction::withoutGlobalScopes()->updateOrCreate(
                ['patient_id' => $patient->id, 'subject' => $demo['interaction']['subject']],
                $demo['interaction'] + [
                    'clinic_id' => $patient->clinic_id,
                    'user_id' => $user?->id,
                    'occurred_at' => now()->subDay()->setTime(16, 30),
                ]
            );

            PatientTask::withoutGlobalScopes()->updateOrCreate(
                ['patient_id' => $patient->id, 'title' => $demo['task']['title']],
                $demo['task'] + [
                    'clinic_id' => $patient->clinic_id,
                    'assigned_to' => $user?->id,
                    'created_by' => $user?->id,
                    'status' => 'pending',
                    'due_at' => now()->addDays(3)->setTime(10, 0),
                ]
            );
        }
    }
}
