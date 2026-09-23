<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('patient_accounts')) {
            Schema::create('patient_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('email',190)->unique();
                $table->string('password');
                $table->boolean('active')->default(true)->index();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('exam_results')) {
            Schema::create('exam_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title',190);
                $table->string('category',100)->index();
                $table->string('laboratory',190)->nullable();
                $table->date('exam_date')->index();
                $table->string('status',30)->default('draft')->index();
                $table->text('summary')->nullable();
                $table->string('file_path',500);
                $table->string('original_name',255);
                $table->string('mime_type',120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->timestamp('released_at')->nullable()->index();
                $table->timestamps();
                $table->index(['clinic_id','patient_id']);
            });
        }

        if (! Schema::hasTable('demo_requests')) {
            Schema::create('demo_requests', function (Blueprint $table) {
                $table->id();
                $table->string('name',150);
                $table->string('clinic_name',190);
                $table->string('email',190)->index();
                $table->string('phone',50)->nullable();
                $table->string('city',120)->nullable();
                $table->text('message')->nullable();
                $table->string('status',30)->default('new')->index();
                $table->string('source',50)->default('site');
                $table->string('ip_address',64)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('patient_accounts');
    }
};
