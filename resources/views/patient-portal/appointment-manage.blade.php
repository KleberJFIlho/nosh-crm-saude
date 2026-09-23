<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Alterar consulta · NOSH CRM Saúde</title>
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
    <a class="portal-back-link" href="{{ route('patient.appointments') }}">← Voltar às consultas</a>

    <section class="manage-hero">
        <div>
            <span>ALTERAR CONSULTA</span>
            <h1>{{ $appointment->type }}</h1>
            <p>{{ $appointment->scheduled_at->translatedFormat('d/m/Y \à\s H:i') }} · {{ $appointment->professionalName() }}</p>
        </div>
        <span class="status-pill status-{{ $appointment->status }}">
            {{ match($appointment->status){'scheduled'=>'Agendada','confirmed'=>'Confirmada',default=>ucfirst($appointment->status)} }}
        </span>
    </section>

    @if($errors->any())
        <div class="portal-alert portal-alert-danger">
            <strong>Verifique os dados.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="deadline-grid">
        <article class="deadline-card {{ $canCancelDirectly ? 'allowed' : 'closed' }}">
            <span>Cancelamento normal</span>
            <strong>{{ $canCancelDirectly ? 'Disponível' : 'Prazo encerrado' }}</strong>
            <small>Prazo: {{ $cancelDeadline->format('d/m/Y') }}. São considerados apenas dias úteis.</small>
        </article>
        <article class="deadline-card {{ $canReschedule ? 'allowed' : 'closed' }}">
            <span>Reagendamento</span>
            <strong>{{ $canReschedule ? 'Disponível' : 'Prazo encerrado' }}</strong>
            <small>Prazo: {{ $rescheduleDeadline->format('d/m/Y') }}. São considerados apenas dias úteis.</small>
        </article>
    </section>

    @if($canReschedule)
        <section class="manage-card">
            <div class="manage-card-head">
                <div>
                    <span>REAGENDAMENTO</span>
                    <h2>Escolha uma nova vaga</h2>
                </div>
                @if($availability['mode'] === 'same_professional')
                    <span class="priority-pill">Mesmo profissional</span>
                @elseif($availability['mode'] === 'same_specialty')
                    <span class="priority-pill secondary">Mesma especialidade</span>
                @endif
            </div>

            @if($availability['mode'] === 'same_professional')
                <p class="manage-helper">Encontrámos vaga com <strong>{{ $appointment->professionalName() }}</strong>. Por segurança assistencial, estas opções têm prioridade.</p>
                @php($slots = $availability['same_professional'])
            @elseif($availability['mode'] === 'same_specialty')
                <p class="manage-helper">Não há vaga disponível com {{ $appointment->professionalName() }}. Mostramos profissionais ativos da mesma especialidade.</p>
                @php($slots = $availability['same_specialty'])
            @else
                @php($slots = collect())
                <div class="no-slots-box">
                    <strong>Não encontramos vaga para reagendamento.</strong>
                    <p>Não existe disponibilidade com o mesmo profissional nem com outro profissional da mesma especialidade dentro da janela de pesquisa.</p>
                </div>
            @endif

            @if($slots->isNotEmpty())
                <form method="post" action="{{ route('patient.appointments.reschedule', $appointment->id) }}">
                    @csrf
                    <div class="slot-grid">
                        @foreach($slots as $slot)
                            <label class="slot-option">
                                <input type="radio" name="slot" required
                                       data-professional="{{ $slot['professional_id'] }}"
                                       data-scheduled="{{ $slot['scheduled_at']->format('Y-m-d H:i:s') }}">
                                <span>
                                    <strong>{{ $slot['scheduled_at']->translatedFormat('D, d/m') }} · {{ $slot['scheduled_at']->format('H:i') }}</strong>
                                    <small>{{ $slot['professional_name'] }}</small>
                                    @if($slot['specialty'])
                                        <em>{{ $slot['specialty'] }}</em>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <input type="hidden" name="health_professional_id" id="selected-professional">
                    <input type="hidden" name="scheduled_at" id="selected-scheduled">
                    <button class="portal-primary inline manage-primary" type="submit">Confirmar reagendamento</button>
                </form>
            @endif
        </section>
    @endif

    @if($canCancelDirectly || $canExceptionalCancel)
        <section class="manage-card cancel-card">
            <div class="manage-card-head">
                <div>
                    <span>CANCELAMENTO</span>
                    <h2>{{ $canExceptionalCancel && ! $canCancelDirectly ? 'Não foi possível reagendar. Deseja cancelar?' : 'Cancelar esta consulta' }}</h2>
                </div>
                @if($canExceptionalCancel && ! $canCancelDirectly)
                    <span class="exception-pill">Exceção por falta de vaga</span>
                @endif
            </div>

            @if($canExceptionalCancel && ! $canCancelDirectly)
                <p class="manage-helper">O prazo normal de 2 dias úteis já terminou, mas como o reagendamento ainda está dentro do prazo de 1 dia útil e não há vaga com o mesmo profissional nem com profissional da mesma especialidade, o cancelamento está excepcionalmente autorizado.</p>
            @else
                <p class="manage-helper">O cancelamento está dentro do prazo de 2 dias úteis.</p>
            @endif

            <form method="post" action="{{ route('patient.appointments.cancel', $appointment->id) }}">
                @csrf
                <label class="form-label" for="reason">Motivo (opcional)</label>
                <textarea class="portal-textarea" id="reason" name="reason" maxlength="500" placeholder="Se desejar, indique o motivo do cancelamento.">{{ old('reason') }}</textarea>
                <label class="confirm-check">
                    <input type="checkbox" name="confirm_cancel" value="1" required>
                    <span>Confirmo que desejo cancelar esta consulta.</span>
                </label>
                <button class="danger-action" type="submit">Cancelar consulta</button>
            </form>
        </section>
    @endif
</main>

<script>
document.querySelectorAll('input[name="slot"]').forEach(function (input) {
    input.addEventListener('change', function () {
        document.getElementById('selected-professional').value = input.dataset.professional;
        document.getElementById('selected-scheduled').value = input.dataset.scheduled;
    });
});
</script>
</body>
</html>
