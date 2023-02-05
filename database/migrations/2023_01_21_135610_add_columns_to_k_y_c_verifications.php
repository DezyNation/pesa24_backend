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
        Schema::table('k_y_c_verifications', function (Blueprint $table) {
            $table->boolean('eko')->default(0)->after('bank');
            $table->boolean('paysprint')->default(0)->after('eko');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('k__y_c_verifications', function (Blueprint $table) {
            //
        });
    }
};
