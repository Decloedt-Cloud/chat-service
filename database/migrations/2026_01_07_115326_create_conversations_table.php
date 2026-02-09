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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // Type de conversation: 'direct' (entre 2 utilisateurs) ou 'group'
            $table->enum('type', ['direct', 'group'])->default('direct');

            // Nom du groupe (null pour conversations directes)
            $table->string('name')->nullable();

            // ID de l'utilisateur qui a créé la conversation
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Avatar du groupe (optionnel)
            $table->string('avatar')->nullable();

            // Description du groupe (optionnel)
            $table->text('description')->nullable();

            // Statut: 'active' ou 'archived'
            $table->enum('status', ['active', 'archived'])->default('active');

            // ID de l'application cliente (pour multi-tenant)
            $table->string('app_id')->default('default');

            // Timestamps avec soft deletes
            $table->softDeletes();
            $table->timestamps();

            // Index pour les recherches fréquentes
            $table->index(['type', 'status']);
            $table->index('app_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
