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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('transaction_for');
            $table->integer('credit_amount');
            $table->integer('debit_amount');
            $table->integer('opening_balance');
            $table->integer('balance_left');
            $table->integer('commission');
            $table->integer('distributor_commission');
            $table->integer('super_distributor_commission');
            $table->integer('admin_commission');
            $table->string('transaction_id');
            $table->boolean('is_flat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
