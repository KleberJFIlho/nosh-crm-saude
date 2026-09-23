<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('appointments', 'patient_rescheduled_at')) {
                $table->timestamp('patient_rescheduled_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('appointments', 'patient_reschedule_count')) {
                $table->unsignedInteger('patient_reschedule_count')->default(0)->after('patient_rescheduled_at');
            }
            if (! Schema::hasColumn('appointments', 'patient_cancelled_at')) {
                $table->timestamp('patient_cancelled_at')->nullable()->after('patient_reschedule_count');
            }
            if (! Schema::hasColumn('appointments', 'patient_cancellation_reason')) {
                $table->string('patient_cancellation_reason', 500)->nullable()->after('patient_cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $columns = [
                'patient_rescheduled_at',
                'patient_reschedule_count',
                'patient_cancelled_at',
                'patient_cancellation_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
