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
            $table->unsignedBigInteger('User_ID')->nullable();
            $table->text('Content')->nullable();
            $table->timestamp('Timestamp')->nullable();

            $table->foreign('Tender_ID')->references('Tender_ID')->on('Tender')->onDelete('cascade');
            $table->foreign('User_ID')->references('User_ID')->on('User')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Chat');
    }

};
