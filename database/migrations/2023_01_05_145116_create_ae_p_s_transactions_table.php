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
        Schema::create('ae_p_s_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('shop')->nullable();
            $table->string('service_tax')->nullable();
            $table->string('total_fee')->nullable();
            $table->string('stan')->nullable();
            $table->string('tid')->nullable();
            $table->string('client_ref_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('merchant_code')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('customer_balance')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('auth_code')->nullable();
            $table->string('bank_ref_num')->nullable();
            $table->string('terminal_id')->nullable();
            $table->integer('amount')->nullable();
            $table->string('tx_status')->nullable();
            $table->string('trasaction_date')->nullable();
            $table->string('aadhar')->nullable();
            $table->integer('response_type_id')->nullable();
            $table->string('reason')->nullable();
            $table->string('comment')->nullable();
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
        Schema::dropIfExists('ae_p_s_transactions');
    }
};
