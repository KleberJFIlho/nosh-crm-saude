<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar · NOSH CRM Saúde</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
<div class="login-shell">
    <section class="login-brand-panel">
        <div class="brand login-brand"><div class="brand-mark">N</div><div><strong>NOSH</strong><span>CRM Saúde</span></div></div>
        <div class="login-hero-copy">
            <span class="eyebrow light">PLATAFORMA MULTI-CLÍNICA</span>
            <h1>Uma rede. Clínicas isoladas. Dados protegidos.</h1>
            <p>Cada unidade trabalha apenas com os seus próprios pacientes, leads e agenda. Transferências entre clínicas exigem consentimento e aceite.</p>
        </div>
        <div class="clinic-preview"><span>Fafe</span><span>Braga</span><span>Guimarães</span><span>+ novas clínicas</span></div>
    </section>
    <section class="login-form-panel">
        <form class="login-card" method="post" action="{{ route('login.store') }}">@csrf
            <span class="eyebrow">ACESSO SEGURO</span><h2>Entrar no CRM</h2><p>Utilize o acesso atribuído à sua clínica.</p>
            <label><span>E-mail</span><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
            <label>
                <span>Palavra-passe</span>
                <div class="password-field">
                    <input id="login-password" class="input password-input" type="password" name="password" required autocomplete="current-password">
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="login-password" aria-label="Mostrar palavra-passe" aria-pressed="false" title="Mostrar palavra-passe">
                        <svg class="password-eye password-eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.1 12s3.6-6 9.9-6 9.9 6 9.9 6-3.6 6-9.9 6-9.9-6-9.9-6Z"/><circle cx="12" cy="12" r="2.8"/></svg>
                        <svg class="password-eye password-eye-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 6.2A9.9 9.9 0 0 1 12 6c6.3 0 9.9 6 9.9 6a17.1 17.1 0 0 1-3.1 3.6M13.9 17.8A10.3 10.3 0 0 1 12 18c-6.3 0-9.9-6-9.9-6a17.7 17.7 0 0 1 4-4.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
            </label>
            <label class="remember-row"><input type="checkbox" name="remember" value="1"> <span>Manter sessão iniciada</span></label>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="btn btn-primary login-submit" type="submit">Entrar</button>
            <div class="demo-login-note"><strong>Ambiente de portfólio</strong><span>Use uma das contas demonstrativas criadas pelo seeder.</span></div>
        </form>
    </section>
</div>
</body>
</html>
