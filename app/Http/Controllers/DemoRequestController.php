<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DemoRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'clinic_name' => ['required','string','max:190'],
            'email' => ['required','email','max:190'],
            'phone' => ['nullable','string','max:50'],
            'city' => ['nullable','string','max:120'],
            'message' => ['nullable','string','max:2000'],
        ]);

        DemoRequest::create($data + ['status' => 'new', 'source' => 'site', 'ip_address' => $request->ip()]);
        return back()->with('demo_success', 'Pedido recebido! A equipa NOSH CRM Saúde entrará em contacto.');
    }
}
