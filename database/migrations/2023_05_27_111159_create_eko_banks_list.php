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
        Schema::create('eko_banks_list', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('bank_id');
            $table->string('short_code');
            $table->string('imps_status');
            $table->string('neft_status');
            $table->string('is_verification_available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eko_banks_list');
    }
};
