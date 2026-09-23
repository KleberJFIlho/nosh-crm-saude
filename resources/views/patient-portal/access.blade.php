@extends('layouts.app')

@section('title', 'Acesso do paciente · NOSH CRM Saúde')
@section('page-title', 'Portal do paciente')

@section('content')
<link rel="stylesheet" href="{{ asset('css/nosh-patient-access-v074.css') }}?v=0.7.4">

@php
    $activeValue = (string) old('active', $account ? ($account->active ? '1' : '0') : '1');
    $nameParts = preg_split('/\s+/', trim($patient->full_name));
    $initials = mb_strtoupper(mb_substr($nameParts[0] ?? 'P', 0, 1).mb_substr($nameParts[count($nameParts)-1] ?? '', 0, 1));
    $accessStatus = !$account ? 'not-created' : ($account->active ? 'active' : 'inactive');
    $accessStatusLabel = !$account ? 'Acesso não criado' : ($account->active ? 'Acesso ativo' : 'Acesso inativo');
    $defaultChannel = old('delivery_channel', $emailAvailable ? 'email' : ($smsAvailable ? 'sms' : ''));
@endphp

<div class="pa-page" data-patient-access>
    <a class="pa-back" href="{{ route('patients.show', $patient) }}">
        <span aria-hidden="true">←</span><span>Voltar ao paciente</span>
    </a>

    @if(session('success'))
        <div class="pa-alert pa-alert-success" role="status"><strong>Concluído.</strong> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="pa-alert pa-alert-error" role="alert"><strong>Não foi possível concluir.</strong> {{ $errors->first() }}</div>
    @endif

    <div class="pa-layout">
        <section class="pa-card pa-main-card">
            <header class="pa-card-header">
                <div class="pa-secure-icon" aria-hidden="true">🛡</div>
                <div><span class="pa-eyebrow">ACESSO EXTERNO SEGURO</span><h1>{{ $patient->full_name }}</h1></div>
            </header>
            <p class="pa-intro">Configure o login e envie uma credencial provisória somente para um contacto já cadastrado do paciente.</p>
            <div class="pa-divider"></div>

            <form method="post" action="{{ route('patients.portal-access.update', $patient) }}" class="pa-form">
                @csrf
                @method('PUT')

                <div class="pa-form-grid pa-config-grid">
                    <label class="pa-field">
                        <span class="pa-label">E-mail de acesso <b>*</b></span>
                        <div class="pa-control"><input type="email" name="email" value="{{ old('email', $account?->email ?? $patient->email) }}" required autocomplete="off"></div>
                    </label>
                    <label class="pa-field">
                        <span class="pa-label">Estado</span>
                        <div class="pa-control"><select name="active"><option value="1" @selected($activeValue==='1')>Ativo</option><option value="0" @selected($activeValue==='0')>Inativo</option></select></div>
                    </label>
                </div>

                <section class="pa-delivery-section" aria-labelledby="delivery-title">
                    <div class="pa-delivery-head">
                        <div><span class="pa-eyebrow">ENTREGA SEGURA</span><h2 id="delivery-title">Canal cadastrado do paciente</h2></div>
                        <span class="pa-expiry">Validade: {{ config('noshcrm.patient_portal.temporary_password_minutes', 30) }} min</span>
                    </div>
                    <p>O sistema gera a senha no servidor, envia pelo canal selecionado e guarda somente o hash. A senha nunca é exibida ao funcionário.</p>

                    <div class="pa-channel-grid">
                        <label class="pa-channel {{ !$emailAvailable ? 'is-disabled' : '' }}">
                            <input type="radio" name="delivery_channel" value="email" @checked($defaultChannel==='email') @disabled(!$emailAvailable)>
                            <span class="pa-channel-icon">✉</span>
                            <span><strong>E-mail cadastrado</strong><small>{{ $maskedEmail ?: 'Nenhum e-mail cadastrado' }}</small></span>
                            <i></i>
                        </label>
                        <label class="pa-channel {{ !$smsAvailable ? 'is-disabled' : '' }}">
                            <input type="radio" name="delivery_channel" value="sms" @checked($defaultChannel==='sms') @disabled(!$smsAvailable)>
                            <span class="pa-channel-icon">☏</span>
                            <span><strong>SMS para telefone cadastrado</strong><small>{{ $maskedPhone ?: 'Nenhum telefone cadastrado' }}@if($smsHasPhone && !$smsAvailable) · gateway não configurado @endif</small></span>
                            <i></i>
                        </label>
                    </div>

                    @if(!$emailAvailable && !$smsHasPhone)
                        <div class="pa-inline-warning">Cadastre um e-mail ou telefone no perfil do paciente antes de gerar o acesso.</div>
                    @elseif($smsHasPhone && !$smsAvailable)
                        <div class="pa-inline-info">O telefone está cadastrado. Para liberar SMS, configure <code>NOSH_SMS_WEBHOOK_URL</code> no ambiente.</div>
                    @endif
                </section>

                <div class="pa-actions pa-secure-actions">
                    <button class="pa-btn pa-btn-soft" type="submit" name="action" value="save">Guardar configuração</button>
                    <button class="pa-btn pa-btn-primary" type="submit" name="action" value="send" @disabled(!$emailAvailable && !$smsAvailable)>
                        🔐 <span>Gerar e enviar acesso</span>
                    </button>
                </div>

                <div class="pa-security-note">
                    <strong>Proteção aplicada</strong>
                    <span>Senha provisória de uso único, expiração automática, troca obrigatória no primeiro acesso e auditoria sem armazenar a senha em texto simples.</span>
                </div>

                @if($account?->credentials_sent_at)
                    <div class="pa-last-delivery">
                        <span>Último envio</span>
                        <strong>{{ $account->credentials_sent_at->format('d/m/Y H:i') }} · {{ strtoupper($account->credentials_sent_via ?? '—') }}</strong>
                        @if($account->must_change_password && $account->temporary_password_expires_at)
                            <small>Credencial provisória válida até {{ $account->temporary_password_expires_at->format('d/m/Y H:i') }}@if($account->temporary_password_used_at) · já utilizada @endif</small>
                        @endif
                    </div>
                @endif

                <div class="pa-login-box">
                    <div><span>Login do paciente</span><strong id="patientPortalLoginUrl">{{ url('/paciente/login') }}</strong></div>
                    <button type="button" id="copyPatientPortalUrl" aria-label="Copiar endereço do portal">Copiar</button>
                </div>
            </form>
        </section>

        <aside class="pa-card pa-side-card">
            <div class="pa-patient-summary"><div class="pa-patient-avatar">{{ $initials }}</div><div><h2>{{ $patient->full_name }}</h2><span class="pa-status pa-status-{{ $accessStatus }}"><i></i>{{ $accessStatusLabel }}</span></div></div>
            <div class="pa-side-divider"></div>
            <div class="pa-guidance-list">
                <div class="pa-guidance-item"><div class="pa-guidance-icon">✓</div><div><strong>Canal previamente cadastrado</strong><p>Não é permitido digitar um novo destino durante o envio da credencial.</p></div></div>
                <div class="pa-guidance-item"><div class="pa-guidance-icon">⏱</div><div><strong>Expiração automática</strong><p>A credencial provisória expira em {{ config('noshcrm.patient_portal.temporary_password_minutes', 30) }} minutos.</p></div></div>
                <div class="pa-guidance-item"><div class="pa-guidance-icon">1×</div><div><strong>Uso único</strong><p>Depois da primeira autenticação, a mesma senha provisória não poderá iniciar outra sessão.</p></div></div>
                <div class="pa-guidance-item"><div class="pa-guidance-icon">🔒</div><div><strong>Troca obrigatória</strong><p>Consultas e exames ficam bloqueados até o paciente definir a própria palavra-passe.</p></div></div>
            </div>
            <div class="pa-recommendation">A auditoria guarda o canal, destino mascarado, utilizador e horário. <strong>A senha nunca é registada.</strong></div>
            <footer class="pa-side-footer"><span>v{{ config('noshcrm.version', '0.7.4') }} · Portal do Paciente</span><small>{{ config('noshcrm.release_name', 'Entrega Segura de Credenciais') }}</small></footer>
        </aside>
    </div>
</div>
<script src="{{ asset('js/nosh-patient-access-v074.js') }}?v=0.7.4" defer></script>
@endsection
