<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>NOSH CRM Saúde · Gestão moderna para clínicas</title>
    <meta name="description" content="CRM multi-clínica para gestão de pacientes, agenda, profissionais, prontuário e portal do paciente.">
    <link rel="stylesheet" href="{{ asset('css/nosh-site-v070.css') }}?v=0.7.0">
</head>
<body class="site-body">
<header class="site-header">
    <a href="{{ route('site.home') }}" class="brand"><span class="brand-mark">✚</span><span>NOSH <b>CRM Saúde</b></span></a>
    <button class="mobile-menu" type="button" data-menu-toggle aria-label="Abrir menu">☰</button>
    <nav class="site-nav" data-menu>
        <a href="#solucoes">Soluções</a><a href="#recursos">Recursos</a><a href="#seguranca">Segurança</a><a href="#demonstracao">Contacto</a>
    </nav>
    <div class="header-actions">
        <a class="ghost-link" href="{{ route('patient.login') }}">Login paciente</a>
        <a class="ghost-link" href="{{ route('login') }}">Login utilizador</a>
        <a class="primary-btn small" href="#demonstracao">Solicitar demonstração</a>
    </div>
</header>

<main>
<section class="hero">
    <div class="hero-copy">
        <span class="hero-pill">● CRM completo para clínicas e profissionais da saúde</span>
        <h1>O CRM que conecta sua clínica. Cuida de pessoas. <em>Impulsiona resultados.</em></h1>
        <p>Gestão inteligente de pacientes, equipas e atendimentos numa plataforma segura, prática, multi-clínica e preparada para crescer.</p>
        <div class="hero-actions"><a class="primary-btn" href="#demonstracao">Solicitar demonstração →</a><a class="secondary-btn" href="#solucoes">Ver como funciona</a></div>
        <div class="trust-row"><span>✓ Multi-clínica</span><span>✓ Dados protegidos</span><span>✓ Portal do paciente</span></div>
    </div>

    <div class="glass-stage" data-carousel>
        <button class="carousel-arrow left" data-prev aria-label="Anterior">←</button>
        <div class="glass-slide is-active" data-slide>
            <div class="slide-copy"><span>01 / 03</span><h2>Visão completa da clínica <b>em um só lugar</b></h2><p>Indicadores, agenda, pacientes, equipas e resultados em tempo real.</p><ul><li>Dashboard operacional</li><li>Agenda inteligente</li><li>Gestão multi-clínica</li><li>Funil CRM</li></ul></div>
            <div class="mock-dashboard"><div class="mock-top">Bem-vindo, Dr. Ricardo <span>Clínica Fafe ▾</span></div><div class="mock-kpis"><div><small>Atendimentos</small><strong>1.248</strong><em>+18%</em></div><div><small>Novos pacientes</small><strong>324</strong><em>+22%</em></div><div><small>Ocupação</small><strong>78%</strong><em>+9%</em></div></div><div class="mock-chart"><span></span><span></span><span></span><span></span><span></span></div></div>
        </div>
        <div class="glass-slide" data-slide>
            <div class="slide-copy"><span>02 / 03</span><h2>Prontuário clínico <b>seguro e organizado</b></h2><p>Evoluções, sinais vitais, alergias, diagnósticos, prescrições e documentos numa experiência simples.</p><ul><li>Histórico clínico</li><li>Auditoria de acessos</li><li>Anexos privados</li><li>Permissões por perfil</li></ul></div>
            <div class="mock-record"><div class="record-avatar">MC</div><div><small>Paciente</small><h3>Maria Costa</h3><p>Último atendimento · hoje 14:30</p></div><div class="record-lines"><i></i><i></i><i></i><i></i></div></div>
        </div>
        <div class="glass-slide" data-slide>
            <div class="slide-copy"><span>03 / 03</span><h2>O paciente acompanha <b>consultas e exames</b></h2><p>Uma área exclusiva para acompanhar consultas e consultar resultados de exames disponibilizados pela clínica.</p><ul><li>Login separado e seguro</li><li>Próximas consultas</li><li>Histórico de atendimentos</li><li>Resultados de exames</li></ul></div>
            <div class="mock-patient"><div class="patient-welcome">Olá, Ana 👋</div><div class="patient-card"><small>Próxima consulta</small><strong>18 AGO · 10:30</strong><span>Dr. Ricardo Costa · Clínica Fafe</span></div><div class="patient-card"><small>Novo resultado</small><strong>Análises clínicas</strong><span>Disponível para consulta</span></div></div>
        </div>
        <button class="carousel-arrow right" data-next aria-label="Seguinte">→</button>
        <div class="carousel-dots"><button class="active" data-dot="0"></button><button data-dot="1"></button><button data-dot="2"></button></div>
    </div>
