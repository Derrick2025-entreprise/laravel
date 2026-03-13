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
        Schema::create('document_submission_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sigle')->unique();
            $table->text('address');
            $table->string('city');
            $table->string('region');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('opening_hours')->nullable(); // Horaires d'ouverture
            $table->json('accepted_documents')->nullable(); // Types de documents acceptés
            $table->text('directions')->nullable(); // Indications pour s'y rendre
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_submission_centers');
    }
};
