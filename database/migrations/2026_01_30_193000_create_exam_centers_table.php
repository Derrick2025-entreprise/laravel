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
        Schema::create('exam_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sigle', 10)->unique();
            $table->text('address');
            $table->string('city');
            $table->string('region');
            $table->integer('capacity')->default(0);
            $table->json('facilities')->nullable(); // Équipements disponibles
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('coordinates')->nullable(); // Latitude, longitude
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Table pivot pour les examens et centres
        Schema::create('exam_exam_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_center_id')->constrained()->onDelete('cascade');
            $table->integer('capacity_allocated')->default(0);
            $table->enum('status', ['assigned', 'confirmed', 'cancelled'])->default('assigned');
            $table->timestamps();
        });

        // Ajouter la colonne exam_center_id à candidate_exams
        Schema::table('candidate_exams', function (Blueprint $table) {
            $table->foreignId('exam_center_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_exams', function (Blueprint $table) {
            $table->dropForeign(['exam_center_id']);
            $table->dropColumn('exam_center_id');
        });
        
        Schema::dropIfExists('exam_exam_centers');
        Schema::dropIfExists('exam_centers');
    }
};