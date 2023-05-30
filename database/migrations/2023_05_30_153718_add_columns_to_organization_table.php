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
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('firm_address');
            $table->string('phone_number')->unique();
            $table->string('email')->unique();
            $table->string('coi')->unique();
            $table->string('coi_attachment');
            $table->string('gst')->unique();
            $table->string('gst_attachment');
            $table->string('mou')->unique();
            $table->string('mou_attachment');
            $table->string('aoa')->unique();
            $table->string('aoa_attachment');
            $table->string('firm_pan')->unique();
            $table->string('firm_pan_attachment');
            $table->string('signatuary_pan');
            $table->string('signatuary_pan_attachment');
            $table->string('signatury_aadhaar')->unique();
            $table->string('signatury_aadhaar_attachment');
            $table->string('signatury_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization', function (Blueprint $table) {
            //
        });
    }
};
