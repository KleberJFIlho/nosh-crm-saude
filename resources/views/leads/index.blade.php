@extends('layouts.app')
@section('title', 'Funil CRM · NOSH CRM Saúde')
@section('page-title', 'Funil CRM')
@section('content')
<div class="page-heading compact"><div><span class="eyebrow">{{ mb_strtoupper(auth()->user()->clinic->name) }}</span><h1>Funil de oportunidades</h1><p>Leads isolados por clínica, do primeiro contacto à conversão.</p></div>@if(auth()->user()->canAccess('leads.create'))<button class="btn btn-primary" type="button" data-modal-open="new-lead">+ Novo lead</button>@endif</div>
<div class="kanban">
@php($labels=['new'=>'Novo','contacted'=>'Contactado','qualified'=>'Qualificado','proposal'=>'Proposta','won'=>'Convertido','lost'=>'Perdido'])
@foreach($columns as $column)
<section class="kanban-column"><header><span>{{ $labels[$column] }}</span><strong>{{ ($leads[$column] ?? collect())->count() }}</strong></header><div class="kanban-stack">
@forelse($leads[$column] ?? [] as $lead)
<article class="lead-card"><div class="lead-card-head"><span class="score-chip">{{ $lead->score }}</span><small>{{ $lead->source ?: 'Direto' }}</small></div><h3>{{ $lead->name }}</h3><p>{{ $lead->interest }}</p><div class="lead-meta"><span>{{ $lead->phone ?: 'Sem telefone' }}</span><strong>{{ $lead->estimated_value ? '€ '.number_format((float)$lead->estimated_value,2,',','.') : '—' }}</strong></div>
@if(auth()->user()->canAccess('leads.edit'))<form method="post" action="{{ route('leads.update',$lead) }}">@csrf @method('PATCH')<select class="input compact-select" name="status" onchange="this.form.submit()">@foreach($labels as $value=>$label)<option value="{{ $value }}" @selected($lead->status===$value)>{{ $label }}</option>@endforeach</select></form>@else<span class="badge">{{ $labels[$lead->status] }}</span>@endif
@if(auth()->user()->canAccess('leads.delete'))<form class="lead-delete" method="post" action="{{ route('leads.destroy',$lead) }}" onsubmit="return confirm('Remover este lead?')">@csrf @method('DELETE')<button class="link-danger" type="submit">Remover</button></form>@endif
</article>
@empty<div class="kanban-empty">Sem oportunidades</div>@endforelse
</div></section>
@endforeach
</div>
@if(auth()->user()->canAccess('leads.create'))<div class="modal" data-modal="new-lead" hidden><div class="modal-backdrop" data-modal-close></div><div class="modal-card"><div class="modal-head"><div><span class="eyebrow">NOVO CONTACTO</span><h2>Adicionar lead</h2></div><button class="icon-btn" data-modal-close type="button">×</button></div><form method="post" action="{{ route('leads.store') }}">@csrf<div class="form-grid"><label><span>Nome *</span><input class="input" name="name" required></label><label><span>Interesse *</span><input class="input" name="interest" required></label><label><span>E-mail</span><input class="input" type="email" name="email"></label><label><span>Telefone</span><input class="input" name="phone"></label><label><span>Origem</span><input class="input" name="source"></label><label><span>Score *</span><input class="input" type="number" min="0" max="100" name="score" value="50" required></label><label><span>Valor estimado (€)</span><input class="input" type="number" step="0.01" min="0" name="estimated_value"></label><label><span>Próximo contacto</span><input class="input" type="datetime-local" name="next_contact_at"></label></div><div class="form-actions"><button class="btn" data-modal-close type="button">Cancelar</button><button class="btn btn-primary" type="submit">Adicionar ao funil</button></div></form></div></div>@endif
@endsection
