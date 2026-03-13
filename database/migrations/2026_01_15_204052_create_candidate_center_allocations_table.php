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
        Schema::create('candidate_center_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_exam_id')->constrained('candidate_exams')->onDelete('cascade');
            $table->foreignId('composition_center_id')->constrained('composition_centers')->onDelete('cascade');
            $table->string('numero_place')->nullable(); // N° de place dans le centre
            $table->enum('statut', ['alloue', 'confirme', 'absent', 'present'])->default('alloue');
            $table->datetime('allocated_at');
            $table->datetime('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['candidate_exam_id', 'composition_center_id'], 'candidate_center_unique');
            $table->index('composition_center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_center_allocations');
    }
};
