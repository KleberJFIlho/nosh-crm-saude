@extends('layouts.app')

@section('title', 'Profissionais · NOSH CRM Saúde')
@section('page-title', 'Profissionais de Saúde')

{{-- CSS dedicado: independente do bundle/cache do Vite --}}
<link rel="stylesheet" href="{{ asset('css/nosh-professionals-v043.css') }}?v={{ config('noshcrm.version', '0.4.3') }}">

@section('content')
<div class="professionals-v042">
    <div class="pro-actions-row">
        <div></div>
        @if(auth()->user()->canAccess('professionals.create'))
            <a class="pro-primary-btn" href="{{ route('professionals.create') }}">
                <span aria-hidden="true">＋</span> Novo profissional
            </a>
        @endif
    </div>

    <section class="pro-stats" aria-label="Resumo dos profissionais">
        <article class="pro-stat-card">
            <div class="pro-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div><span>Total</span><strong>{{ $stats['total'] }}</strong></div>
        </article>
        <article class="pro-stat-card">
            <div class="pro-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M6 3v5a6 6 0 0 0 12 0V3M6 3H4M18 3h2M12 14v3a4 4 0 0 0 4 4h1M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
            </div>
            <div><span>Médicos Ativos</span><strong>{{ $stats['doctors'] }}</strong></div>
        </article>
        <article class="pro-stat-card">
            <div class="pro-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3c3 0 5 2.2 5 5v3c0 3.4-2 6-5 6s-5-2.6-5-6V8c0-2.8 2-5 5-5ZM9 8h6M12 5v6M9 8h6M5 21c1.3-2.5 3.6-4 7-4s5.7 1.5 7 4"/></svg>
            </div>
            <div><span>Enfermeiros Ativos</span><strong>{{ $stats['nurses'] }}</strong></div>
        </article>
        <article class="pro-stat-card">
            <div class="pro-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10ZM3 21v-3a3 3 0 0 1 3-3M21 21v-3a3 3 0 0 0-3-3"/></svg>
            </div>
            <div><span>Auxiliares Ativos</span><strong>{{ $stats['assistants'] }}</strong></div>
        </article>
    </section>

    <form class="pro-filter-bar" method="get" action="{{ route('professionals.index') }}">
        <label class="pro-search-field">
            <span class="sr-only">Pesquisar</span>
            <span class="pro-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Pesquisar por nome, especialidade ou registo">
        </label>

        <label class="pro-select-field"><span>Tipo</span><select name="type">
            <option value="">Todos</option>
            <option value="doctor" @selected(request('type') === 'doctor')>Médico</option>
            <option value="nurse" @selected(request('type') === 'nurse')>Enfermeiro</option>
            <option value="assistant" @selected(request('type') === 'assistant')>Auxiliar</option>
        </select></label>

        <label class="pro-select-field"><span>Estado</span><select name="status">
            <option value="">Todos</option>
            <option value="active" @selected(request('status') === 'active')>Ativos</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
        </select></label>

        <button class="pro-filter-btn" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z"/></svg>
            Filtrar
        </button>

        @if(request()->hasAny(['q','type','status']))
            <a class="pro-clear-link" href="{{ route('professionals.index') }}">Limpar</a>
        @endif
    </form>

    @if($professionals->count())
        <div class="pro-grid">
            @foreach($professionals as $professional)
                <article class="pro-card">
                    <div class="pro-card-top">
                        <div class="professional-avatar pro-avatar">
                            @if($professional->photo_url)
                                <img src="{{ $professional->photo_url }}" alt="Foto de {{ $professional->full_name }}">
                            @else
                                <span>{{ $professional->initials() }}</span>
                            @endif
                        </div>

                        <div class="pro-card-identity">
                            <span class="pro-role role-{{ $professional->professional_type }}">{{ $professional->typeLabel() }}</span>
                            <h2>{{ $professional->full_name }}</h2>
                            <p>{{ $professional->specialty ?: 'Sem especialidade informada' }}</p>
                        </div>

                        <span class="pro-status {{ $professional->active ? 'is-active' : 'is-inactive' }}">
                            <i></i>{{ $professional->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    <div class="pro-divider"></div>

                    <dl class="pro-meta">
                        <div>
                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M6 16c.8-2 2-3 3-3s2.2 1 3 3M15 9h3M15 13h3"/></svg>Registo</dt>
                            <dd>{{ $professional->registration_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/></svg>Telefone</dt>
                            <dd>{{ $professional->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>E-mail</dt>
                            <dd>{{ $professional->email ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>Acesso ao sistema</dt>
                            <dd>{{ $professional->user?->email ?: 'Não vinculado' }}</dd>
                        </div>
                    </dl>

                    <div class="pro-card-actions">
                        @if(auth()->user()->canAccess('professionals.edit'))
                            <a class="pro-action-btn pro-edit" href="{{ route('professionals.edit', $professional) }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                Editar
                            </a>
                        @endif
                        @if(auth()->user()->canAccess('professionals.delete'))
                            <form method="post" action="{{ route('professionals.destroy', $professional) }}" onsubmit="return confirm('Remover este profissional?');">
                                @csrf @method('DELETE')
                                <button class="pro-action-btn pro-remove" type="submit">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg>
                                    Remover
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $professionals->links() }}</div>
    @else
        <div class="pro-empty">
            <div class="pro-empty-icon">＋</div>
            <strong>Nenhum profissional encontrado</strong>
            <p>Cadastre médicos, enfermeiros e auxiliares desta clínica.</p>
            @if(auth()->user()->canAccess('professionals.create'))
                <a class="pro-primary-btn" href="{{ route('professionals.create') }}">Novo profissional</a>
            @endif
        </div>
    @endif
</div>
@endsection
