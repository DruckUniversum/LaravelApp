<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Chat', function (Blueprint $table) {
            $table->id('Chat_ID');
            $table->unsignedBigInteger('Tender_ID')->nullable();

            $table->foreign('Tender_ID')->references('Tender_ID')->on('Tender')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Chat');
    }

};
