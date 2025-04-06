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
        Schema::create('Printer', function (Blueprint $table) {
            $table->id('Printer_ID');
            $table->unsignedBigInteger('User_ID');
            $table->string('API_Key');
            $table->foreign('User_ID')->references('User_ID')->on('User');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer');
    }
};
