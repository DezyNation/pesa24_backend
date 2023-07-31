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
        Schema::create('srk_money', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('request_id')->nullable();
            $table->string('resp_code')->nullable();
            $table->string('utr')->nullable();
            $table->string('resp_desc')->nullable();
            $table->string('benename')->nullable();
            $table->string('opid')->nullable();
            $table->string('txnid')->nullable();
            $table->decimal('txn_amt', 16, 4)->nullable();
            $table->string('txn_status')->nullable();
            $table->string('txn_desc')->nullable();
            $table->timestamp('date')->nullable();
            $table->string('date_text')->nullable();
            $table->integer('commission')->nullable();
            $table->integer('tds')->nullable();
            $table->integer('total_charge')->nullable();
            $table->integer('total_ccf')->nullable();
            $table->integer('tras_amt')->nullable();
            $table->integer('charged_amt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('srk_money');
    }
};
