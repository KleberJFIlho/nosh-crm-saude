<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NoshCrmMultiClinicSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = collect([
            ['name' => 'NOSH Saúde Fafe', 'code' => 'FAFE', 'city' => 'Fafe', 'district' => 'Braga'],
            ['name' => 'NOSH Saúde Braga', 'code' => 'BRAGA', 'city' => 'Braga', 'district' => 'Braga'],
            ['name' => 'NOSH Saúde Guimarães', 'code' => 'GUIMARAES', 'city' => 'Guimarães', 'district' => 'Braga'],
        ])->mapWithKeys(function (array $data) {
            $clinic = Clinic::updateOrCreate(['code' => $data['code']], $data + ['active' => true]);
            return [$data['code'] => $clinic];
        });

        $fafe = $clinics['FAFE'];
        Patient::withoutGlobalScopes()->whereNull('clinic_id')->update(['clinic_id' => $fafe->id]);
        Lead::withoutGlobalScopes()->whereNull('clinic_id')->update(['clinic_id' => $fafe->id]);
        Appointment::withoutGlobalScopes()->whereNull('clinic_id')->update(['clinic_id' => $fafe->id]);

        $users = [
            ['clinic' => 'FAFE', 'name' => 'Administrador Fafe', 'email' => 'admin.fafe@noshsaude.test', 'role' => 'admin'],
            ['clinic' => 'FAFE', 'name' => 'Receção Fafe', 'email' => 'rececao.fafe@noshsaude.test', 'role' => 'receptionist'],
            ['clinic' => 'BRAGA', 'name' => 'Administrador Braga', 'email' => 'admin.braga@noshsaude.test', 'role' => 'admin'],
            ['clinic' => 'BRAGA', 'name' => 'Dra. Helena Braga', 'email' => 'profissional.braga@noshsaude.test', 'role' => 'health_professional'],
            ['clinic' => 'GUIMARAES', 'name' => 'Administrador Guimarães', 'email' => 'admin.guimaraes@noshsaude.test', 'role' => 'admin'],
            ['clinic' => 'GUIMARAES', 'name' => 'Gestor Guimarães', 'email' => 'gestor.guimaraes@noshsaude.test', 'role' => 'manager'],
        ];
        foreach ($users as $data) {
            User::updateOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'clinic_id' => $clinics[$data['clinic']]->id,
                'role' => $data['role'],
                'active' => true,
                'password' => Hash::make('Nosh@2026'),
            ]);
        }

        $demoPatients = [
            ['clinic' => 'BRAGA', 'first_name' => 'Inês', 'last_name' => 'Moura', 'email' => 'ines.braga@example.test', 'phone' => '+351 930 200 101', 'status' => 'active', 'source' => 'Website'],
            ['clinic' => 'BRAGA', 'first_name' => 'Tiago', 'last_name' => 'Ribeiro', 'email' => 'tiago.braga@example.test', 'phone' => '+351 930 200 102', 'status' => 'active', 'source' => 'Indicação'],
            ['clinic' => 'GUIMARAES', 'first_name' => 'Beatriz', 'last_name' => 'Lopes', 'email' => 'beatriz.guimaraes@example.test', 'phone' => '+351 930 300 101', 'status' => 'active', 'source' => 'Instagram'],
            ['clinic' => 'GUIMARAES', 'first_name' => 'Diogo', 'last_name' => 'Pires', 'email' => 'diogo.guimaraes@example.test', 'phone' => '+351 930 300 102', 'status' => 'active', 'source' => 'Google'],
        ];
        foreach ($demoPatients as $data) {
            Patient::withoutGlobalScopes()->updateOrCreate(['email' => $data['email']], [
                'clinic_id' => $clinics[$data['clinic']]->id,
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'phone' => $data['phone'],
                'status' => $data['status'], 'source' => $data['source'], 'next_follow_up_at' => now()->addDays(3),
            ]);
        }

        $bragaPatient = Patient::withoutGlobalScopes()->where('email', 'ines.braga@example.test')->first();
        $guimaraesPatient = Patient::withoutGlobalScopes()->where('email', 'beatriz.guimaraes@example.test')->first();
        if ($bragaPatient) {
            Appointment::withoutGlobalScopes()->updateOrCreate(
                ['patient_id' => $bragaPatient->id, 'type' => 'Consulta demonstrativa Braga'],
                ['clinic_id' => $clinics['BRAGA']->id, 'scheduled_at' => now()->addDay()->setTime(11, 0), 'status' => 'scheduled', 'professional' => 'Dra. Helena Braga', 'location' => 'Sala 1']
            );
        }
        if ($guimaraesPatient) {
            Appointment::withoutGlobalScopes()->updateOrCreate(
                ['patient_id' => $guimaraesPatient->id, 'type' => 'Consulta demonstrativa Guimarães'],
                ['clinic_id' => $clinics['GUIMARAES']->id, 'scheduled_at' => now()->addDays(2)->setTime(14, 30), 'status' => 'confirmed', 'professional' => 'Dr. Luís Martins', 'location' => 'Sala 2']
            );
        }

        foreach ([
            ['clinic' => 'BRAGA', 'email' => 'lead.braga@example.test', 'name' => 'Lead Braga', 'interest' => 'Fisioterapia'],
            ['clinic' => 'GUIMARAES', 'email' => 'lead.guimaraes@example.test', 'name' => 'Lead Guimarães', 'interest' => 'Nutrição'],
        ] as $data) {
            Lead::withoutGlobalScopes()->updateOrCreate(['email' => $data['email']], [
                'clinic_id' => $clinics[$data['clinic']]->id,
                'name' => $data['name'], 'interest' => $data['interest'], 'source' => 'Demo',
                'status' => 'new', 'score' => 70, 'estimated_value' => 150, 'next_contact_at' => now()->addDay(),
            ]);
        }
    }
}
