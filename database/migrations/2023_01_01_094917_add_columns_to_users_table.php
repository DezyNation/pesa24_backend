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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('phone_number')->after('email');
            $table->string('pan_number')->after('phone_number');
            $table->string('pan_number')->after('aadhaar')->nullable();
            $table->string('referal_code')->after('pan_number')->nullable();
            $table->boolean('kyc')->default(0)->after('password');
            $table->boolean('onboard_fee')->default(0)->nullable()->after('pan_number');
            $table->string('user_code')->nullable()->after('phone_numer');
            $table->string('portal')->nullable()->after('user_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
