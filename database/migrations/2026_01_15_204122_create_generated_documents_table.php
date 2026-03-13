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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_exam_id')->constrained('candidate_exams')->onDelete('cascade');
            $table->string('type'); // fiche_enrolement, convocation, quitus, attestation_admission
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('qr_code_unique')->unique()->nullable(); // Code unique pour traçabilité
            $table->enum('statut', ['genere', 'telecharge', 'expire'])->default('genere');
            $table->datetime('date_generation');
            $table->datetime('date_expiration')->nullable();
            $table->datetime('date_premiere_telecharge')->nullable();
            $table->integer('nombre_telechargements')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('candidate_exam_id');
            $table->index('type');
            $table->index('qr_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
