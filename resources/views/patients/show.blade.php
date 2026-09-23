@extends('layouts.app')
@section('title', $patient->full_name.' · NOSH CRM Saúde')
@section('page-title', 'Perfil do paciente')
@section('content')
<div class="patient-profile-head">
    <div class="patient-identity">
        <div class="patient-avatar">{{ mb_strtoupper(mb_substr($patient->first_name,0,1).mb_substr($patient->last_name,0,1)) }}</div>
        <div><span class="eyebrow">PACIENTE #{{ str_pad($patient->id, 5, '0', STR_PAD_LEFT) }}</span><h1>{{ $patient->full_name }}</h1><div class="patient-head-meta"><span class="badge badge-{{ $patient->status }}">{{ ucfirst($patient->status) }}</span><span>Clínica {{ auth()->user()->clinic->city }}</span><span>Origem: {{ $patient->source ?: 'não informada' }}</span></div></div>
    </div>
    <div class="profile-actions">
    <a class="btn" href="{{ route('patients.index') }}">← Pacientes</a>

    @if(auth()->user()->canAccess('clinical.view'))
        <a class="btn" href="{{ route('medical-records.show', $patient) }}">Prontuário clínico</a>
        <a class="btn" href="{{ route('exam-results.index', $patient) }}">Resultados de exames</a>
    @endif

    @if(in_array(auth()->user()->role, ['admin', 'manager'], true))
        <a class="btn" href="{{ route('patients.portal-access.edit', $patient) }}">Acesso do paciente</a>
    @endif

    @if(auth()->user()->canAccess('patients.edit'))
        <a class="btn btn-primary" href="{{ route('patients.edit', $patient) }}">Editar perfil</a>
    @endif
</div>
</div>

@if($errors->any())<div class="error-box page-error">{{ $errors->first() }}</div>@endif

<div class="profile-kpi-grid">
    <article><span>Último contacto</span><strong>{{ $patient->last_contact_at?->format('d/m/Y H:i') ?? 'Sem contacto' }}</strong></article>
    <article><span>Próximo follow-up</span><strong>{{ $patient->next_follow_up_at?->format('d/m/Y H:i') ?? 'Não agendado' }}</strong></article>
    <article><span>Interações</span><strong>{{ $patient->interactions->count() }}</strong></article>
    <article><span>Tarefas pendentes</span><strong>{{ $tasks->where('status', 'pending')->count() }}</strong></article>
</div>

