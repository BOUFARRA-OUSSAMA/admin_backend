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
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->nullable();

            $table->foreignId('ai_model_id')->constrained()->onDelete('cascade');
            $table->string('condition_type');
            $table->string('image_path')->nullable();
            $table->string('diagnosis');
            $table->float('confidence');
            $table->json('report_data')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            // Add the foreign key constraint to users table
            $table->foreign('patient_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
