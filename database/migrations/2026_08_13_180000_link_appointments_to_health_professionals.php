<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('appointments', 'health_professional_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreignId('health_professional_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained('health_professionals')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'health_professional_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('health_professional_id');
            });
        }
    }
};
