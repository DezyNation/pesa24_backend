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
        Schema::create('commission_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('commission_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->integer('from');
            $table->integer('to');
            $table->integer('commission');
            $table->boolean('is_flat');
            $table->boolean('is_surcharge')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_package');
    }
};
