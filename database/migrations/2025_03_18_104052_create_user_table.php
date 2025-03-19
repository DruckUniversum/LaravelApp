<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('User', function (Blueprint $table) {
            $table->id('User_ID');
            $table->string('First_Name', 50)->nullable();
            $table->string('Last_Name', 50)->nullable();
            $table->string('Street', 100)->nullable();
            $table->string('House_Number', 10)->nullable();
            $table->string('Country', 50)->nullable();
            $table->string('City', 50)->nullable();
            $table->string('Postal_Code', 10)->nullable();
            $table->string('Email', 100)->unique();
            $table->string('Password_Hash', 255)->nullable();
            $table->boolean('AGB_Akzeptiert')->nullable();
            $table->date('Last_Login')->nullable();
            $table->unsignedInteger('Failed_Logins')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('User');
    }

};
