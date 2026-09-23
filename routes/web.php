<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\HealthProfessionalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientInteractionController;
use App\Http\Controllers\PatientTaskController;
use App\Http\Controllers\PatientTransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



// NOSH CRM Saude v0.7.0 - Site publico + Portal Paciente
Route::get('/', \App\Http\Controllers\PublicSiteController::class)->name('site.home');
Route::post('/solicitar-demonstracao', [\App\Http\Controllers\DemoRequestController::class, 'store'])->middleware('throttle:5,1')->name('site.demo.store');

Route::get('/paciente/login', [\App\Http\Controllers\PatientAuthController::class, 'create'])->name('patient.login');
Route::post('/paciente/login', [\App\Http\Controllers\PatientAuthController::class, 'store'])->name('patient.login.store');
Route::post('/paciente/logout', [\App\Http\Controllers\PatientAuthController::class, 'destroy'])->name('patient.logout');
Route::get('/paciente', [\App\Http\Controllers\PatientPortalController::class, 'dashboard'])->name('patient.dashboard');
Route::get('/paciente/consultas', [\App\Http\Controllers\PatientPortalController::class, 'appointments'])->name('patient.appointments');
// NOSH CRM Saude v0.8.0 - Autoatendimento de consultas
Route::get('/paciente/consultas/{appointment}/alterar', [\App\Http\Controllers\PatientPortalController::class, 'manageAppointment'])->whereNumber('appointment')->name('patient.appointments.manage');
Route::post('/paciente/consultas/{appointment}/reagendar', [\App\Http\Controllers\PatientPortalController::class, 'rescheduleAppointment'])->whereNumber('appointment')->name('patient.appointments.reschedule');
Route::post('/paciente/consultas/{appointment}/cancelar', [\App\Http\Controllers\PatientPortalController::class, 'cancelAppointment'])->whereNumber('appointment')->name('patient.appointments.cancel');
Route::get('/paciente/exames', [\App\Http\Controllers\PatientPortalController::class, 'exams'])->name('patient.exams');
Route::get('/paciente/seguranca', [\App\Http\Controllers\PatientPortalController::class, 'security'])->name('patient.security');
Route::put('/paciente/seguranca/password', [\App\Http\Controllers\PatientPortalController::class, 'updatePassword'])->name('patient.password.update');
Route::get('/paciente/exames/{exam}/download', [\App\Http\Controllers\ExamResultController::class, 'patientDownload'])->name('patient.exams.download');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications/summary', [NotificationController::class, 'summary'])->name('notifications.summary');

    Route::resource('patients', PatientController::class);
    Route::post('/patients/{patient}/interactions', [PatientInteractionController::class, 'store'])->name('patients.interactions.store');
    Route::delete('/patients/{patient}/interactions/{interaction}', [PatientInteractionController::class, 'destroy'])->name('patients.interactions.destroy');
    Route::post('/patients/{patient}/tasks', [PatientTaskController::class, 'store'])->name('patients.tasks.store');
    Route::patch('/patients/{patient}/tasks/{task}', [PatientTaskController::class, 'update'])->name('patients.tasks.update');
    Route::delete('/patients/{patient}/tasks/{task}', [PatientTaskController::class, 'destroy'])->name('patients.tasks.destroy');

    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    Route::resource('professionals', HealthProfessionalController::class)->except(['show']);

    Route::get('/patient-transfers', [PatientTransferController::class, 'index'])->name('transfers.index');
    Route::post('/patient-transfers', [PatientTransferController::class, 'store'])->name('transfers.store');
    Route::post('/patient-transfers/{transfer}/accept', [PatientTransferController::class, 'accept'])->name('transfers.accept');
    Route::post('/patient-transfers/{transfer}/reject', [PatientTransferController::class, 'reject'])->name('transfers.reject');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // NOSH CRM Saude v0.6.0 - Prontuario Clinico
    Route::get('/medical-records', [\App\Http\Controllers\MedicalRecordController::class, 'index'])->name('medical-records.index');
    Route::get('/patients/{patient}/medical-record', [\App\Http\Controllers\MedicalRecordController::class, 'show'])->name('medical-records.show');
    Route::post('/patients/{patient}/medical-records', [\App\Http\Controllers\MedicalRecordController::class, 'store'])->name('medical-records.store');
    Route::post('/patients/{patient}/vital-signs', [\App\Http\Controllers\VitalSignController::class, 'store'])->name('medical-records.vitals.store');
    Route::post('/patients/{patient}/allergies', [\App\Http\Controllers\PatientAllergyController::class, 'store'])->name('medical-records.allergies.store');
    Route::patch('/patients/{patient}/allergies/{allergy}', [\App\Http\Controllers\PatientAllergyController::class, 'update'])->name('medical-records.allergies.update');
    Route::post('/patients/{patient}/diagnoses', [\App\Http\Controllers\PatientDiagnosisController::class, 'store'])->name('medical-records.diagnoses.store');
    Route::patch('/patients/{patient}/diagnoses/{diagnosis}', [\App\Http\Controllers\PatientDiagnosisController::class, 'update'])->name('medical-records.diagnoses.update');
    Route::post('/patients/{patient}/prescriptions', [\App\Http\Controllers\PatientPrescriptionController::class, 'store'])->name('medical-records.prescriptions.store');
    Route::patch('/patients/{patient}/prescriptions/{prescription}', [\App\Http\Controllers\PatientPrescriptionController::class, 'update'])->name('medical-records.prescriptions.update');
    Route::post('/patients/{patient}/clinical-attachments', [\App\Http\Controllers\ClinicalAttachmentController::class, 'store'])->name('medical-records.attachments.store');
    Route::get('/patients/{patient}/clinical-attachments/{attachment}/download', [\App\Http\Controllers\ClinicalAttachmentController::class, 'download'])->name('medical-records.attachments.download');

    // NOSH CRM Saude v0.7.0 - Exames e acesso portal
    Route::get('/patients/{patient}/portal-access', [\App\Http\Controllers\PatientPortalAccessController::class, 'edit'])->name('patients.portal-access.edit');
    Route::put('/patients/{patient}/portal-access', [\App\Http\Controllers\PatientPortalAccessController::class, 'update'])->name('patients.portal-access.update');
    Route::get('/patients/{patient}/exam-results', [\App\Http\Controllers\ExamResultController::class, 'index'])->name('exam-results.index');
    Route::post('/patients/{patient}/exam-results', [\App\Http\Controllers\ExamResultController::class, 'store'])->name('exam-results.store');
    Route::patch('/patients/{patient}/exam-results/{exam}/release', [\App\Http\Controllers\ExamResultController::class, 'release'])->name('exam-results.release');
    Route::get('/patients/{patient}/exam-results/{exam}/download', [\App\Http\Controllers\ExamResultController::class, 'staffDownload'])->name('exam-results.download');
});
