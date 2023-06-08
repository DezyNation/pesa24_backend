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
        Schema::create('axis_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->integer('fixed_charge');
            $table->integer('is_flat');
            $table->integer('gst');
            $table->boolean('parents')->default(0);
            $table->integer('retailer_commission');
            $table->integer('distributor_commission');
            $table->integer('super_distributor_commission');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('axis_commissions');
    }
};
