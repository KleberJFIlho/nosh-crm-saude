@extends('layouts.app')
@section('title', 'Utilizadores · NOSH CRM Saúde')
@section('page-title', 'Utilizadores')
<link rel="stylesheet" href="{{ asset('css/nosh-ui-v050.css') }}?v={{ config('noshcrm.version', '0.5.0') }}">
@section('content')
<div class="nosh-v050 users-v050">
    <div class="v5-actions-row"><div class="v5-context-copy"><span>Equipa da {{ auth()->user()->clinic->city }}</span><p>Contas desta unidade não podem consultar dados de outras clínicas.</p></div>@if(auth()->user()->canAccess('users.manage'))<button class="v5-primary-btn" type="button" data-modal-open="new-user">＋ Novo utilizador</button>@endif</div>

    <section class="v5-stats">
        <article class="v5-stat"><span>Total</span><strong>{{ $stats['total'] }}</strong><small>Contas da clínica</small></article>
        <article class="v5-stat"><span>Ativos</span><strong>{{ $stats['active'] }}</strong><small>Acesso autorizado</small></article>
        <article class="v5-stat"><span>Administradores</span><strong>{{ $stats['admins'] }}</strong><small>Perfil administrativo</small></article>
        <article class="v5-stat"><span>Profissionais</span><strong>{{ $stats['clinical'] }}</strong><small>Contas clínicas</small></article>
    </section>

    <section class="v5-panel">
        <div class="v5-users-grid">
            @forelse($users as $user)
                <article class="v5-user-card"><div class="v5-person-head"><div class="v5-avatar single">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</div><div class="v5-person-copy"><h2>{{ $user->name }}</h2><p>{{ $user->email }}</p></div><span class="v5-status {{ $user->active ? 'status-active' : 'status-inactive' }}">{{ $user->active ? 'Ativo' : 'Inativo' }}</span></div><div class="v5-meta-grid"><div><span>Perfil</span><strong>{{ $user->roleLabel() }}</strong></div><div><span>Clínica</span><strong>{{ auth()->user()->clinic->city }}</strong></div></div>@if(auth()->user()->canAccess('users.manage'))<div class="v5-card-actions"><button class="v5-action" type="button" data-modal-open="edit-user-{{ $user->id }}">Editar conta</button></div>@endif</article>
            @empty<div class="v5-empty"><strong>Nenhum utilizador</strong></div>@endforelse
        </div>
    </section>

    @if(auth()->user()->canAccess('users.manage'))
    <div class="modal" data-modal="new-user" hidden><div class="modal-backdrop" data-modal-close></div><form class="modal-card" method="post" action="{{ route('users.store') }}">@csrf<div class="modal-head"><div><span class="eyebrow">NOVA CONTA</span><h2>Utilizador da {{ auth()->user()->clinic->city }}</h2></div><button class="icon-btn" type="button" data-modal-close>×</button></div><div class="form-grid"><label><span>Nome *</span><input class="input" name="name" required></label><label><span>E-mail *</span><input class="input" type="email" name="email" required></label><label><span>Palavra-passe inicial *</span><input class="input" type="password" name="password" minlength="8" required></label><label><span>Perfil *</span><select class="input" name="role" required>@foreach($roles as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label></div><div class="form-actions"><button class="btn" type="button" data-modal-close>Cancelar</button><button class="btn btn-primary" type="submit">Criar utilizador</button></div></form></div>
    @foreach($users as $user)<div class="modal" data-modal="edit-user-{{ $user->id }}" hidden><div class="modal-backdrop" data-modal-close></div><form class="modal-card" method="post" action="{{ route('users.update',$user) }}">@csrf @method('PUT')<div class="modal-head"><div><span class="eyebrow">EDITAR CONTA</span><h2>{{ $user->name }}</h2></div><button class="icon-btn" type="button" data-modal-close>×</button></div><div class="form-grid"><label><span>Nome *</span><input class="input" name="name" value="{{ $user->name }}" required></label><label><span>E-mail *</span><input class="input" type="email" name="email" value="{{ $user->email }}" required></label><label><span>Nova palavra-passe</span><input class="input" type="password" name="password" minlength="8" placeholder="Deixe vazio para manter"></label><label><span>Perfil *</span><select class="input" name="role">@foreach($roles as $value=>$label)<option value="{{ $value }}" @selected($user->role===$value)>{{ $label }}</option>@endforeach</select></label><label><span>Estado</span><select class="input" name="active"><option value="1" @selected($user->active)>Ativo</option><option value="0" @selected(!$user->active)>Inativo</option></select></label></div><div class="form-actions"><button class="btn" type="button" data-modal-close>Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div></form></div>@endforeach
    @endif
</div>
@endsection
