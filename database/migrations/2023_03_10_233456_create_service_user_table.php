<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('pesa24_active')->default(0);
            $table->boolean('eko_active')->default(0);
            $table->boolean('paysprint_active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_user');
    }
};
