@extends('layouts.app')
@section('title', 'Prontuários · NOSH CRM Saúde')
@section('page-title', 'Prontuários')
@section('content')
<div class="clinical-page">
    <div class="clinical-page-head">
        <div>
            <span class="eyebrow">ÁREA CLÍNICA · ACESSO RESTRITO</span>
            <h1>Prontuários</h1>
            <p>Consulte o histórico clínico apenas dos pacientes pertencentes à clínica atual.</p>
        </div>
    </div>

    <div class="clinical-stats-grid">
        <article class="clinical-stat"><span class="clinical-stat-icon">♡</span><div><small>Pacientes ativos</small><strong>{{ $stats['patients'] }}</strong></div></article>
        <article class="clinical-stat"><span class="clinical-stat-icon">≡</span><div><small>Evoluções clínicas</small><strong>{{ $stats['records'] }}</strong></div></article>
        <article class="clinical-stat"><span class="clinical-stat-icon">＋</span><div><small>Registos hoje</small><strong>{{ $stats['today'] }}</strong></div></article>
        <article class="clinical-stat"><span class="clinical-stat-icon clinical-danger-icon">!</span><div><small>Alergias ativas</small><strong>{{ $stats['allergies'] }}</strong></div></article>
    </div>

    <section class="clinical-toolbar">
        <form method="get" action="{{ route('medical-records.index') }}" class="clinical-search-form">
            <label class="clinical-search"><span>⌕</span><input type="search" name="q" value="{{ $search }}" placeholder="Pesquisar paciente por nome, e-mail ou telefone"></label>
            <button class="clinical-btn clinical-btn-primary" type="submit">Pesquisar</button>
            @if($search !== '')<a class="clinical-btn" href="{{ route('medical-records.index') }}">Limpar</a>@endif
        </form>
    </section>

    <section class="clinical-patient-grid">
        @forelse($patients as $patient)
            <article class="clinical-patient-card">
                <div class="clinical-patient-top">
                    <div class="clinical-avatar">{{ mb_strtoupper(mb_substr($patient->first_name,0,1).mb_substr($patient->last_name,0,1)) }}</div>
                    <div class="clinical-patient-name"><span>PACIENTE #{{ str_pad($patient->id,5,'0',STR_PAD_LEFT) }}</span><h2>{{ $patient->full_name }}</h2><p>{{ $patient->birth_date?->format('d/m/Y') ?? 'Nascimento não informado' }}</p></div>
                </div>
                <div class="clinical-mini-metrics">
                    <div><strong>{{ $patient->medical_records_count }}</strong><span>Evoluções</span></div>
                    <div class="{{ $patient->active_allergies_count ? 'metric-alert' : '' }}"><strong>{{ $patient->active_allergies_count }}</strong><span>Alergias</span></div>
                    <div><strong>{{ $patient->active_diagnoses_count }}</strong><span>Diagnósticos</span></div>
                    <div><strong>{{ $patient->active_prescriptions_count }}</strong><span>Prescrições</span></div>
                </div>
                <div class="clinical-card-footer"><span>Clínica {{ auth()->user()->clinic->city }}</span><a class="clinical-btn clinical-btn-primary" href="{{ route('medical-records.show',$patient) }}">Abrir prontuário</a></div>
            </article>
        @empty
            <div class="clinical-empty">Nenhum paciente encontrado nesta clínica.</div>
        @endforelse
    </section>

    <div class="clinical-pagination">{{ $patients->links() }}</div>
</div>
@endsection
