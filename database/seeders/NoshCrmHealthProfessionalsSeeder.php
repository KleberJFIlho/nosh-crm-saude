<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\HealthProfessional;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoshCrmHealthProfessionalsSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::query()->whereIn('code', ['FAFE', 'BRAGA', 'GUIMARAES'])->get()->keyBy('code');

        $rows = [
            ['clinic' => 'FAFE', 'type' => 'doctor', 'name' => 'Dr. Ricardo Costa', 'registration' => 'OM-FAFE-001', 'specialty' => 'Medicina Geral e Familiar', 'email' => 'ricardo.costa@example.test'],
            ['clinic' => 'FAFE', 'type' => 'nurse', 'name' => 'Enf. Sofia Ferreira', 'registration' => 'OE-FAFE-001', 'specialty' => 'Enfermagem Geral', 'email' => 'sofia.ferreira@example.test'],
            ['clinic' => 'FAFE', 'type' => 'assistant', 'name' => 'Carla Mendes', 'registration' => null, 'specialty' => 'Apoio clínico', 'email' => 'carla.mendes@example.test'],
            ['clinic' => 'BRAGA', 'type' => 'doctor', 'name' => 'Dra. Helena Braga', 'registration' => 'OM-BRAGA-001', 'specialty' => 'Medicina Geral e Familiar', 'email' => 'profissional.braga@noshsaude.test', 'user_email' => 'profissional.braga@noshsaude.test'],
            ['clinic' => 'GUIMARAES', 'type' => 'nurse', 'name' => 'Enf. Luís Martins', 'registration' => 'OE-GMR-001', 'specialty' => 'Enfermagem Geral', 'email' => 'luis.martins@example.test'],
        ];

        foreach ($rows as $row) {
            $clinic = $clinics->get($row['clinic']);
            if (! $clinic) {
                continue;
            }

            $userId = null;
            if (! empty($row['user_email'])) {
                $userId = User::query()->where('clinic_id', $clinic->id)->where('email', $row['user_email'])->value('id');
            }

            HealthProfessional::withoutGlobalScopes()->updateOrCreate(
                ['clinic_id' => $clinic->id, 'full_name' => $row['name']],
                [
                    'user_id' => $userId,
                    'professional_type' => $row['type'],
                    'registration_number' => $row['registration'],
                    'specialty' => $row['specialty'],
                    'email' => $row['email'],
                    'active' => true,
                ]
            );
        }
    }
}
