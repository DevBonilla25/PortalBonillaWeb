<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_assignment_assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ticket_assignment_id', 'assistant_user_id'], 'ticket_assignment_assistants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_assignment_assistants');
    }
};
