<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('interest', 150);
            $table->string('source', 100)->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->unsignedTinyInteger('score')->default(50);
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->timestamp('next_contact_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('leads'); }
};
