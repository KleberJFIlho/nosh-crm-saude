<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Segurança · NOSH CRM Saúde</title>
    <link rel="stylesheet" href="{{ asset('css/nosh-patient-v070.css') }}?v=0.7.4">
    <style>
        .first-access-alert{max-width:760px;margin:0 0 18px;padding:16px 18px;border:1px solid #b9dfcf;background:#edf9f4;border-radius:14px;color:#134d3f;line-height:1.5}
        .first-access-alert strong{display:block;margin-bottom:4px}.security-card input{width:100%;box-sizing:border-box}.security-card label{display:block;margin-bottom:14px}.security-card small{display:block;margin:8px 0 18px}
    </style>
</head>
<body class="portal-body">
<header class="portal-header">
    <a href="{{ $account->must_change_password ? route('patient.security') : route('patient.dashboard') }}" class="patient-brand">✚ <strong>NOSH CRM Saúde</strong></a>
    @unless($account->must_change_password)
        <nav>
            <a href="{{ route('patient.dashboard') }}">Visão geral</a>
            <a href="{{ route('patient.appointments') }}">Consultas</a>
            <a href="{{ route('patient.exams') }}">Resultados de exames</a>
            <a class="active" href="{{ route('patient.security') }}">Segurança</a>
        </nav>
    @endunless
    <form method="post" action="{{ route('patient.logout') }}">@csrf<button>Sair</button></form>
</header>
<main class="portal-main">
    <section class="portal-page-head">
        <span>CONTA DO PACIENTE</span>
        <h1>{{ $account->must_change_password ? 'Defina a sua palavra-passe' : 'Segurança' }}</h1>
        <p>{{ $account->must_change_password ? 'A credencial provisória é de uso único. Para continuar, crie agora uma palavra-passe pessoal.' : 'Altere a sua palavra-passe de acesso à área do paciente.' }}</p>
    </section>

    @if(session('warning'))
        <div class="first-access-alert" role="alert"><strong>Primeiro acesso protegido</strong>{{ session('warning') }}</div>
    @endif
    @if($account->must_change_password)
        <div class="first-access-alert"><strong>Troca obrigatória</strong>Por segurança, consultas, exames e restantes áreas só serão liberados depois desta alteração.</div>
    @endif
    @if(session('success'))<div class="portal-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="portal-error security-error">{{ $errors->first() }}</div>@endif

    <section class="security-card">
        <form method="post" action="{{ route('patient.password.update') }}">
            @csrf
            @method('PUT')
            <label>Palavra-passe {{ $account->must_change_password ? 'provisória' : 'atual' }}
                <input type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <label>Nova palavra-passe
                <input type="password" name="password" minlength="10" autocomplete="new-password" required>
            </label>
            <label>Confirmar nova palavra-passe
                <input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required>
            </label>
            <small>Use pelo menos 10 caracteres e não reutilize a credencial provisória nem palavras-passe de outros serviços.</small>
            <button class="portal-primary" type="submit">{{ $account->must_change_password ? 'Definir palavra-passe e continuar' : 'Atualizar palavra-passe' }}</button>
        </form>
    </section>
</main>
</body>
</html>
