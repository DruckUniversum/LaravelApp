<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Tender', function (Blueprint $table) {
            $table->id('Tender_ID');
            $table->enum('Status', ['OPEN', 'ACCEPTED', 'PAID', 'PROCESSING', 'SHIPPING', 'CLOSED'])->nullable();
            $table->float('Bid')->nullable();
            $table->unsignedInteger('Infill')->nullable();
            $table->enum('Filament', ['PLA', 'ABS', 'Carbon'])->nullable();
            $table->text('Description')->nullable();
            $table->unsignedBigInteger('Tenderer_ID')->nullable();
            $table->unsignedBigInteger('Provider_ID')->nullable();
            $table->unsignedBigInteger('Order_ID')->nullable();
            $table->timestamp('Tender_Date')->useCurrent();
            $table->string('Shipping_Provider', 50)->nullable();
            $table->string('Shipping_Number', 50)->nullable();
            $table->string('Transaction_Hash', 255)->nullable();

            $table->foreign('Tenderer_ID')->references('User_ID')->on('User')->onDelete('set null');
            $table->foreign('Provider_ID')->references('User_ID')->on('User')->onDelete('set null');
            $table->foreign('Order_ID')->references('Order_ID')->on('Order')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Tender');
    }

};
