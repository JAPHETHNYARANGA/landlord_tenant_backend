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
        Schema::table('maintenance_tickets', function (Blueprint $table) {
             // Add the new column
        $table->unsignedBigInteger('closed_by_service_provider_id')->nullable();  // Allows null if not assigned

        // Add the foreign key constraint
        $table->foreign('closed_by_service_provider_id')
              ->references('id')
              ->on('service_providers')
              ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            //
             // Drop the foreign key constraint and the column
        $table->dropForeign(['closed_by_service_provider_id']);
        $table->dropColumn('closed_by_service_provider_id');
        });
    }
};