</section>

<section class="metric-strip" id="recursos"><article><strong>Multi-clínica</strong><span>Fafe, Braga, Guimarães e novas unidades isoladas.</span></article><article><strong>99,9%</strong><span>Arquitetura preparada para alta disponibilidade.</span></article><article><strong>Portal do paciente</strong><span>Consultas e exames num acesso exclusivo.</span></article><article><strong>Auditoria</strong><span>Ações críticas registadas e rastreáveis.</span></article></section>

<section class="features" id="solucoes"><div class="section-heading"><span>ECOSSISTEMA NOSH</span><h2>Tudo o que sua clínica precisa, <b>em um só lugar</b></h2></div><div class="feature-grid">
    <article><i>▦</i><h3>Multi-clínica</h3><p>Unidades separadas com utilizadores e pacientes isolados por clínica.</p></article>
    <article><i>□</i><h3>Agenda inteligente</h3><p>Consultas associadas a profissionais de saúde cadastrados.</p></article>
    <article><i>✚</i><h3>Prontuário eletrónico</h3><p>Evoluções, sinais vitais, diagnósticos, prescrições e anexos privados.</p></article>
    <article><i>◎</i><h3>Profissionais</h3><p>Médicos, enfermeiros e auxiliares com avatar e vínculo à clínica.</p></article>
    <article><i>♢</i><h3>Portal do paciente</h3><p>Acompanhamento de consultas e resultados de exames liberados.</p></article>
    <article><i>◉</i><h3>Notificações</h3><p>Alertas de consultas, follow-ups e transferências entre clínicas.</p></article>
</div></section>

<section class="patient-access-section" id="seguranca"><div><span>DOIS ACESSOS, DUAS EXPERIÊNCIAS</span><h2>Paciente e equipa entram por áreas separadas.</h2><p>Isso evita misturar permissões internas com dados do paciente. Cada ambiente possui a própria experiência e regras de acesso.</p></div><div class="access-cards"><a href="{{ route('patient.login') }}"><i>♡</i><strong>Área do paciente</strong><span>Consultas, exames e informações pessoais →</span></a><a href="{{ route('login') }}"><i>⌘</i><strong>Área da clínica</strong><span>CRM, prontuário, agenda e gestão →</span></a></div></section>

<section class="demo-section" id="demonstracao"><div class="demo-copy"><span>CONHEÇA O NOSH CRM SAÚDE</span><h2>Veja como a plataforma pode funcionar na sua clínica.</h2><p>Preencha os dados e deixe seu pedido registado diretamente no NOSH.</p></div><form method="post" action="{{ route('site.demo.store') }}" class="demo-form">@csrf
@if(session('demo_success'))<div class="form-success">{{ session('demo_success') }}</div>@endif
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
<div class="form-grid"><label>Nome *<input name="name" value="{{ old('name') }}" required></label><label>Clínica *<input name="clinic_name" value="{{ old('clinic_name') }}" required></label><label>E-mail *<input type="email" name="email" value="{{ old('email') }}" required></label><label>Telefone<input name="phone" value="{{ old('phone') }}"></label><label>Cidade<input name="city" value="{{ old('city') }}"></label><label class="wide">Mensagem<textarea name="message" rows="3">{{ old('message') }}</textarea></label></div><button class="primary-btn" type="submit">Solicitar demonstração →</button></form></section>
</main>

<footer class="site-footer"><div class="footer-brand"><div class="brand"><span class="brand-mark">✚</span><span>NOSH <b>CRM Saúde</b></span></div><p>CRM para clínicas que querem crescer com organização, tecnologia e foco no paciente.</p></div><div><strong>Soluções</strong><a href="#solucoes">Multi-clínica</a><a href="#solucoes">Agenda</a><a href="#solucoes">Prontuário</a></div><div><strong>Acessos</strong><a href="{{ route('patient.login') }}">Login paciente</a><a href="{{ route('login') }}">Login utilizador</a></div><div><strong>Privacidade</strong><span>Dados clínicos protegidos por autenticação e autorização.</span></div><small>© {{ date('Y') }} NOSH CRM Saúde · v0.7.0</small></footer>
<script src="{{ asset('js/nosh-site-v070.js') }}?v=0.7.0" defer></script>
</body></html>
