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
        Schema::create('lic_commissions', function (Blueprint $table) {
        $table->id();
        $table->decimal('from', 19, 4);
        $table->decimal('to', 19, 4);
        $table->foreignId('package_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
        $table->decimal('fixed_charge');
        $table->boolean('is_flat')->default(0);
        $table->decimal('gst');
        $table->decimal('retailer_commission');
        $table->decimal('distributor_commission');
        $table->decimal('super_distributor_commission');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
