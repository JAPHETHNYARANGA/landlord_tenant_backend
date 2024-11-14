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
        Schema::create('provider_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('maintenance_tickets')->onDelete('cascade'); // Reference to the ticket
            $table->foreignId('user_id')->constrained('tenants')->onDelete('cascade'); // Reference to the user giving the rating
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade'); // Reference to the service provider
            $table->integer('rating')->unsigned()->default(1); // Rating (1-5 stars)
            $table->text('comment')->nullable(); // User comment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_ratings');
    }
};
