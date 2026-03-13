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
        Schema::create('exam_filieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('filiere_name');
            $table->string('filiere_code')->nullable();
            $table->integer('quota')->default(0);
            $table->integer('registered')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'filiere_code']);
            $table->index('exam_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_filieres');
    }
};
