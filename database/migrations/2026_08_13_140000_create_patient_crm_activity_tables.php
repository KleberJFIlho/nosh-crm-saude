<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('note')->index();
            $table->string('direction', 20)->nullable();
            $table->string('subject', 190)->nullable();
            $table->text('content');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id', 'occurred_at'], 'patient_interactions_clinic_patient_at_idx');
        });

        Schema::create('patient_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('follow_up')->index();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'status', 'due_at'], 'patient_tasks_clinic_status_due_idx');
            $table->index(['clinic_id', 'patient_id', 'status'], 'patient_tasks_clinic_patient_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_tasks');
        Schema::dropIfExists('patient_interactions');
    }
};
