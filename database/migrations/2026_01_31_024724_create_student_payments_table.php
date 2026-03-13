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
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('payment_type', ['enrollment_fee', 'tuition_fee', 'exam', 'other'])->default('enrollment_fee');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'card', 'check'])->default('bank_transfer');
            $table->string('reference_number')->unique();
            $table->string('transaction_id')->nullable();
            $table->string('receipt_file')->nullable();
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->datetime('payment_date');
            $table->datetime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('academic_year')->default('2026-2027');
            $table->timestamps();
            
            $table->index(['student_id', 'status']);
            $table->index('reference_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};