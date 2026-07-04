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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('client_id')->index()->constrained('clients');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_engineer_id')->nullable()->index()->constrained('users');
            $table->string('title');
            $table->text('description');
            $table->string('priority')->index(); // Cast to Priority enum
            $table->string('status')->default('open')->index(); // Cast to TicketStatus enum
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();

            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
