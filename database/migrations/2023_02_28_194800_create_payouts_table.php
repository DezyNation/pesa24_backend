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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('payout_id');
            $table->string('entity');
            $table->string('fund_account_id');
            $table->bigInteger('amount');
            $table->string('currency');
            $table->integer('fees');
            $table->integer('tax');
            $table->integer('status');
            $table->string('utr')->nullable();
            $table->string('mode');
            $table->string('purpose')->nullable();
            $table->string('reference_id');
            $table->string('narration');
            $table->string('batch_id')->nullable();
            $table->string('description');
            $table->string('source');
            $table->string('reason');
            $table->bigInteger('added_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
