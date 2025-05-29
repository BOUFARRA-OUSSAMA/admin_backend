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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('bill_number')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending'); // pending, paid, overdue, cancelled
            $table->text('description')->nullable();
            $table->string('pdf_path')->nullable(); // Path to the PDF file
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Add indexes for faster lookups
            $table->index('patient_id');
            $table->index('status');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
