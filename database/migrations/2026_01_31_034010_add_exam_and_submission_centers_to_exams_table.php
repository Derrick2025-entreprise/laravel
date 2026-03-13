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
        Schema::table('exams', function (Blueprint $table) {
            // Ajouter les relations avec les centres
            $table->json('exam_center_ids')->nullable()->after('description'); // IDs des centres d'examen
            $table->json('submission_center_ids')->nullable()->after('exam_center_ids'); // IDs des centres de dépôt
            
            // Informations supplémentaires sur les centres
            $table->text('exam_centers_info')->nullable()->after('submission_center_ids');
            $table->text('submission_instructions')->nullable()->after('exam_centers_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'exam_center_ids',
                'submission_center_ids', 
                'exam_centers_info',
                'submission_instructions'
            ]);
        });
    }
};
