@extends('layouts.app')
@section('title', 'Agenda · NOSH CRM Saúde')
@section('page-title', 'Agenda')
<link rel="stylesheet" href="{{ asset('css/nosh-ui-v050.css') }}?v={{ config('noshcrm.version', '0.5.0') }}">
@section('content')
<div class="nosh-v050 agenda-v050">
    <div class="v5-actions-row">
        <div class="v5-context-copy"><span>Agenda da {{ auth()->user()->clinic->city }}</span><p>Pacientes e profissionais disponíveis pertencem exclusivamente à clínica atual.</p></div>
        @if(auth()->user()->canAccess('appointments.create'))<button class="v5-primary-btn" type="button" data-modal-open="new-appointment">＋ Nova consulta</button>@endif
    </div>

    <section class="v5-stats">
        <article class="v5-stat"><span>Hoje</span><strong>{{ $stats['today'] }}</strong><small>Consultas do dia</small></article>
        <article class="v5-stat"><span>Próximas</span><strong>{{ $stats['upcoming'] }}</strong><small>Agendadas/confirmadas</small></article>
        <article class="v5-stat"><span>Confirmadas</span><strong>{{ $stats['confirmed'] }}</strong><small>A partir de agora</small></article>
        <article class="v5-stat"><span>Concluídas</span><strong>{{ $stats['completed_month'] }}</strong><small>Este mês</small></article>
    </section>

    <section class="v5-panel">
        <form class="v5-toolbar agenda-toolbar" method="get" action="{{ route('appointments.index') }}">
            <label class="v5-select"><span>Data</span><input type="date" name="date" value="{{ request('date') }}"></label>
            <label class="v5-select"><span>Profissional</span><select name="professional_id"><option value="">Todos</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string)request('professional_id')===(string)$professional->id)>{{ $professional->full_name }} · {{ $professional->typeLabel() }}</option>@endforeach</select></label>
            <label class="v5-select"><span>Estado</span><select name="status"><option value="">Todos</option>@foreach(['scheduled'=>'Agendada','confirmed'=>'Confirmada','completed'=>'Concluída','cancelled'=>'Cancelada','no_show'=>'Faltou'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label>
            <button class="v5-filter-btn" type="submit">Filtrar</button>
            @if(request()->hasAny(['date','professional_id','status']))<a class="v5-clear" href="{{ route('appointments.index') }}">Limpar</a>@endif
        </form>

        <div class="v5-appointment-list">
            @forelse($appointments as $appointment)
                <article class="v5-appointment-card">
                    <div class="v5-date-tile"><strong>{{ $appointment->scheduled_at->format('d') }}</strong><span>{{ mb_strtoupper($appointment->scheduled_at->translatedFormat('M')) }}</span><small>{{ $appointment->scheduled_at->format('H:i') }}</small></div>
                    <div class="v5-appointment-copy"><span>{{ $appointment->type }}</span><h2>{{ $appointment->patient->full_name }}</h2><p>{{ $appointment->professionalName() }} · {{ $appointment->location ?: 'Local a definir' }}</p></div>
                    <div class="v5-professional-chip">{{ $appointment->healthProfessional?->typeLabel() ?? 'Histórico' }}</div>
                    @if(auth()->user()->canAccess('appointments.edit'))
                        <form method="post" action="{{ route('appointments.update',$appointment) }}">@csrf @method('PATCH')<select class="v5-status-select" name="status" onchange="this.form.submit()">@foreach(['scheduled'=>'Agendada','confirmed'=>'Confirmada','completed'=>'Concluída','cancelled'=>'Cancelada','no_show'=>'Faltou'] as $value=>$label)<option value="{{ $value }}" @selected($appointment->status===$value)>{{ $label }}</option>@endforeach</select></form>
                    @else
                        <span class="v5-status status-{{ $appointment->status }}">{{ ucfirst($appointment->status) }}</span>
                    @endif
                </article>
            @empty<div class="v5-empty"><strong>Agenda vazia</strong><p>Nenhuma consulta encontrada para estes filtros.</p></div>@endforelse
        </div>
        <div class="v5-pagination">{{ $appointments->links() }}</div>
    </section>

    @if(auth()->user()->canAccess('appointments.create'))
    <div class="modal" data-modal="new-appointment" hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-card">
            <div class="modal-head"><div><span class="eyebrow">AGENDA</span><h2>Nova consulta</h2></div><button class="icon-btn" data-modal-close type="button">×</button></div>
            @if($professionals->isEmpty())
                <div class="error-box">Cadastre primeiro um profissional ativo em <a href="{{ route('professionals.index') }}">Profissionais</a>.</div>
            @endif
            <form method="post" action="{{ route('appointments.store') }}">@csrf
                <div class="form-grid">
                    <label class="span-2"><span>Paciente desta clínica *</span><select class="input" name="patient_id" required><option value="">Selecione...</option>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->full_name }}</option>@endforeach</select></label>
                    <label><span>Data e hora *</span><input class="input" type="datetime-local" name="scheduled_at" required></label>
                    <label><span>Tipo *</span><input class="input" name="type" placeholder="Consulta, retorno..." required></label>
                    <label class="span-2"><span>Profissional cadastrado *</span><select class="input" name="health_professional_id" required @disabled($professionals->isEmpty())><option value="">Selecione...</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}">{{ $professional->full_name }} · {{ $professional->typeLabel() }}{{ $professional->specialty ? ' · '.$professional->specialty : '' }}</option>@endforeach</select></label>
                    <label><span>Local</span><input class="input" name="location" placeholder="Sala 1 / Teleconsulta"></label>
                    <label class="span-2"><span>Notas</span><textarea class="input" name="notes" rows="3"></textarea></label>
                </div>
                <div class="form-actions"><button class="btn" data-modal-close type="button">Cancelar</button><button class="btn btn-primary" type="submit" @disabled($professionals->isEmpty())>Confirmar agendamento</button></div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
