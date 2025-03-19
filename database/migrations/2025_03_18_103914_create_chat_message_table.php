<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Chat_Message', function (Blueprint $table) {
            $table->id('Message_ID');
            $table->unsignedBigInteger('Chat_ID')->nullable();
            $table->unsignedBigInteger('User_ID')->nullable();
            $table->timestamp('Timestamp')->useCurrent();
            $table->text('Content')->nullable();

            $table->foreign('Chat_ID')->references('Chat_ID')->on('Chat')->onDelete('cascade');
            $table->foreign('User_ID')->references('User_ID')->on('User')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Chat_Message');
    }

};
