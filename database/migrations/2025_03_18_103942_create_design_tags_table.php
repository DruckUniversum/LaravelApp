<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Design_Tags', function (Blueprint $table) {
            $table->unsignedBigInteger('Design_ID');
            $table->unsignedBigInteger('Tag_ID');

            $table->primary(['Tag_ID', 'Design_ID']);

            $table->foreign('Design_ID')->references('Design_ID')->on('Design')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('Tag_ID')->references('Tag_ID')->on('Tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Design_Tags');
    }

};
