<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class NoshCrmHealthDemoSeeder extends Seeder
{
    public function run(): void
    {
        $patients = collect([
            ['first_name' => 'Marta', 'last_name' => 'Silva', 'email' => 'marta@example.test', 'phone' => '+351 910 000 101', 'status' => 'active', 'source' => 'Indicação', 'next_follow_up_at' => now()->addDays(2)],
            ['first_name' => 'João', 'last_name' => 'Costa', 'email' => 'joao@example.test', 'phone' => '+351 910 000 102', 'status' => 'active', 'source' => 'Website', 'next_follow_up_at' => now()->addDay()],
            ['first_name' => 'Ana', 'last_name' => 'Ferreira', 'email' => 'ana@example.test', 'phone' => '+351 910 000 103', 'status' => 'active', 'source' => 'Instagram', 'next_follow_up_at' => now()->subHour()],
            ['first_name' => 'Rui', 'last_name' => 'Martins', 'email' => 'rui@example.test', 'phone' => '+351 910 000 104', 'status' => 'prospect', 'source' => 'Campanha'],
        ])->map(fn ($data) => Patient::create($data));

        Lead::insert([
            ['name' => 'Carla Sousa', 'email' => 'carla@example.test', 'phone' => '+351 920 100 100', 'interest' => 'Avaliação nutricional', 'source' => 'Google', 'status' => 'qualified', 'score' => 88, 'estimated_value' => 180, 'next_contact_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Paulo Lima', 'email' => 'paulo@example.test', 'phone' => '+351 920 100 101', 'interest' => 'Fisioterapia', 'source' => 'Indicação', 'status' => 'contacted', 'score' => 72, 'estimated_value' => 320, 'next_contact_at' => now()->addDays(2), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sofia Rocha', 'email' => 'sofia@example.test', 'phone' => '+351 920 100 102', 'interest' => 'Consulta de rotina', 'source' => 'Website', 'status' => 'new', 'score' => 61, 'estimated_value' => 95, 'next_contact_at' => now()->addHours(5), 'created_at' => now(), 'updated_at' => now()],
        ]);

        Appointment::create(['patient_id' => $patients[0]->id, 'scheduled_at' => now()->addHours(2), 'type' => 'Consulta de acompanhamento', 'status' => 'confirmed', 'professional' => 'Dra. Helena Santos', 'location' => 'Sala 2']);
        Appointment::create(['patient_id' => $patients[1]->id, 'scheduled_at' => now()->addDay()->setTime(10, 30), 'type' => 'Avaliação inicial', 'status' => 'scheduled', 'professional' => 'Dr. Miguel Alves', 'location' => 'Sala 1']);
        Appointment::create(['patient_id' => $patients[2]->id, 'scheduled_at' => now()->addDays(2)->setTime(15, 0), 'type' => 'Retorno', 'status' => 'scheduled', 'professional' => 'Dra. Helena Santos', 'location' => 'Teleconsulta']);
    }
}
