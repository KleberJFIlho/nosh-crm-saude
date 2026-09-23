<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class PatientCredentialDeliveryService
{
    public function deliver(string $channel, Patient $patient, PatientAccount $account, string $temporaryPassword, int $minutes): string
    {
        return match ($channel) {
            'email' => $this->email($patient, $account, $temporaryPassword, $minutes),
            'sms' => $this->sms($patient, $account, $temporaryPassword, $minutes),
            default => throw new RuntimeException('Canal de entrega inválido.'),
        };
    }

    public function emailAvailable(Patient $patient): bool
    {
        return filled($patient->email);
    }

    public function smsAvailable(Patient $patient): bool
    {
        return filled($patient->phone) && filled(config('noshcrm.sms.webhook_url'));
    }

    private function email(Patient $patient, PatientAccount $account, string $temporaryPassword, int $minutes): string
    {
        if (! $this->emailAvailable($patient)) {
            throw new RuntimeException('O paciente não possui e-mail cadastrado.');
        }

        $destination = trim((string) $patient->email);
        $loginUrl = url('/paciente/login');
        $message = implode("\n", [
            'Olá, '.$patient->full_name.'.',
            '',
            'Foi criado um acesso provisório ao Portal do Paciente NOSH CRM Saúde.',
            'Login: '.$account->email,
            'Palavra-passe provisória: '.$temporaryPassword,
            'Validade: '.$minutes.' minutos.',
            '',
            'A palavra-passe é de uso único. No primeiro acesso será obrigatório definir uma nova palavra-passe.',
            'Portal: '.$loginUrl,
            '',
            'Se não solicitou este acesso, contacte a sua clínica.',
        ]);

        Mail::raw($message, function ($mail) use ($destination) {
            $mail->to($destination)->subject('Acesso provisório ao Portal do Paciente · NOSH CRM Saúde');
        });

        return $destination;
    }

    private function sms(Patient $patient, PatientAccount $account, string $temporaryPassword, int $minutes): string
    {
        if (! filled($patient->phone)) {
            throw new RuntimeException('O paciente não possui telefone cadastrado.');
        }

        $url = trim((string) config('noshcrm.sms.webhook_url'));
        if ($url === '') {
            throw new RuntimeException('O gateway de SMS ainda não está configurado.');
        }

        $destination = trim((string) $patient->phone);
        $message = 'NOSH CRM Saúde: acesso provisório. Login '.$account->email
            .' | Senha '.$temporaryPassword
            .' | válida '.$minutes.' min | uso único e troca obrigatória no primeiro acesso. '
            .url('/paciente/login');

        $request = Http::asJson()->timeout(12);
        $token = trim((string) config('noshcrm.sms.token'));
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'to' => $destination,
            'message' => $message,
            'patient_id' => $patient->id,
            'clinic_id' => $patient->clinic_id,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('O gateway de SMS recusou o envio (HTTP '.$response->status().').');
        }

        return $destination;
    }
}
