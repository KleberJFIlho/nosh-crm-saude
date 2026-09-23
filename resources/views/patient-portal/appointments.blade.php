<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Minhas consultas · NOSH CRM Saúde</title>
    <link rel="stylesheet" href="{{ asset('css/nosh-patient-v070.css') }}?v=0.7.0">
    <link rel="stylesheet" href="{{ asset('css/nosh-patient-appointments-v080.css') }}?v=0.8.0">
</head>
<body class="portal-body">
<header class="portal-header">
    <a href="{{ route('patient.dashboard') }}" class="patient-brand">✚ <strong>NOSH CRM Saúde</strong></a>
    <nav>
        <a href="{{ route('patient.dashboard') }}">Visão geral</a>
        <a class="active" href="{{ route('patient.appointments') }}">Consultas</a>
        <a href="{{ route('patient.exams') }}">Resultados de exames</a>
        <a href="{{ route('patient.security') }}">Segurança</a>
    </nav>
    <form method="post" action="{{ route('patient.logout') }}">
        @csrf
        <button>Sair</button>
    </form>
</header>

<main class="portal-main appointments-v080">
    <section class="portal-page-head appointments-head">
        <span>AGENDA DO PACIENTE</span>
        <h1>Minhas consultas</h1>
        <p>Acompanhe as suas consultas e, quando permitido, reagende ou cancele diretamente pelo portal.</p>
    </section>

    @if(session('success'))
        <div class="portal-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="portal-alert portal-alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="portal-alert portal-alert-danger">
            <strong>Não foi possível concluir a operação.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="appointment-rules-card">
        <div>
            <strong>Cancelamento</strong>
            <span>Até 2 dias úteis antes da consulta.</span>
        </div>
        <div>
            <strong>Reagendamento</strong>
            <span>Até 1 dia útil antes da consulta.</span>
        </div>
        <div>
            <strong>Fim de semana</strong>
            <span>Sábado e domingo não entram na contagem.</span>
        </div>
        <div>
            <strong>Último dia útil</strong>
            <span>Sem vaga para reagendar, o cancelamento excepcional é liberado.</span>
        </div>
    </section>

    <div class="appointment-list appointment-list-v080">
        @forelse($appointments as $appointment)
            @php($rules = $appointmentPolicies->get($appointment->id, []))
            <article class="appointment-row appointment-row-v080">
                <div class="appointment-date">
                    <strong>{{ $appointment->scheduled_at->format('d') }}</strong>
                    <span>{{ mb_strtoupper($appointment->scheduled_at->translatedFormat('M')) }}</span>
                </div>

                <div class="appointment-info">
                    <div class="appointment-title-line">
                        <strong>{{ $appointment->type }}</strong>
                        @if(($appointment->patient_reschedule_count ?? 0) > 0)
                            <span class="rescheduled-badge">Reagendada {{ $appointment->patient_reschedule_count }}x</span>
                        @endif
                    </div>
                    <span>{{ $appointment->scheduled_at->format('H:i') }} · {{ $appointment->professionalName() }}</span>
                    <small>{{ $appointment->location ?: 'Local a confirmar' }}</small>

                    @if(in_array($appointment->status, ['scheduled','confirmed'], true) && $appointment->scheduled_at->isFuture())
                        <div class="appointment-deadlines">
                            <span>Cancelar até {{ $rules['cancel_deadline'] ?? '—' }}</span>
                            <span>Reagendar até {{ $rules['reschedule_deadline'] ?? '—' }}</span>
                        </div>
                    @endif
                </div>

                <div class="appointment-right">
                    <span class="status-pill status-{{ $appointment->status }}">
                        {{ match($appointment->status){'scheduled'=>'Agendada','confirmed'=>'Confirmada','completed'=>'Concluída','cancelled'=>'Cancelada','no_show'=>'Faltou',default=>ucfirst($appointment->status)} }}
                    </span>

                    @if(($rules['can_reschedule'] ?? false) || ($rules['can_cancel'] ?? false))
                        <a class="appointment-manage-btn" href="{{ route('patient.appointments.manage', $appointment->id) }}">
                            Alterar consulta
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="portal-empty">Nenhuma consulta registada.</div>
        @endforelse
    </div>
</main>
</body>
</html>
