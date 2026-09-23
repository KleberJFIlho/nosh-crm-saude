<?php

return [
    'version' => '0.8.0',
    'release_name' => 'Autoatendimento de Consultas',

    'patient_portal' => [
        'temporary_password_minutes' => (int) env('NOSH_PATIENT_TEMP_PASSWORD_MINUTES', 30),
        'max_credential_sends' => (int) env('NOSH_PATIENT_CREDENTIAL_MAX_SENDS', 3),
        'credential_send_window_seconds' => (int) env('NOSH_PATIENT_CREDENTIAL_WINDOW_SECONDS', 900),
    ],
    'patient_appointments' => [
        // Regras solicitadas: apenas sábado e domingo são excluídos da contagem.
        'cancel_business_days_before' => 2,
        'reschedule_business_days_before' => 1,
        'slot_minutes' => (int) env('NOSH_APPOINTMENT_SLOT_MINUTES', 30),
        'workday_start' => env('NOSH_APPOINTMENT_WORKDAY_START', '09:00'),
        'workday_end' => env('NOSH_APPOINTMENT_WORKDAY_END', '18:00'),
        'search_days' => (int) env('NOSH_APPOINTMENT_SEARCH_DAYS', 30),
    ],

    'sms' => [
        'webhook_url' => env('NOSH_SMS_WEBHOOK_URL'),
        'token' => env('NOSH_SMS_WEBHOOK_TOKEN'),
    ],

    'roles' => [
        'admin' => 'Administrador',
        'manager' => 'Gestor',
        'receptionist' => 'Receção',
        'health_professional' => 'Profissional de Saúde',
    ],

    'permissions' => [
        'admin' => ['*'],
        'manager' => [
            'dashboard.view','patients.view','patients.create','patients.edit',
            'interactions.view','interactions.create','interactions.delete',
            'tasks.view','tasks.create','tasks.edit','tasks.delete',
            'leads.view','leads.create','leads.edit','leads.delete',
            'appointments.view','appointments.create','appointments.edit',
            'professionals.view','professionals.create','professionals.edit','professionals.delete',
            'clinical.view','transfers.view','transfers.request','transfers.respond','users.view',
        ],
        'receptionist' => [
            'dashboard.view','patients.view','patients.create','patients.edit',
            'interactions.view','interactions.create','tasks.view','tasks.create','tasks.edit',
            'leads.view','leads.create','leads.edit','appointments.view','appointments.create','appointments.edit',
            'professionals.view','professionals.create','professionals.edit','transfers.view','transfers.request',
        ],
        'health_professional' => [
            'dashboard.view','patients.view','patients.edit','interactions.view','interactions.create',
            'tasks.view','tasks.create','tasks.edit','appointments.view','appointments.edit','professionals.view',
            'clinical.view','clinical.write','clinical.attachments','transfers.view','transfers.request',
        ],
    ],
];
