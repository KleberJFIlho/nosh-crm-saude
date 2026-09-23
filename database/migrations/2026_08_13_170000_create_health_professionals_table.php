<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_professionals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('professional_type', 30)->index();
            $table->string('full_name', 160);
            $table->string('registration_number', 80)->nullable();
            $table->string('specialty', 120)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'professional_type', 'active'], 'health_prof_clinic_type_active_idx');
            $table->unique(['clinic_id', 'registration_number'], 'health_prof_clinic_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_professionals');
    }
};
