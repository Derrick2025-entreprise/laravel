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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('sigle')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('telephone')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->enum('status', ['validee', 'en_attente', 'rejetee'])->default('en_attente');
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('parameters')->nullable(); // Paramètres personnalisés école
            $table->timestamps();
            $table->index('status');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
