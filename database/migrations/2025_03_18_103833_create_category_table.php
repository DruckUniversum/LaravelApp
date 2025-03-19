<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Category', function (Blueprint $table) {
            $table->id('Category_ID');
            $table->string('Name', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Category');
    }

};
