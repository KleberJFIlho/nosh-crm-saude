<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('health_professional_id')->nullable()->constrained('health_professionals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('professional_name_snapshot', 160)->nullable();
            $table->string('author_name_snapshot', 160)->nullable();
            $table->string('record_type', 40)->default('evolution')->index();
            $table->string('chief_complaint', 500)->nullable();
            $table->longText('subjective')->nullable();
            $table->longText('objective')->nullable();
            $table->longText('assessment')->nullable();
            $table->longText('plan')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id', 'recorded_at'], 'medrec_clinic_patient_recorded_idx');
        });

        Schema::create('patient_vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name_snapshot', 160)->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->unsignedSmallInteger('systolic_bp')->nullable();
            $table->unsignedSmallInteger('diastolic_bp')->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->unsignedSmallInteger('oxygen_saturation')->nullable();
            $table->unsignedSmallInteger('glucose_mg_dl')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('measured_at')->index();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id', 'measured_at'], 'vitals_clinic_patient_measured_idx');
        });

        Schema::create('patient_allergies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name_snapshot', 160)->nullable();
            $table->string('substance', 190);
            $table->string('reaction', 500)->nullable();
            $table->string('severity', 20)->default('unknown')->index();
            $table->string('status', 20)->default('active')->index();
            $table->date('identified_at')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id', 'status'], 'allergy_clinic_patient_status_idx');
        });

        Schema::create('patient_diagnoses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('health_professional_id')->nullable()->constrained('health_professionals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('professional_name_snapshot', 160)->nullable();
            $table->string('author_name_snapshot', 160)->nullable();
            $table->string('code', 40)->nullable();
            $table->string('description', 500);
            $table->string('diagnosis_type', 30)->default('working')->index();
            $table->string('status', 20)->default('active')->index();
            $table->date('diagnosed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id', 'status'], 'diagnosis_clinic_patient_status_idx');
        });

        Schema::create('patient_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('health_professional_id')->nullable()->constrained('health_professionals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('professional_name_snapshot', 160)->nullable();
            $table->string('author_name_snapshot', 160)->nullable();
            $table->string('medication', 190);
            $table->string('dosage', 120)->nullable();
            $table->string('route', 80)->nullable();
            $table->string('frequency', 120)->nullable();
            $table->string('duration', 120)->nullable();
            $table->text('instructions')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id', 'status'], 'prescription_clinic_patient_status_idx');
        });

        Schema::create('clinical_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uploader_name_snapshot', 160)->nullable();
            $table->string('original_name', 255);
            $table->string('path', 700);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'patient_id'], 'clinical_attachment_clinic_patient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_attachments');
        Schema::dropIfExists('patient_prescriptions');
        Schema::dropIfExists('patient_diagnoses');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_vital_signs');
        Schema::dropIfExists('medical_records');
    }
};
