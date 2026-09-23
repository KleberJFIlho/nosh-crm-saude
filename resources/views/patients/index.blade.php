@extends('layouts.app')
@section('title', 'Pacientes · NOSH CRM Saúde')
@section('page-title', 'Pacientes')
<link rel="stylesheet" href="{{ asset('css/nosh-ui-v050.css') }}?v={{ config('noshcrm.version', '0.5.0') }}">
@section('content')
<div class="nosh-v050 patients-v050">
    <div class="v5-actions-row">
        <div class="v5-context-copy">
            <span>Dados isolados por clínica</span>
            <p>Somente pacientes de {{ auth()->user()->clinic->city }} são apresentados.</p>
        </div>
        @if(auth()->user()->canAccess('patients.create'))
            <a class="v5-primary-btn" href="{{ route('patients.create') }}">＋ Novo paciente</a>
        @endif
    </div>

    <section class="v5-stats">
        <article class="v5-stat"><span>Total</span><strong>{{ $stats['total'] }}</strong><small>Pacientes registados</small></article>
        <article class="v5-stat"><span>Ativos</span><strong>{{ $stats['active'] }}</strong><small>Em acompanhamento</small></article>
        <article class="v5-stat"><span>Prospects</span><strong>{{ $stats['prospects'] }}</strong><small>Potenciais pacientes</small></article>
        <article class="v5-stat"><span>Follow-ups</span><strong>{{ $stats['followups'] }}</strong><small>Próximos 7 dias</small></article>
    </section>

    <section class="v5-panel">
        <form class="v5-toolbar" method="get" action="{{ route('patients.index') }}">
            <label class="v5-search"><span aria-hidden="true">⌕</span><input type="search" name="q" value="{{ $search }}" placeholder="Pesquisar por nome, telefone ou e-mail"></label>
            <label class="v5-select"><span>Estado</span><select name="status"><option value="">Todos</option><option value="active" @selected($status==='active')>Ativos</option><option value="prospect" @selected($status==='prospect')>Prospects</option><option value="inactive" @selected($status==='inactive')>Inativos</option></select></label>
            <button class="v5-filter-btn" type="submit">Filtrar</button>
            @if($search !== '' || $status !== '')<a class="v5-clear" href="{{ route('patients.index') }}">Limpar</a>@endif
        </form>

        <div class="v5-list-grid">
            @forelse($patients as $patient)
                <article class="v5-person-card">
                    <div class="v5-person-head">
                        <div class="v5-avatar">{{ mb_strtoupper(mb_substr($patient->first_name,0,1).mb_substr($patient->last_name,0,1)) }}</div>
                        <div class="v5-person-copy"><h2>{{ $patient->full_name }}</h2><p>#{{ str_pad($patient->id, 5, '0', STR_PAD_LEFT) }} · {{ $patient->source ?: 'Origem não informada' }}</p></div>
                        <span class="v5-status status-{{ $patient->status }}">{{ $patient->status === 'active' ? 'Ativo' : ($patient->status === 'prospect' ? 'Prospect' : 'Inativo') }}</span>
                    </div>
                    <div class="v5-meta-grid">
                        <div><span>Telefone</span><strong>{{ $patient->phone ?: '—' }}</strong></div>
                        <div><span>E-mail</span><strong>{{ $patient->email ?: '—' }}</strong></div>
                        <div><span>Próximo follow-up</span><strong>{{ $patient->next_follow_up_at?->format('d/m/Y H:i') ?? 'Sem follow-up' }}</strong></div>
                    </div>
                    <div class="v5-card-actions">
                        <a class="v5-action" href="{{ route('patients.show', $patient) }}">Ver perfil</a>
                        @if(auth()->user()->canAccess('patients.edit'))<a class="v5-action" href="{{ route('patients.edit', $patient) }}">Editar</a>@endif
                        @if(auth()->user()->canAccess('patients.delete'))<form method="post" action="{{ route('patients.destroy',$patient) }}" onsubmit="return confirm('Remover este paciente?')">@csrf @method('DELETE')<button class="v5-action danger" type="submit">Remover</button></form>@endif
                    </div>
                </article>
            @empty
                <div class="v5-empty"><strong>Nenhum paciente encontrado</strong><p>A pesquisa continua limitada à clínica atual.</p></div>
            @endforelse
        </div>
        <div class="v5-pagination">{{ $patients->links() }}</div>
    </section>
</div>
@endsection
