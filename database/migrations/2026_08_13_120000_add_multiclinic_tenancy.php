<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('city', 100)->index();
            $table->string('district', 100)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 190)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('clinics')->insert([
            ['name' => 'NOSH Saúde Fafe', 'code' => 'FAFE', 'city' => 'Fafe', 'district' => 'Braga', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'NOSH Saúde Braga', 'code' => 'BRAGA', 'city' => 'Braga', 'district' => 'Braga', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'NOSH Saúde Guimarães', 'code' => 'GUIMARAES', 'city' => 'Guimarães', 'district' => 'Braga', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach (['users', 'patients', 'leads', 'appointments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('clinic_id')->nullable()->after('id')->constrained('clinics')->restrictOnDelete();
                $table->index(['clinic_id', 'created_at'], $tableName.'_clinic_created_idx');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 40)->default('receptionist')->after('clinic_id')->index();
            $table->boolean('active')->default(true)->after('role')->index();
        });

        $fafeId = DB::table('clinics')->where('code', 'FAFE')->value('id');
        foreach (['patients', 'leads', 'appointments'] as $tableName) {
            DB::table($tableName)->whereNull('clinic_id')->update(['clinic_id' => $fafeId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'active']);
        });
        foreach (['appointments', 'leads', 'patients', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign([$tableName === 'users' ? 'clinic_id' : 'clinic_id']);
                $table->dropIndex($tableName.'_clinic_created_idx');
                $table->dropColumn('clinic_id');
            });
        }
        Schema::dropIfExists('clinics');
    }
};
