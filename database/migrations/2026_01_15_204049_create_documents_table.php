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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers')->onDelete('cascade');
            $table->string('type'); // Exemple: CNI, Diplome, Bulletin
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type')->nullable();
            $table->bigInteger('taille_fichier')->nullable();
            $table->enum('statut', ['valide', 'rejete', 'en_attente'])->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->datetime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('dossier_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
