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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('candidate_exam_id')->constrained('candidate_exams')->onDelete('cascade');
            $table->string('reference_paiement')->unique();
            $table->decimal('montant', 12, 2);
            $table->string('devise')->default('XAF');
            $table->enum('methode', ['mobile_money', 'virement', 'carte', 'cash'])->default('mobile_money');
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'remboursé'])->default('en_attente');
            $table->text('notes')->nullable();
            $table->string('numero_telephone')->nullable();
            $table->datetime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('preuve_paiement_path')->nullable();
            $table->json('metadata')->nullable(); // Infos du paiement externe
            $table->timestamps();
            $table->index('candidate_id');
            $table->index('statut');
            $table->index('reference_paiement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
