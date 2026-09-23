@extends('layouts.app')
@section('title', ($patient->exists ? 'Editar paciente' : 'Novo paciente').' · NOSH CRM Saúde')
@section('page-title', $patient->exists ? 'Editar paciente' : 'Novo paciente')
@section('content')
<div class="page-heading compact"><div><span class="eyebrow">CADASTRO</span><h1>{{ $patient->exists ? 'Atualizar paciente' : 'Novo paciente' }}</h1><p>Dados de relacionamento e acompanhamento.</p></div><a class="btn" href="{{ route('patients.index') }}">← Voltar</a></div>
<form class="panel form-panel" method="post" action="{{ $patient->exists ? route('patients.update', $patient) : route('patients.store') }}">@csrf @if($patient->exists) @method('PUT') @endif
    <div class="form-grid">
        <label><span>Nome *</span><input class="input" name="first_name" required value="{{ old('first_name', $patient->first_name) }}"></label>
        <label><span>Apelido *</span><input class="input" name="last_name" required value="{{ old('last_name', $patient->last_name) }}"></label>
        <label><span>E-mail</span><input class="input" type="email" name="email" value="{{ old('email', $patient->email) }}"></label>
        <label><span>Telefone</span><input class="input" name="phone" value="{{ old('phone', $patient->phone) }}"></label>
        <label><span>Data de nascimento</span><input class="input" type="date" name="birth_date" value="{{ old('birth_date', optional($patient->birth_date)->format('Y-m-d')) }}"></label>
        <label><span>Género</span><select class="input" name="gender"><option value="">Não informado</option>@foreach(['female'=>'Feminino','male'=>'Masculino','other'=>'Outro','prefer_not_to_say'=>'Prefere não informar'] as $value=>$label)<option value="{{ $value }}" @selected(old('gender',$patient->gender)===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Estado *</span><select class="input" name="status" required>@foreach(['active'=>'Ativo','inactive'=>'Inativo','prospect'=>'Prospect'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$patient->status ?: 'active')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Origem</span><input class="input" name="source" placeholder="Website, indicação, campanha..." value="{{ old('source', $patient->source) }}"></label>
        <label class="span-2"><span>Próximo follow-up</span><input class="input" type="datetime-local" name="next_follow_up_at" value="{{ old('next_follow_up_at', $patient->next_follow_up_at?->format('Y-m-d\TH:i')) }}"></label>
        <label class="span-2"><span>Notas</span><textarea class="input" name="notes" rows="5" placeholder="Contexto do relacionamento, preferências e observações...">{{ old('notes', $patient->notes) }}</textarea></label>
    </div>
    @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
    <div class="form-actions"><a class="btn" href="{{ route('patients.index') }}">Cancelar</a><button class="btn btn-primary" type="submit">{{ $patient->exists ? 'Guardar alterações' : 'Criar paciente' }}</button></div>
</form>
@endsection
