<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Wallet', function (Blueprint $table) {
            $table->string('Address', 255)->primary();
            $table->string('Coin_Symbol', 10);
            $table->text('Pub_Key');
            $table->text('Priv_Key');
            $table->unsignedBigInteger('User_ID');

            $table->foreign('User_ID')->references('User_ID')->on('User')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Wallet');
    }

};
