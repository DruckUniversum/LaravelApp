<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('User_Roles', function (Blueprint $table) {
            $table->unsignedBigInteger('User_ID');
            $table->enum('Role', ['User', 'Designer', 'Provider']);

            $table->primary(['User_ID', 'Role']);
            $table->foreign('User_ID')->references('User_ID')->on('User')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('User_Roles');
    }

};
