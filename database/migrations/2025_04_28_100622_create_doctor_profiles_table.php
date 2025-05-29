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
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('specialty')->nullable();
            $table->string('license_number')->nullable()->unique();
            $table->text('education')->nullable();
            $table->text('experience')->nullable();
            $table->text('availability_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Add index for faster lookups
            $table->index('specialty');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
