@extends('layouts.app')
@section('title', 'Dashboard · NOSH CRM Saúde')
@section('page-title', 'Visão geral')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">{{ mb_strtoupper(auth()->user()->clinic->name) }}</span><h1>A operação da clínica, num só lugar.</h1><p>Todos os indicadores abaixo são calculados exclusivamente sobre os dados da unidade atual.</p></div>
    @if(auth()->user()->canAccess('patients.create'))<a class="btn btn-primary" href="{{ route('patients.create') }}">+ Novo paciente</a>@endif
</div>

<section class="metric-grid">
    <article class="metric-card"><span class="metric-icon">♡</span><div><small>Pacientes ativos</small><strong>{{ $metrics['activePatients'] }}</strong><em>base em acompanhamento</em></div></article>
    <article class="metric-card"><span class="metric-icon">◎</span><div><small>Leads em aberto</small><strong>{{ $metrics['openLeads'] }}</strong><em>oportunidades no funil</em></div></article>
    <article class="metric-card"><span class="metric-icon">□</span><div><small>Consultas hoje</small><strong>{{ $metrics['todayAppointments'] }}</strong><em>agenda do dia</em></div></article>
    <article class="metric-card"><span class="metric-icon">↗</span><div><small>Follow-ups</small><strong>{{ $metrics['pendingFollowUps'] }}</strong><em>até ao fim de hoje</em></div></article>
</section>

<div class="dashboard-grid">
    <section class="panel panel-large">
        <div class="panel-head"><div><span class="eyebrow">PRÓXIMOS ATENDIMENTOS</span><h2>Agenda</h2></div><a href="{{ route('appointments.index') }}">Ver agenda →</a></div>
        <div class="schedule-list">
            @forelse($appointments as $appointment)
                <div class="schedule-row"><div class="date-box"><strong>{{ $appointment->scheduled_at->format('d') }}</strong><span>{{ mb_strtoupper($appointment->scheduled_at->translatedFormat('M')) }}</span></div><div class="schedule-main"><strong>{{ $appointment->patient->full_name }}</strong><span>{{ $appointment->type }} · {{ $appointment->professional }}</span></div><div class="schedule-time"><strong>{{ $appointment->scheduled_at->format('H:i') }}</strong><span class="badge badge-{{ $appointment->status }}">{{ ucfirst($appointment->status) }}</span></div></div>
            @empty
                <div class="empty-state">Ainda não existem consultas futuras.</div>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><span class="eyebrow">PRIORIDADE COMERCIAL</span><h2>Leads em destaque</h2></div><a href="{{ route('leads.index') }}">Funil →</a></div>
        <div class="lead-rank">
            @forelse($leads as $lead)
                <div class="lead-rank-row"><div><strong>{{ $lead->name }}</strong><span>{{ $lead->interest }}</span></div><div class="score">{{ $lead->score }}<small>/100</small></div></div>
            @empty
                <div class="empty-state">Sem leads registados.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
