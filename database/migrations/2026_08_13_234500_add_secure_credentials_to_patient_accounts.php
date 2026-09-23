<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('patient_accounts')) {
            return;
        }

        if (! Schema::hasColumn('patient_accounts', 'must_change_password')) {
            Schema::table('patient_accounts', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false)->after('active');
            });
        }
        if (! Schema::hasColumn('patient_accounts', 'temporary_password_expires_at')) {
            Schema::table('patient_accounts', function (Blueprint $table) {
                $table->timestamp('temporary_password_expires_at')->nullable()->after('must_change_password');
            });
        }
        if (! Schema::hasColumn('patient_accounts', 'temporary_password_used_at')) {
            Schema::table('patient_accounts', function (Blueprint $table) {
                $table->timestamp('temporary_password_used_at')->nullable()->after('temporary_password_expires_at');
            });
        }
        if (! Schema::hasColumn('patient_accounts', 'credentials_sent_at')) {
            Schema::table('patient_accounts', function (Blueprint $table) {
                $table->timestamp('credentials_sent_at')->nullable()->after('temporary_password_used_at');
            });
        }
        if (! Schema::hasColumn('patient_accounts', 'credentials_sent_via')) {
            Schema::table('patient_accounts', function (Blueprint $table) {
                $table->string('credentials_sent_via', 20)->nullable()->after('credentials_sent_at');
            });
        }
        if (! Schema::hasColumn('patient_accounts', 'password_changed_at')) {
            Schema::table('patient_accounts', function (Blueprint $table) {
                $table->timestamp('password_changed_at')->nullable()->after('credentials_sent_via');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('patient_accounts')) {
            return;
        }

        foreach ([
            'password_changed_at',
            'credentials_sent_via',
            'credentials_sent_at',
            'temporary_password_used_at',
            'temporary_password_expires_at',
            'must_change_password',
        ] as $column) {
            if (Schema::hasColumn('patient_accounts', $column)) {
                Schema::table('patient_accounts', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
