<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NOSH CRM Saúde')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/nosh-clinical-v060.css') }}?v=0.6.0">
</head>
<body>
<div class="app-shell" data-app-shell>
    <aside class="sidebar" data-sidebar>
        <div class="brand"><div class="brand-mark">N</div><div><strong>NOSH</strong><span>CRM Saúde</span></div></div>
        <div class="tenant-card"><span>CLÍNICA ATUAL</span><strong>{{ auth()->user()->clinic->name }}</strong><small>{{ auth()->user()->clinic->city }} · {{ auth()->user()->roleLabel() }}</small></div>
        <nav class="nav-list" aria-label="Navegação principal">
            @if(auth()->user()->canAccess('dashboard.view'))<a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"><span>⌂</span> Visão geral</a>@endif
            @if(auth()->user()->canAccess('patients.view'))<a href="{{ route('patients.index') }}" class="nav-item {{ request()->routeIs('patients.*') ? 'active' : '' }}"><span>♡</span> Pacientes</a>@endif
            @if(auth()->user()->canAccess('leads.view'))<a href="{{ route('leads.index') }}" class="nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}"><span>◎</span> Funil CRM</a>@endif
            @if(auth()->user()->canAccess('appointments.view'))<a href="{{ route('appointments.index') }}" class="nav-item {{ request()->routeIs('appointments.*') ? 'active' : '' }}"><span>□</span> Agenda</a>@endif
            @if(auth()->user()->canAccess('professionals.view'))
                <a href="{{ route('professionals.index') }}" class="nav-item {{ request()->routeIs('professionals.*') ? 'active' : '' }}">
                    <span>+</span> Profissionais
                </a>
            @endif
            @if(auth()->user()->canAccess('clinical.view'))<a href="{{ route('medical-records.index') }}" class="nav-item {{ request()->routeIs('medical-records.*') ? 'active' : '' }}"><span>✚</span> Prontuários</a>@endif
            @if(auth()->user()->canAccess('transfers.view'))<a href="{{ route('transfers.index') }}" class="nav-item {{ request()->routeIs('transfers.*') ? 'active' : '' }}"><span>⇄</span> Transferências</a>@endif
            @if(auth()->user()->canAccess('users.view'))<a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"><span>♙</span> Utilizadores</a>@endif
        </nav>
                <div class="sidebar-card"><span class="eyebrow">MULTI-CLÍNICA</span><strong>v{{ config('noshcrm.version', '0.5.0') }} · {{ config('noshcrm.release_name', 'NOSH CRM Saúde') }}</strong><small>Laravel 13 · Multi-clínica · CRM Saúde</small></div>
    </aside>

    <div class="main-column">
        <header class="topbar">
            <button class="icon-btn mobile-menu" type="button" data-menu-toggle aria-label="Abrir menu">☰</button>
            <div class="topbar-copy"><span class="eyebrow">{{ mb_strtoupper(auth()->user()->clinic->city) }} · AMBIENTE ISOLADO</span><strong>@yield('page-title', 'Visão geral')</strong></div>
            <div class="topbar-actions">
                <div class="notification-center" data-notification-center data-notification-url="{{ route('notifications.summary') }}">
                    <button class="notification-trigger" type="button" data-notification-toggle aria-label="Abrir notificações" aria-expanded="false" aria-controls="nosh-notification-popup">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                        <span class="notification-badge" data-notification-badge hidden>0</span>
                    </button>
                    <section class="notification-popup" id="nosh-notification-popup" data-notification-popup hidden aria-label="Notificações">
                        <header class="notification-popup-head">
                            <div><span class="eyebrow">CENTRO DE ALERTAS</span><strong>Notificações</strong></div>
                            <span data-notification-clinic>{{ auth()->user()->clinic->city }}</span>
                        </header>
                        <div class="notification-summary" data-notification-summary></div>
                        <div class="notification-list" data-notification-list>
                            <div class="notification-loading">A carregar notificações…</div>
                        </div>
                        <footer class="notification-popup-foot">Atualização automática · dados apenas desta clínica</footer>
                    </section>
                </div>
                <span class="status-dot"></span><span class="topbar-user">{{ auth()->user()->name }}</span>
                <div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</div>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="logout-btn" type="submit">Sair</button></form>
            </div>
        </header>
        <main class="content">
            @if(session('success'))<div class="flash" data-flash>{{ session('success') }} <button type="button" data-flash-close>×</button></div>@endif
            @if($errors->any() && !request()->routeIs('patients.*'))<div class="error-box page-error">{{ $errors->first() }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
