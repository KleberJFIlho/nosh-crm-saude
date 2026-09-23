<?php

namespace App\Http\Controllers;

use App\Models\HealthProfessional;
use App\Models\User;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HealthProfessionalController extends Controller
{
    public function index(Request $request): View
    {
        ClinicAccess::allow('professionals.view');

        $query = HealthProfessional::query()->with('user')->latest('full_name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('specialty', 'like', '%'.$search.'%')
                    ->orWhere('registration_number', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if (in_array($request->query('type'), ['doctor', 'nurse', 'assistant'], true)) {
            $query->where('professional_type', $request->query('type'));
        }

        if ($request->query('status') === 'active') {
            $query->where('active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('active', false);
        }

        $professionals = $query->paginate(12)->withQueryString();
        $stats = [
            'total' => HealthProfessional::query()->count(),
            'doctors' => HealthProfessional::query()->where('professional_type', 'doctor')->where('active', true)->count(),
            'nurses' => HealthProfessional::query()->where('professional_type', 'nurse')->where('active', true)->count(),
            'assistants' => HealthProfessional::query()->where('professional_type', 'assistant')->where('active', true)->count(),
        ];

        return view('professionals.index', compact('professionals', 'stats'));
    }

    public function create(): View
    {
        ClinicAccess::allow('professionals.create');

        return view('professionals.form', [
            'professional' => new HealthProfessional(['active' => true]),
            'users' => $this->clinicUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicAccess::allow('professionals.create');
        $clinicId = ClinicAccess::clinicId();
        $data = $this->validated($request, $clinicId);

        $data['clinic_id'] = $clinicId;
        $data['active'] = $request->boolean('active');
        $data['photo_path'] = $this->storePhoto($request, $clinicId);

        $professional = HealthProfessional::create($data);
        ClinicAccess::audit('professional.created', HealthProfessional::class, $professional->id, [
            'type' => $professional->professional_type,
            'has_photo' => (bool) $professional->photo_path,
        ]);

        return redirect()->route('professionals.index')->with('success', 'Profissional cadastrado com sucesso.');
    }

    public function edit(HealthProfessional $professional): View
    {
        ClinicAccess::allow('professionals.edit');

        return view('professionals.form', [
            'professional' => $professional,
            'users' => $this->clinicUsers(),
        ]);
    }

    public function update(Request $request, HealthProfessional $professional): RedirectResponse
    {
        ClinicAccess::allow('professionals.edit');
        $clinicId = ClinicAccess::clinicId();
        abort_unless((int) $professional->clinic_id === $clinicId, 404);

        $data = $this->validated($request, $clinicId, $professional);
        $data['active'] = $request->boolean('active');

        if ($request->boolean('remove_photo') && $professional->photo_path) {
            Storage::disk('public')->delete($professional->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($professional->photo_path) {
                Storage::disk('public')->delete($professional->photo_path);
            }
            $data['photo_path'] = $this->storePhoto($request, $clinicId);
        }

        $professional->update($data);
        ClinicAccess::audit('professional.updated', HealthProfessional::class, $professional->id, [
            'type' => $professional->professional_type,
            'has_photo' => (bool) $professional->photo_path,
        ]);

        return redirect()->route('professionals.index')->with('success', 'Cadastro do profissional atualizado.');
    }

    public function destroy(HealthProfessional $professional): RedirectResponse
    {
        ClinicAccess::allow('professionals.delete');
        abort_unless((int) $professional->clinic_id === ClinicAccess::clinicId(), 404);

        if ($professional->photo_path) {
            Storage::disk('public')->delete($professional->photo_path);
        }

        ClinicAccess::audit('professional.deleted', HealthProfessional::class, $professional->id, [
            'name' => $professional->full_name,
            'type' => $professional->professional_type,
        ]);
        $professional->delete();

        return redirect()->route('professionals.index')->with('success', 'Profissional removido.');
    }

    private function validated(Request $request, int $clinicId, ?HealthProfessional $professional = null): array
    {
        $registrationRule = Rule::unique('health_professionals', 'registration_number')
            ->where(fn ($query) => $query->where('clinic_id', $clinicId));
        if ($professional) {
            $registrationRule->ignore($professional->id);
        }

        return $request->validate([
            'professional_type' => ['required', Rule::in(['doctor', 'nurse', 'assistant'])],
            'full_name' => ['required', 'string', 'max:160'],
            'registration_number' => ['nullable', 'string', 'max:80', $registrationRule],
            'specialty' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('clinic_id', $clinicId)->where('active', true)),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);
    }

    private function storePhoto(Request $request, int $clinicId): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('professionals/clinic-'.$clinicId, 'public');
    }

    private function clinicUsers()
    {
        $clinicId = ClinicAccess::clinicId();
        return User::query()
            ->where('clinic_id', $clinicId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }
}
