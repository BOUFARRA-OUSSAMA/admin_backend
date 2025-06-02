<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('start_datetime'); 
            $table->dateTime('end_datetime'); 
            $table->string('reason')->nullable();
            $table->string('block_type')->default('personal'); 
            $table->boolean('is_recurring')->default(false); 
            $table->string('recurring_pattern')->nullable(); 
            $table->date('recurring_end_date')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable(); 
            $table->timestamps();
            $table->softDeletes(); 

            $table->index(['doctor_user_id', 'start_datetime']);
            $table->index('block_type');
            $table->index('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_time_slots');
    }
};