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
        Schema::create('candidate_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('exam_filiere_id')->constrained('exam_filieres')->onDelete('cascade');
            $table->enum('statut', ['inscrit', 'confirme', 'present', 'absent'])->default('inscrit');
            $table->datetime('registered_at');
            $table->datetime('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_id']);
            $table->index('candidate_id');
            $table->index('exam_id');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_exams');
    }
};
