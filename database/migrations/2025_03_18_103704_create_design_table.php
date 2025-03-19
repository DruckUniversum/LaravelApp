<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Design', function (Blueprint $table) {
            $table->id('Design_ID');
            $table->string('Name', 50)->nullable();
            $table->string('STL_File', 255)->nullable();
            $table->float('Price')->nullable();
            $table->text('Description')->nullable();
            $table->string('Cover_Picture_File', 255)->nullable();
            $table->string('License', 100)->nullable();
            $table->unsignedBigInteger('Category_ID')->nullable();
            $table->unsignedBigInteger('Designer_ID')->nullable();

            $table->foreign('Category_ID')->references('Category_ID')->on('Category')->onDelete('set null');
            $table->foreign('Designer_ID')->references('User_ID')->on('User')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Design');
    }

};
