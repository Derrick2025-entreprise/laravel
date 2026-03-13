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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->integer('year')->nullable();
            $table->integer('session')->default(1); // Session 1 ou 2
            $table->datetime('registration_start_date');
            $table->datetime('registration_end_date');
            $table->datetime('exam_date')->nullable();
            $table->integer('num_candidates')->default(0);
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->text('conditions')->nullable(); // Conditions d'accès
            $table->enum('status', ['draft', 'published', 'closed', 'in_progress', 'results_published'])->default('draft');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->index('school_id');
            $table->index('status');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
