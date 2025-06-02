<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('specialty'); 
            $table->string('license_number')->unique();
            $table->integer('experience_years')->default(0);  
            $table->decimal('consultation_fee', 8, 2)->nullable();
            $table->integer('max_patient_appointments')->default(10); // Maximum number of appointments per day
            $table->boolean('is_available')->default(true);
            $table->json('working_hours')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'specialty']);  
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctors');
    }
};