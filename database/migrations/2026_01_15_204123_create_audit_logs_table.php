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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // create, update, delete, view, download
            $table->string('model'); // Modèle affecté
            $table->unsignedBigInteger('model_id')->nullable(); // ID du modèle affecté
            $table->json('ancienne_valeur')->nullable(); // État avant
            $table->json('nouvelle_valeur')->nullable(); // État après
            $table->string('ip_adresse')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->enum('statut_action', ['success', 'failed'])->default('success');
            $table->timestamps();
            $table->index('user_id');
            $table->index('model');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
