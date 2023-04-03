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
        Schema::table('a_e_p_s', function (Blueprint $table) {
            $table->boolean('parents')->after('package_id')->default(true);
        });
        Schema::table('d_m_t_s', function (Blueprint $table) {
            $table->boolean('parents')->after('package_id')->default(true);
        });
        Schema::table('payoutcommissions', function (Blueprint $table) {
            $table->boolean('parents')->after('package_id')->default(true);
        });
        Schema::table('fund_settlements', function (Blueprint $table) {
            $table->boolean('parents')->after('package_id')->default(true);
        });
        Schema::table('recharges', function (Blueprint $table) {
            $table->boolean('parents')->after('package_id')->default(true);
        });
        Schema::table('ae_p_s_mini_statements', function (Blueprint $table) {
            $table->boolean('parents')->after('package_id')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comm_tbls', function (Blueprint $table) {
            //
        });
    }
};
