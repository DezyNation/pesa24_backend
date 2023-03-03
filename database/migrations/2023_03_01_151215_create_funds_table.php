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
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->bigInteger('amount');
            $table->string('bank_name');
            $table->string('transacion_type');
            $table->string('transacion_id');
            $table->date('transaction_date');
            $table->string('transaction_receipt');
            $table->boolean('approved')->default('false');
            $table->string('remarks')->nullable();
            $table->string('admin_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};
