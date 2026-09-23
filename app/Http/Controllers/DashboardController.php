<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Patient;
use App\Support\ClinicAccess;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        ClinicAccess::allow('dashboard.view');
        $today = now()->startOfDay();

        return view('dashboard.index', [
            'metrics' => [
                'activePatients' => Patient::where('status', 'active')->count(),
                'openLeads' => Lead::whereNotIn('status', ['won', 'lost'])->count(),
                'todayAppointments' => Appointment::whereBetween('scheduled_at', [$today, $today->copy()->endOfDay()])->count(),
                'pendingFollowUps' => Patient::whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay())->count(),
            ],
            'appointments' => Appointment::with('patient')->where('scheduled_at', '>=', now()->startOfDay())->orderBy('scheduled_at')->limit(6)->get(),
            'leads' => Lead::orderByDesc('score')->orderBy('next_contact_at')->limit(5)->get(),
        ]);
    }
}
