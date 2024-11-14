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
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('issue');
            $table->text('description');
            $table->enum('status', ['open', 'closed'])->default('open'); // Status field
            $table->enum('priority', ['low', 'high'])->default('low'); // Low or High Priority
            $table->enum('ticket_status', ['pending', 'in_progress', 'complete'])->default('pending'); // Pending, In Progress, Complete
            $table->string('image')->nullable(); // Nullable image field
            $table->text('technician_notes')->nullable(); // Technician notes field, nullable
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
