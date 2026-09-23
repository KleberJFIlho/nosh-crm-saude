@extends('layouts.app')

@php($editing = $professional->exists)
@section('title', ($editing ? 'Editar profissional' : 'Novo profissional').' · NOSH CRM Saúde')
@section('page-title', $editing ? 'Editar profissional' : 'Novo profissional')

@section('content')
<div class="page-heading professional-heading">
    <div>
        <a class="back-link" href="{{ route('professionals.index') }}">← Voltar para profissionais</a>
        <span class="eyebrow">{{ mb_strtoupper(auth()->user()->clinic->city) }} · EQUIPA</span>
        <h1>{{ $editing ? 'Editar profissional' : 'Adicionar profissional' }}</h1>
        <p>A foto é opcional e ficará vinculada somente ao cadastro desta clínica.</p>
    </div>
</div>

@if($errors->any())
<div class="error-box page-error"><strong>Revise os dados:</strong><br>{{ $errors->first() }}</div>
@endif

<form class="professional-form-card" method="post" enctype="multipart/form-data" action="{{ $editing ? route('professionals.update', $professional) : route('professionals.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <section class="professional-photo-section">
        <div class="professional-avatar professional-avatar-preview" data-professional-photo-preview>
            @if($professional->photo_url)
                <img src="{{ $professional->photo_url }}" alt="Foto atual de {{ $professional->full_name }}">
            @else
                <span>{{ $professional->exists ? $professional->initials() : 'PS' }}</span>
            @endif
        </div>
        <div>
            <h2>Avatar / foto do profissional</h2>
            <p>JPG, PNG ou WebP. Tamanho máximo: 2 MB.</p>
            <label class="photo-upload-btn">
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-professional-photo-input>
                <span>Escolher foto</span>
            </label>
            @if($editing && $professional->photo_path)
                <label class="check-row"><input type="checkbox" name="remove_photo" value="1"> Remover foto atual</label>
            @endif
        </div>
    </section>

    <div class="form-grid professional-form-grid">
        <label class="field"><span>Tipo de profissional *</span><select name="professional_type" required>
            <option value="">Selecione</option>
            <option value="doctor" @selected(old('professional_type', $professional->professional_type) === 'doctor')>Médico</option>
            <option value="nurse" @selected(old('professional_type', $professional->professional_type) === 'nurse')>Enfermeiro</option>
            <option value="assistant" @selected(old('professional_type', $professional->professional_type) === 'assistant')>Auxiliar</option>
        </select></label>

        <label class="field field-wide"><span>Nome completo *</span><input type="text" name="full_name" maxlength="160" required value="{{ old('full_name', $professional->full_name) }}" placeholder="Ex.: Dra. Maria Silva"></label>

        <label class="field"><span>N.º de registo profissional</span><input type="text" name="registration_number" maxlength="80" value="{{ old('registration_number', $professional->registration_number) }}" placeholder="Ordem / cédula"></label>
        <label class="field"><span>Especialidade</span><input type="text" name="specialty" maxlength="120" value="{{ old('specialty', $professional->specialty) }}" placeholder="Ex.: Medicina Geral e Familiar"></label>
        <label class="field"><span>Telefone</span><input type="text" name="phone" maxlength="40" value="{{ old('phone', $professional->phone) }}"></label>
        <label class="field"><span>E-mail</span><input type="email" name="email" maxlength="190" value="{{ old('email', $professional->email) }}"></label>

        <label class="field field-wide"><span>Utilizador do sistema (opcional)</span><select name="user_id">
            <option value="">Sem acesso vinculado</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $professional->user_id) === (string) $user->id)>{{ $user->name }} · {{ $user->email }}</option>
            @endforeach
        </select><small>Somente utilizadores ativos da clínica atual podem ser vinculados.</small></label>

        <label class="field field-wide"><span>Observações</span><textarea name="notes" rows="4" maxlength="2000" placeholder="Informações administrativas sobre o profissional">{{ old('notes', $professional->notes) }}</textarea></label>
    </div>

    <label class="active-switch"><input type="checkbox" name="active" value="1" @checked(old('active', $professional->active ?? true))><span>Profissional ativo nesta clínica</span></label>

    <div class="form-actions">
        <a class="btn btn-ghost" href="{{ route('professionals.index') }}">Cancelar</a>
        <button class="btn btn-primary" type="submit">{{ $editing ? 'Guardar alterações' : 'Cadastrar profissional' }}</button>
    </div>
</form>
@endsection
