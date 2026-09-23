@extends('layouts.app')
@section('title', 'Prontuário · '.$patient->full_name.' · NOSH CRM Saúde')
@section('page-title', 'Prontuário clínico')
@section('content')
<div class="clinical-page clinical-record-page">
    <header class="record-hero">
        <div class="record-patient">
            <div class="clinical-avatar clinical-avatar-lg">{{ mb_strtoupper(mb_substr($patient->first_name,0,1).mb_substr($patient->last_name,0,1)) }}</div>
            <div><span class="eyebrow">PRONTUÁRIO #{{ str_pad($patient->id,5,'0',STR_PAD_LEFT) }} · {{ mb_strtoupper(auth()->user()->clinic->city) }}</span><h1>{{ $patient->full_name }}</h1><p>{{ $patient->birth_date?->format('d/m/Y') ?? 'Nascimento não informado' }} · {{ $patient->phone ?: 'Telefone não informado' }}</p></div>
        </div>
        <div class="record-actions"><a class="clinical-btn" href="{{ route('exam-results.index',$patient) }}">Resultados de exames</a><a class="clinical-btn" href="{{ route('patients.show',$patient) }}">CRM do paciente</a><a class="clinical-btn" href="{{ route('medical-records.index') }}">Todos os prontuários</a></div>
    </header>

    <div class="clinical-security-note"><strong>Dados clínicos confidenciais.</strong><span>O acesso e as principais ações desta página são auditados e limitados à clínica atual.</span></div>

    @if($errors->any())<div class="clinical-error">{{ $errors->first() }}</div>@endif
    @if(auth()->user()->role === 'health_professional' && $professionals->isEmpty())<div class="clinical-error">O seu utilizador ainda não está vinculado a um cadastro em Profissionais de Saúde. Peça ao administrador para efetuar o vínculo antes de assinar registos clínicos.</div>@endif

    <div class="clinical-stats-grid record-stats">
        <article class="clinical-stat"><span class="clinical-stat-icon">≡</span><div><small>Evoluções</small><strong>{{ $stats['records'] }}</strong></div></article>
        <article class="clinical-stat {{ $stats['active_allergies'] ? 'clinical-stat-alert' : '' }}"><span class="clinical-stat-icon">!</span><div><small>Alergias ativas</small><strong>{{ $stats['active_allergies'] }}</strong></div></article>
        <article class="clinical-stat"><span class="clinical-stat-icon">◎</span><div><small>Diagnósticos ativos</small><strong>{{ $stats['active_diagnoses'] }}</strong></div></article>
        <article class="clinical-stat"><span class="clinical-stat-icon">Rx</span><div><small>Prescrições ativas</small><strong>{{ $stats['active_prescriptions'] }}</strong></div></article>
    </div>

    <nav class="clinical-anchor-nav" aria-label="Secções do prontuário">
        <a href="#evolucoes">Evoluções</a><a href="#sinais-vitais">Sinais vitais</a><a href="#alergias">Alergias</a><a href="#diagnosticos">Diagnósticos</a><a href="#prescricoes">Prescrições</a><a href="#anexos">Anexos</a>
    </nav>

    <div class="record-layout">
        <div class="record-main">
            <section class="clinical-panel" id="evolucoes">
                <div class="clinical-panel-head"><div><span class="eyebrow">HISTÓRICO CLÍNICO</span><h2>Evoluções</h2><p>Registos assinados permanecem no histórico e não possuem exclusão pela interface.</p></div></div>

                @if(auth()->user()->canAccess('clinical.write'))
                <details class="clinical-create-box" {{ old('record_type') || $errors->has('chief_complaint') ? 'open' : '' }}>
                    <summary>+ Registar nova evolução</summary>
                    <form method="post" action="{{ route('medical-records.store',$patient) }}" class="clinical-form">@csrf
                        <p class="field-help"><strong>* Campos obrigatórios.</strong> A consulta relacionada é opcional.</p>
                        <div class="clinical-form-grid">
                            <label><span>Tipo *</span><select name="record_type" required><option value="evolution" @selected(old('record_type','evolution') === 'evolution')>Evolução</option><option value="consultation" @selected(old('record_type') === 'consultation')>Consulta</option><option value="nursing" @selected(old('record_type') === 'nursing')>Enfermagem</option><option value="procedure" @selected(old('record_type') === 'procedure')>Procedimento</option></select></label>
                            <label><span>Profissional *</span><select name="health_professional_id" required><option value="">Selecionar</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string)old('health_professional_id') === (string)$professional->id)>{{ $professional->full_name }} · {{ $professional->typeLabel() }}</option>@endforeach</select></label>
                            <label><span>Data e hora *</span><input type="datetime-local" name="recorded_at" required value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}"></label>
                            <label><span>Consulta relacionada <small>(opcional)</small></span><select name="appointment_id"><option value="">Sem vínculo</option>@foreach($appointments as $appointment)<option value="{{ $appointment->id }}" @selected((string)old('appointment_id') === (string)$appointment->id)>{{ $appointment->scheduled_at->format('d/m/Y H:i') }} · {{ $appointment->type }}</option>@endforeach</select></label>
                            <label class="span-2"><span>Queixa principal *</span><input type="text" name="chief_complaint" required maxlength="500" placeholder="Motivo principal do atendimento" value="{{ old('chief_complaint') }}"></label>
                            <label class="span-2"><span>Subjetivo / relato *</span><textarea name="subjective" required rows="4" maxlength="10000" placeholder="Sintomas, contexto e relato do paciente">{{ old('subjective') }}</textarea></label>
                            <label class="span-2"><span>Objetivo / exame *</span><textarea name="objective" required rows="4" maxlength="10000" placeholder="Achados objetivos e observações do atendimento">{{ old('objective') }}</textarea></label>
                            <label class="span-2"><span>Avaliação *</span><textarea name="assessment" required rows="4" maxlength="10000" placeholder="Avaliação clínica">{{ old('assessment') }}</textarea></label>
                            <label class="span-2"><span>Plano / conduta *</span><textarea name="plan" required rows="4" maxlength="10000" placeholder="Orientações, plano e conduta">{{ old('plan') }}</textarea></label>
                        </div>
                        <div class="clinical-form-actions"><button class="clinical-btn clinical-btn-primary" type="submit">Registar e assinar evolução</button></div>
                    </form>
                </details>
                @endif

                <div class="clinical-record-list">
                    @forelse($patient->medicalRecords as $record)
                        <article class="clinical-record-item">
                            <div class="record-line"></div>
                            <div class="record-content">
                                <div class="record-meta"><span class="clinical-pill">{{ match($record->record_type){'consultation'=>'Consulta','nursing'=>'Enfermagem','procedure'=>'Procedimento',default=>'Evolução'} }}</span><time>{{ $record->recorded_at->format('d/m/Y H:i') }}</time></div>
                                <h3>{{ $record->chief_complaint ?: 'Registo clínico' }}</h3>
                                @if($record->subjective)<div class="clinical-note-block"><strong>Subjetivo</strong><p>{{ $record->subjective }}</p></div>@endif
                                @if($record->objective)<div class="clinical-note-block"><strong>Objetivo</strong><p>{{ $record->objective }}</p></div>@endif
                                @if($record->assessment)<div class="clinical-note-block"><strong>Avaliação</strong><p>{{ $record->assessment }}</p></div>@endif
                                @if($record->plan)<div class="clinical-note-block"><strong>Plano</strong><p>{{ $record->plan }}</p></div>@endif
                                <footer><span>{{ $record->professional_name_snapshot ?: $record->healthProfessional?->full_name ?: 'Profissional não identificado' }}</span><span>Assinado {{ $record->signed_at?->format('d/m/Y H:i') }}</span></footer>
                            </div>
                        </article>
                    @empty<div class="clinical-empty compact">Ainda não existem evoluções clínicas.</div>@endforelse
                </div>
            </section>

            <section class="clinical-panel" id="diagnosticos">
                <div class="clinical-panel-head"><div><span class="eyebrow">AVALIAÇÃO</span><h2>Diagnósticos</h2></div></div>
                @if(auth()->user()->canAccess('clinical.write'))
                <details class="clinical-create-box"><summary>+ Novo diagnóstico</summary><form method="post" action="{{ route('medical-records.diagnoses.store',$patient) }}" class="clinical-form">@csrf<div class="clinical-form-grid">
                    <label><span>Profissional *</span><select name="health_professional_id" required><option value="">Selecionar</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}">{{ $professional->full_name }}</option>@endforeach</select></label>
                    <label><span>Tipo *</span><select name="diagnosis_type"><option value="working">Hipótese</option><option value="confirmed">Confirmado</option><option value="history">Histórico</option></select></label>
                    <label><span>Código CID/ICD</span><input name="code" maxlength="40" placeholder="Opcional"></label><label><span>Data</span><input type="date" name="diagnosed_at" value="{{ today()->format('Y-m-d') }}"></label>
                    <label class="span-2"><span>Descrição *</span><input name="description" required maxlength="500"></label><label class="span-2"><span>Notas</span><textarea name="notes" rows="3" maxlength="3000"></textarea></label>
                </div><div class="clinical-form-actions"><button class="clinical-btn clinical-btn-primary">Guardar diagnóstico</button></div></form></details>
                @endif
                <div class="clinical-simple-list">@forelse($patient->diagnoses as $diagnosis)<article><div><span class="clinical-pill {{ $diagnosis->status !== 'active' ? 'muted' : '' }}">{{ ucfirst($diagnosis->status) }}</span><strong>{{ $diagnosis->code ? $diagnosis->code.' · ' : '' }}{{ $diagnosis->description }}</strong><small>{{ ucfirst($diagnosis->diagnosis_type) }} · {{ $diagnosis->professional_name_snapshot ?: $diagnosis->healthProfessional?->full_name ?: 'Profissional não identificado' }} · {{ $diagnosis->diagnosed_at?->format('d/m/Y') }}</small></div>@if(auth()->user()->canAccess('clinical.write') && $diagnosis->status === 'active')<form method="post" action="{{ route('medical-records.diagnoses.update',[$patient,$diagnosis]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="resolved"><button class="clinical-text-btn">Marcar resolvido</button></form>@endif</article>@empty<div class="clinical-empty compact">Sem diagnósticos registados.</div>@endforelse</div>
            </section>

            <section class="clinical-panel" id="prescricoes">
                <div class="clinical-panel-head"><div><span class="eyebrow">TERAPÊUTICA</span><h2>Prescrições</h2></div></div>
                @if(auth()->user()->canAccess('clinical.write'))
                <details class="clinical-create-box"><summary>+ Nova prescrição</summary><form method="post" action="{{ route('medical-records.prescriptions.store',$patient) }}" class="clinical-form">@csrf<div class="clinical-form-grid">
                    <label><span>Profissional *</span><select name="health_professional_id" required><option value="">Selecionar</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}">{{ $professional->full_name }}</option>@endforeach</select></label><label><span>Medicamento *</span><input name="medication" required maxlength="190"></label>
                    <label><span>Dose</span><input name="dosage" maxlength="120" placeholder="Ex.: 500 mg"></label><label><span>Via</span><input name="route" maxlength="80" placeholder="Ex.: oral"></label>
                    <label><span>Frequência</span><input name="frequency" maxlength="120" placeholder="Ex.: 8/8 h"></label><label><span>Duração</span><input name="duration" maxlength="120" placeholder="Ex.: 7 dias"></label>
                    <label><span>Início</span><input type="date" name="starts_at"></label><label><span>Fim</span><input type="date" name="ends_at"></label>
                    <label class="span-2"><span>Instruções</span><textarea name="instructions" rows="3" maxlength="5000"></textarea></label>
                </div><div class="clinical-form-actions"><button class="clinical-btn clinical-btn-primary">Guardar prescrição</button></div></form></details>
                @endif
                <div class="prescription-grid">@forelse($patient->prescriptions as $prescription)<article class="prescription-card {{ $prescription->status !== 'active' ? 'is-muted' : '' }}"><div class="prescription-head"><span>Rx</span><div><strong>{{ $prescription->medication }}</strong><small>{{ $prescription->dosage ?: 'Dose não informada' }}{{ $prescription->frequency ? ' · '.$prescription->frequency : '' }}</small></div><span class="clinical-pill">{{ ucfirst($prescription->status) }}</span></div><p>{{ $prescription->instructions ?: 'Sem instruções adicionais.' }}</p><footer>{{ $prescription->professional_name_snapshot ?: $prescription->healthProfessional?->full_name ?: 'Profissional não identificado' }}{{ $prescription->starts_at ? ' · início '.$prescription->starts_at->format('d/m/Y') : '' }}</footer>@if(auth()->user()->canAccess('clinical.write') && $prescription->status === 'active')<form method="post" action="{{ route('medical-records.prescriptions.update',[$patient,$prescription]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="clinical-text-btn">Concluir</button></form>@endif</article>@empty<div class="clinical-empty compact">Sem prescrições registadas.</div>@endforelse</div>
            </section>
        </div>

        <aside class="record-side">
            <section class="clinical-panel" id="sinais-vitais">
                <div class="clinical-panel-head"><div><span class="eyebrow">MONITORIZAÇÃO</span><h2>Sinais vitais</h2></div></div>
                @if($latestVitals)<div class="vitals-grid"><div><small>PA</small><strong>{{ $latestVitals->systolic_bp && $latestVitals->diastolic_bp ? $latestVitals->systolic_bp.'/'.$latestVitals->diastolic_bp : '—' }}</strong></div><div><small>FC</small><strong>{{ $latestVitals->heart_rate ?? '—' }}</strong></div><div><small>SpO₂</small><strong>{{ $latestVitals->oxygen_saturation ? $latestVitals->oxygen_saturation.'%' : '—' }}</strong></div><div><small>Temp.</small><strong>{{ $latestVitals->temperature_c ? $latestVitals->temperature_c.'°' : '—' }}</strong></div><div><small>Peso</small><strong>{{ $latestVitals->weight_kg ? $latestVitals->weight_kg.' kg' : '—' }}</strong></div><div><small>IMC</small><strong>{{ $latestVitals->bmi ?? '—' }}</strong></div></div><p class="vitals-time">Última medição: {{ $latestVitals->measured_at->format('d/m/Y H:i') }}</p>@else<div class="clinical-empty compact">Sem sinais vitais.</div>@endif
                @if(auth()->user()->canAccess('clinical.write'))<details class="clinical-create-box compact-box"><summary>+ Registar sinais vitais</summary><form method="post" action="{{ route('medical-records.vitals.store',$patient) }}" class="clinical-form">@csrf<div class="clinical-form-grid compact-grid"><label><span>Peso kg</span><input type="number" step="0.01" name="weight_kg"></label><label><span>Altura cm</span><input type="number" step="0.01" name="height_cm"></label><label><span>PA sistólica</span><input type="number" name="systolic_bp"></label><label><span>PA diastólica</span><input type="number" name="diastolic_bp"></label><label><span>FC bpm</span><input type="number" name="heart_rate"></label><label><span>FR rpm</span><input type="number" name="respiratory_rate"></label><label><span>SpO₂ %</span><input type="number" name="oxygen_saturation"></label><label><span>Temperatura °C</span><input type="number" step="0.1" name="temperature_c"></label><label><span>Glicemia mg/dL</span><input type="number" name="glucose_mg_dl"></label><label class="span-2"><span>Data/hora *</span><input type="datetime-local" name="measured_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></label><label class="span-2"><span>Notas</span><textarea name="notes" rows="2"></textarea></label></div><button class="clinical-btn clinical-btn-primary">Guardar medição</button></form></details>@endif
            </section>

            <section class="clinical-panel" id="alergias">
                <div class="clinical-panel-head"><div><span class="eyebrow">SEGURANÇA</span><h2>Alergias</h2></div></div>
                <div class="allergy-list">@forelse($patient->allergies as $allergy)<article class="allergy-item severity-{{ $allergy->severity }} {{ $allergy->status !== 'active' ? 'is-muted' : '' }}"><div><strong>{{ $allergy->substance }}</strong><small>{{ $allergy->reaction ?: 'Reação não informada' }} · {{ match($allergy->severity){'mild'=>'Leve','moderate'=>'Moderada','severe'=>'Grave',default=>'Não classificada'} }}</small></div><span class="clinical-pill">{{ $allergy->status === 'active' ? 'Ativa' : 'Inativa' }}</span>@if(auth()->user()->canAccess('clinical.write') && $allergy->status === 'active')<form method="post" action="{{ route('medical-records.allergies.update',[$patient,$allergy]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="inactive"><button class="clinical-text-btn">Inativar</button></form>@endif</article>@empty<div class="clinical-empty compact">Nenhuma alergia registada.</div>@endforelse</div>
                @if(auth()->user()->canAccess('clinical.write'))<details class="clinical-create-box compact-box"><summary>+ Registar alergia</summary><form method="post" action="{{ route('medical-records.allergies.store',$patient) }}" class="clinical-form">@csrf<div class="clinical-form-grid compact-grid"><label class="span-2"><span>Substância *</span><input name="substance" required></label><label class="span-2"><span>Reação</span><input name="reaction"></label><label><span>Gravidade</span><select name="severity"><option value="unknown">Não classificada</option><option value="mild">Leve</option><option value="moderate">Moderada</option><option value="severe">Grave</option></select></label><label><span>Identificada em</span><input type="date" name="identified_at"></label></div><button class="clinical-btn clinical-btn-primary">Guardar alergia</button></form></details>@endif
            </section>

            <section class="clinical-panel" id="anexos">
                <div class="clinical-panel-head"><div><span class="eyebrow">DOCUMENTOS PRIVADOS</span><h2>Anexos clínicos</h2></div></div>
                <div class="attachment-list">@forelse($patient->clinicalAttachments as $attachment)<a href="{{ route('medical-records.attachments.download',[$patient,$attachment]) }}" class="attachment-item"><span class="attachment-icon">↧</span><div><strong>{{ $attachment->original_name }}</strong><small>{{ $attachment->description ?: 'Documento clínico' }} · {{ $attachment->humanSize() }}</small></div></a>@empty<div class="clinical-empty compact">Sem anexos clínicos.</div>@endforelse</div>
                @if(auth()->user()->canAccess('clinical.attachments'))<details class="clinical-create-box compact-box"><summary>+ Adicionar anexo</summary><form method="post" enctype="multipart/form-data" action="{{ route('medical-records.attachments.store',$patient) }}" class="clinical-form">@csrf<label><span>Ficheiro *</span><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label><label><span>Descrição</span><input name="description" maxlength="500"></label><small class="field-help">PDF ou imagem · máximo 10 MB · armazenamento privado.</small><button class="clinical-btn clinical-btn-primary">Guardar anexo</button></form></details>@endif
            </section>
        </aside>
    </div>
</div>
@endsection
