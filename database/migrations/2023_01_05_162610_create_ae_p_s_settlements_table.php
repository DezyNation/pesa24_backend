<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ae_p_s_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ae_p_s_settlement_account_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('ae_p_s_transaction_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('tx_status')->nullable();
            $table->string('amount')->nullable();
            $table->string('balance')->nullable();
            $table->string('txstatus_desc')->nullable();
            $table->string('totalfee')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('account')->nullable();
            $table->string('tid')->nullable();
            $table->integer('response_type_id')->nullable();
            $table->integer('client_ref_id')->nullable();
            $table->string('message')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ae_p_s_settlements');
    }
};
