<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Order', function (Blueprint $table) {
            $table->id('Order_ID');
            $table->unsignedBigInteger('User_ID')->nullable();
            $table->unsignedBigInteger('Design_ID')->nullable();
            $table->float('Paid_Price')->nullable();
            $table->enum('Payment_Status', ['OPEN', 'PAID'])->nullable();
            $table->timestamp('Order_Date')->useCurrent();
            $table->string('Transaction_Hash', 255)->nullable();

            $table->foreign('User_ID')->references('User_ID')->on('User')->onDelete('set null');
            $table->foreign('Design_ID')->references('Design_ID')->on('Design')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Order');
    }

};
