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
        Schema::create('b_b_p_s', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('type');
            $table->string('operator');
            $table->integer('eko_id');
            $table->integer('paysprint_id');
            $table->boolean('is_surcharge');
            $table->integer('retailer_commission');
            $table->integer('distributor_commission');
            $table->integer('super_distributor_commission');
            $table->integer('admin_commission');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_b_p_s');
    }
};
