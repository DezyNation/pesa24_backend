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
        Schema::table('cms_commissions', function (Blueprint $table) {
            $table->foreignId('cms_biller_id')->after('provider')->nullable()->constrained('cms_billers')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_commissions', function (Blueprint $table) {
            //
        });
    }
};
