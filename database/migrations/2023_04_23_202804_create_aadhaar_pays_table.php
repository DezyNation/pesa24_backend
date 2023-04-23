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
        Schema::create('aadhaar_pays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('from', 19, 4)->nullable();
            $table->decimal('to', 19, 4)->nullable();
            $table->decimal('fixed_charge', 19, 4)->nullable();
            $table->boolean('is_flat')->nullable()->default(true);
            $table->decimal('gst', 19, 4)->nullable()->default(0);
            $table->decimal('retailer_commission', 19, 4)->nullable();
            $table->decimal('distributor_commission', 19, 4)->nullable();
            $table->decimal('super_distributor_commission', 19, 4)->nullable();
            $table->decimal('admin_commission', 19, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aadhaar_pays');
    }
};
