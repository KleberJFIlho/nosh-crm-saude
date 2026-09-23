<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(): View
    {
        ClinicAccess::allow('leads.view');
        $columns = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];
        $leads = Lead::orderByDesc('score')->get()->groupBy('status');
        return view('leads.index', compact('columns', 'leads'));
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicAccess::allow('leads.create');
        $lead = Lead::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'interest' => ['required', 'string', 'max:150'],
            'source' => ['nullable', 'string', 'max:100'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'next_contact_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]) + ['status' => 'new', 'clinic_id' => ClinicAccess::clinicId()]);
        ClinicAccess::audit('lead.created', Lead::class, $lead->id);
        return back()->with('success', 'Novo lead adicionado ao funil desta clínica.');
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        ClinicAccess::allow('leads.edit');
        $lead->update($request->validate(['status' => ['required', 'in:new,contacted,qualified,proposal,won,lost']]));
        ClinicAccess::audit('lead.status_updated', Lead::class, $lead->id, ['status' => $lead->status]);
        return back()->with('success', 'Etapa do lead atualizada.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        ClinicAccess::allow('leads.delete');
        ClinicAccess::audit('lead.deleted', Lead::class, $lead->id);
        $lead->delete();
        return back()->with('success', 'Lead removido.');
    }
}
