<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('from_clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('to_clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('patient_consent_at');
            $table->text('reason');
            $table->text('response_notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['from_clinic_id', 'status']);
            $table->index(['to_clinic_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('patient_transfers'); }
};
