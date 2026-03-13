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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_exam_id')->constrained('candidate_exams')->onDelete('cascade');
            $table->decimal('score_total', 8, 2)->nullable();
            $table->decimal('score_max', 8, 2)->default(100);
            $table->decimal('pourcentage', 5, 2)->nullable();
            $table->string('mention')->nullable(); // Bien, Assez bien, Passable, etc.
            $table->boolean('admis')->default(false);
            $table->text('observations')->nullable();
            $table->datetime('published_at')->nullable();
            $table->foreignId('saisie_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('saisie_at')->nullable();
            $table->json('details')->nullable(); // Détails par matière
            $table->timestamps();
            $table->unique('candidate_exam_id');
            $table->index('admis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
