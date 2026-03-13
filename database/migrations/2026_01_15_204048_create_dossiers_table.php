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
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('candidate_exam_id')->constrained('candidate_exams')->onDelete('cascade');
            $table->enum('etat_dossier', ['incomplet', 'complet', 'valide', 'rejete'])->default('incomplet');
            $table->text('motif_rejet')->nullable();
            $table->datetime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('progress_percentage')->default(0); // % de complétude
            $table->timestamps();
            $table->index('candidate_id');
            $table->index('etat_dossier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
