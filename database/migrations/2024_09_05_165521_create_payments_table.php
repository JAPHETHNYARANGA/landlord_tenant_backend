<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->decimal('amount', 10, 2); // Store amount as a decimal
            $table->enum('status', ['pending', 'active', 'failed']);
            $table->timestamp('payment_date');
            $table->string('payment_method');
            $table->timestamp('next_payment_date')->nullable();
            $table->string('MerchantRequestID')->nullable();
            $table->timestamps();

            // Add foreign key constraints if needed
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