<div class="patient-profile-grid">
    <div class="profile-main">
        <section class="panel">
            <div class="panel-head"><div><span class="eyebrow">HISTÓRICO DE RELACIONAMENTO</span><h2>Timeline do paciente</h2></div>@if(auth()->user()->canAccess('interactions.create'))<button class="btn btn-primary" type="button" data-modal-open="new-interaction">+ Registar interação</button>@endif</div>
            <div class="timeline-list">
                @forelse($timeline as $item)
                    <article class="timeline-item timeline-{{ $item['kind'] }}">
                        <div class="timeline-marker">{{ $item['kind'] === 'appointment' ? '□' : match($item['type']){'call'=>'☎','email'=>'✉','whatsapp'=>'◉','visit'=>'⌂',default=>'•'} }}</div>
                        <div class="timeline-content">
                            <div class="timeline-top"><div><span class="timeline-type">{{ $item['kind'] === 'appointment' ? 'AGENDA' : mb_strtoupper($item['type']) }}</span><strong>{{ $item['title'] }}</strong></div><time>{{ $item['at']?->format('d/m/Y H:i') }}</time></div>
                            <p>{{ $item['content'] }}</p><small>{{ $item['meta'] }}</small>
                            @if($item['kind'] === 'interaction' && auth()->user()->canAccess('interactions.delete'))<form class="timeline-delete" method="post" action="{{ route('patients.interactions.destroy', [$patient, $item['record']]) }}" onsubmit="return confirm('Remover esta interação?')">@csrf @method('DELETE')<button type="submit">Remover</button></form>@endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Ainda não existem interações ou atendimentos na timeline.</div>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="profile-side">
        <section class="panel patient-contact-card">
            <div class="panel-head"><div><span class="eyebrow">CONTACTO</span><h2>Dados do paciente</h2></div></div>
            <dl class="patient-details"><div><dt>Telefone</dt><dd>{{ $patient->phone ?: 'Não informado' }}</dd></div><div><dt>E-mail</dt><dd>{{ $patient->email ?: 'Não informado' }}</dd></div><div><dt>Nascimento</dt><dd>{{ $patient->birth_date?->format('d/m/Y') ?? 'Não informado' }}</dd></div><div><dt>Notas</dt><dd>{{ $patient->notes ?: 'Sem notas gerais.' }}</dd></div></dl>
        </section>

        <section class="panel patient-task-card">
            <div class="panel-head"><div><span class="eyebrow">FOLLOW-UP</span><h2>Tarefas</h2></div>@if(auth()->user()->canAccess('tasks.create'))<button class="mini-add" type="button" data-modal-open="new-task" aria-label="Nova tarefa">+</button>@endif</div>
            <div class="task-list">
                @forelse($tasks as $task)
                    <article class="task-item {{ $task->status !== 'pending' ? 'task-closed' : '' }}">
                        <div class="task-priority priority-{{ $task->priority }}"></div>
                        <div class="task-body"><div class="task-top"><strong>{{ $task->title }}</strong><span class="badge task-status-{{ $task->status }}">{{ match($task->status){'done'=>'Concluída','cancelled'=>'Cancelada',default=>'Pendente'} }}</span></div><p>{{ $task->description ?: ucfirst(str_replace('_',' ',$task->type)) }}</p><small>{{ $task->due_at ? 'Prazo '.$task->due_at->format('d/m/Y H:i') : 'Sem prazo' }} · {{ $task->assignee?->name ?: 'Sem responsável' }}</small>
                            @if(auth()->user()->canAccess('tasks.edit') && $task->status === 'pending')<div class="task-actions"><form method="post" action="{{ route('patients.tasks.update', [$patient, $task]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="done"><button type="submit">✓ Concluir</button></form><form method="post" action="{{ route('patients.tasks.update', [$patient, $task]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button type="submit">Cancelar</button></form></div>@endif
                        </div>
                    </article>
                @empty<div class="empty-state compact-empty">Sem tarefas para este paciente.</div>@endforelse
            </div>
        </section>
    </aside>
</div>

@if(auth()->user()->canAccess('interactions.create'))
<div class="modal" data-modal="new-interaction" hidden><div class="modal-backdrop" data-modal-close></div><div class="modal-card"><div class="modal-head"><div><span class="eyebrow">TIMELINE</span><h2>Nova interação</h2></div><button class="icon-btn" type="button" data-modal-close>×</button></div>
<form method="post" action="{{ route('patients.interactions.store', $patient) }}">@csrf<div class="form-grid">
<label><span>Canal *</span><select class="input" name="type" required><option value="call">Chamada</option><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option><option value="visit">Presencial</option><option value="note">Nota interna</option></select></label>
<label><span>Direção</span><select class="input" name="direction"><option value="">Não aplicável</option><option value="outbound">Enviado / realizado</option><option value="inbound">Recebido</option></select></label>
<label class="span-2"><span>Assunto</span><input class="input" name="subject" maxlength="190" placeholder="Ex.: Confirmação de acompanhamento"></label>
<label class="span-2"><span>Data e hora *</span><input class="input" type="datetime-local" name="occurred_at" required value="{{ now()->format('Y-m-d\TH:i') }}"></label>
<label class="span-2"><span>Registo *</span><textarea class="input" name="content" rows="5" required maxlength="5000" placeholder="Registe o contexto e o resultado do contacto."></textarea></label>
</div><div class="form-actions"><button class="btn" type="button" data-modal-close>Cancelar</button><button class="btn btn-primary" type="submit">Adicionar à timeline</button></div></form></div></div>
@endif

@if(auth()->user()->canAccess('tasks.create'))
<div class="modal" data-modal="new-task" hidden><div class="modal-backdrop" data-modal-close></div><div class="modal-card"><div class="modal-head"><div><span class="eyebrow">FOLLOW-UP</span><h2>Nova tarefa</h2></div><button class="icon-btn" type="button" data-modal-close>×</button></div>
<form method="post" action="{{ route('patients.tasks.store', $patient) }}">@csrf<div class="form-grid">
<label><span>Tipo *</span><select class="input" name="type" required><option value="follow_up">Follow-up</option><option value="call">Chamada</option><option value="email">E-mail</option><option value="document">Documento</option><option value="other">Outro</option></select></label>
<label><span>Prioridade *</span><select class="input" name="priority" required><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option><option value="low">Baixa</option></select></label>
<label class="span-2"><span>Título *</span><input class="input" name="title" required maxlength="190" placeholder="Ex.: Ligar para confirmar evolução"></label>
<label><span>Responsável</span><select class="input" name="assigned_to"><option value="">Sem responsável</option>@foreach($clinicUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->roleLabel() }}</option>@endforeach</select></label>
<label><span>Prazo</span><input class="input" type="datetime-local" name="due_at"></label>
<label class="span-2"><span>Descrição</span><textarea class="input" name="description" rows="4" maxlength="3000" placeholder="Orientações para o próximo contacto."></textarea></label>
</div><div class="form-actions"><button class="btn" type="button" data-modal-close>Cancelar</button><button class="btn btn-primary" type="submit">Criar tarefa</button></div></form></div></div>
@endif
@endsection
