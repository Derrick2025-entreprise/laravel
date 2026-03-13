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
        Schema::create('composition_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('nom_centre')->unique();
            $table->string('code_centre')->unique()->nullable();
            $table->string('ville')->nullable();
            $table->string('region')->nullable();
            $table->text('adresse')->nullable();
            $table->string('responsable_nom')->nullable();
            $table->string('responsable_telephone')->nullable();
            $table->integer('capacite')->default(0);
            $table->integer('places_utilisees')->default(0);
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('exam_id');
            $table->index('ville');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('composition_centers');
    }
};
