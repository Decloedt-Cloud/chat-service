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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Clé étrangère vers conversations
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            // Clé étrangère vers users (expéditeur)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Contenu du message (maintenant nullable pour images sans texte)
            $table->text('content')->nullable()->default(null);

            // Type de message: 'text', 'image', 'file', etc.
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video', 'system'])->default('text');

            // URL du fichier joint (si applicable)
            $table->string('file_url')->nullable();

            // Nom du fichier original
            $table->string('file_name')->nullable();

            // Taille du fichier en octets
            $table->unsignedBigInteger('file_size')->nullable();

            // Le message a-t-il été édité?
            $table->boolean('is_edited')->default(false);

            // Le message a-t-il été supprimé?
            $table->boolean('is_deleted')->default(false);

            // Timestamp de l'édition
            $table->timestamp('edited_at')->nullable();

            // ID de l'application cliente (pour multi-tenant)
            $table->string('app_id')->default('default');

            // Index pour optimisation
            $table->index(['conversation_id', 'created_at']);
            $table->index('user_id');
            $table->index('app_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
