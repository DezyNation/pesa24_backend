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
        Schema::create('payoutcommssions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->integer('from');
            $table->integer('to');
            $table->integer('fixed_charge');
            $table->integer('is_flat');
            $table->integer('gst');
            $table->integer('retailer_commssion');
            $table->integer('distributor_commssion');
            $table->integer('super_distributor_commssion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payoutcommssions');
    }
};
