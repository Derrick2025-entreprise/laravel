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
        Schema::table('student_payments', function (Blueprint $table) {
            // Champs pour les paiements mobiles
            $table->string('mobile_number')->nullable()->after('payment_method');
            $table->string('bank_name')->nullable()->after('mobile_number');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->string('transaction_reference')->nullable()->after('account_number');
            $table->text('gateway_response')->nullable()->after('transaction_reference');
            $table->string('payment_url')->nullable()->after('gateway_response');
            
            // Modifier la colonne payment_method pour inclure les nouvelles méthodes
            $table->enum('payment_method', [
                'bank_transfer', 
                'mobile_money', 
                'cash', 
                'check',
                'orange_money',
                'mtn_money'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_number',
                'bank_name', 
                'account_number',
                'transaction_reference',
                'gateway_response',
                'payment_url'
            ]);
            
            // Restaurer l'ancienne enum
            $table->enum('payment_method', [
                'bank_transfer', 
                'mobile_money', 
                'cash', 
                'check'
            ])->change();
        });
    }
};
