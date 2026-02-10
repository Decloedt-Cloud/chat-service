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
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();

            // Clé étrangère vers conversations
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            // Clé étrangère vers users
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Rôle du participant: 'owner', 'admin', 'member'
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');

            // Timestamp du dernier message lu
            $table->timestamp('last_read_at')->nullable();

            // Nombre de messages non lus
            $table->unsignedInteger('unread_count')->default(0);

            // Timestamp de quand l'utilisateur a rejoint la conversation
            $table->timestamp('joined_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Empêcher les doublons (même user dans même conversation)
            $table->unique(['conversation_id', 'user_id'], 'unique_conversation_user');

            // Index pour les recherches fréquentes
            $table->index(['user_id', 'joined_at']);
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
