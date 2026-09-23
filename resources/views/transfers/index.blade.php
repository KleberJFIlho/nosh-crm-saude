@extends('layouts.app')
@section('title', 'Transferências · NOSH CRM Saúde')
@section('page-title', 'Transferências')
<link rel="stylesheet" href="{{ asset('css/nosh-ui-v050.css') }}?v={{ config('noshcrm.version', '0.5.0') }}">
@section('content')
<div class="nosh-v050 transfers-v050">
    <div class="v5-actions-row"><div class="v5-context-copy"><span>Consentimento + auditoria</span><p>O paciente só muda de clínica por um fluxo explícito e auditado.</p></div>@if(auth()->user()->canAccess('transfers.request'))<button class="v5-primary-btn" type="button" data-modal-open="new-transfer">＋ Solicitar transferência</button>@endif</div>

    <section class="v5-stats">
        <article class="v5-stat"><span>A receber</span><strong>{{ $stats['incoming_pending'] }}</strong><small>Pendentes no destino</small></article>
        <article class="v5-stat"><span>Enviadas</span><strong>{{ $stats['outgoing_pending'] }}</strong><small>Aguardam resposta</small></article>
        <article class="v5-stat"><span>Aceites</span><strong>{{ $stats['accepted'] }}</strong><small>Últimos pedidos listados</small></article>
        <article class="v5-stat"><span>Recusadas</span><strong>{{ $stats['rejected'] }}</strong><small>Últimos pedidos listados</small></article>
    </section>

    <div class="v5-flow"><span>1. Solicitação</span><i>→</i><span>2. Consentimento</span><i>→</i><span>3. Aceite do destino</span><i>→</i><span>4. Transferência</span></div>

    <div class="v5-two-columns">
        <section class="v5-panel"><div class="v5-panel-head"><div><span>SAÍDA</span><h2>Pedidos enviados</h2></div></div><div class="v5-transfer-list">
            @forelse($outgoing as $transfer) @php($p=$visiblePatients->get($transfer->patient_id))
                <article class="v5-transfer-card"><div><span>#{{ $transfer->id }} · {{ $transfer->fromClinic->city }} → {{ $transfer->toClinic->city }}</span><h3>{{ $transfer->status==='pending' && $p ? $p->full_name : ($transfer->status==='accepted' ? 'Paciente transferido' : 'Pedido encerrado') }}</h3><p>{{ $transfer->reason }}</p></div><span class="v5-status status-{{ $transfer->status }}">{{ $transfer->status==='pending' ? 'Pendente' : ($transfer->status==='accepted' ? 'Aceite' : 'Recusada') }}</span></article>
            @empty<div class="v5-empty compact"><strong>Nenhum pedido enviado</strong></div>@endforelse
        </div></section>

        <section class="v5-panel"><div class="v5-panel-head"><div><span>ENTRADA</span><h2>Pedidos recebidos</h2></div></div><div class="v5-transfer-list">
            @forelse($incoming as $transfer) @php($p=$visiblePatients->get($transfer->patient_id))
                <article class="v5-transfer-card incoming"><div class="v5-transfer-main"><div><span>#{{ $transfer->id }} · Origem: {{ $transfer->fromClinic->name }}</span><h3>{{ $transfer->status==='pending' && $p ? $p->full_name : ($transfer->status==='accepted' ? 'Paciente recebido' : 'Pedido encerrado') }}</h3>@if($transfer->status==='pending')<p>{{ $transfer->reason }}</p><small class="v5-consent">✓ Consentimento em {{ $transfer->patient_consent_at->format('d/m/Y H:i') }}</small>@endif</div><span class="v5-status status-{{ $transfer->status }}">{{ $transfer->status==='pending' ? 'Pendente' : ($transfer->status==='accepted' ? 'Aceite' : 'Recusada') }}</span></div>
                @if($transfer->status==='pending' && auth()->user()->canAccess('transfers.respond'))<div class="v5-transfer-actions"><form method="post" action="{{ route('transfers.accept',$transfer) }}">@csrf<input class="input" name="response_notes" placeholder="Nota opcional"><button class="v5-primary-btn small" type="submit" onclick="return confirm('Aceitar e transferir o paciente?')">Aceitar</button></form><form method="post" action="{{ route('transfers.reject',$transfer) }}">@csrf<input class="input" name="response_notes" required placeholder="Motivo da recusa"><button class="v5-secondary-btn danger" type="submit">Recusar</button></form></div>@endif
                </article>
            @empty<div class="v5-empty compact"><strong>Nenhum pedido recebido</strong></div>@endforelse
        </div></section>
    </div>

    @if(auth()->user()->canAccess('transfers.request'))
    <div class="modal" data-modal="new-transfer" hidden><div class="modal-backdrop" data-modal-close></div><form class="modal-card" method="post" action="{{ route('transfers.store') }}">@csrf<div class="modal-head"><div><span class="eyebrow">NOVO PEDIDO</span><h2>Transferir paciente</h2></div><button class="icon-btn" type="button" data-modal-close>×</button></div><div class="form-grid"><label><span>Paciente *</span><select class="input" name="patient_id" required><option value="">Selecione...</option>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->full_name }}</option>@endforeach</select></label><label><span>Clínica de destino *</span><select class="input" name="to_clinic_id" required><option value="">Selecione...</option>@foreach($clinics as $clinic)<option value="{{ $clinic->id }}">{{ $clinic->name }}</option>@endforeach</select></label><label class="span-2"><span>Motivo *</span><textarea class="input" name="reason" rows="4" required></textarea></label><label class="span-2 consent-check"><input type="checkbox" name="patient_consent" value="1" required><span><strong>Confirmo que o paciente solicitou/autorizou a transferência.</strong><small>O consentimento ficará registado na auditoria.</small></span></label></div><div class="form-actions"><button class="btn" type="button" data-modal-close>Cancelar</button><button class="btn btn-primary" type="submit">Enviar pedido</button></div></form></div>
    @endif
</div>
@endsection
