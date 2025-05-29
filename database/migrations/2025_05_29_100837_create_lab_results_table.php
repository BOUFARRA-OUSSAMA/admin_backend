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
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->date('result_date');
            $table->string('performed_by_lab_name')->nullable();
            $table->string('result_document_path')->nullable();
            $table->json('structured_results')->nullable();
            $table->text('interpretation')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending_review'); // pending_review, reviewed, requires_action
            $table->timestamps();

            $table->index('lab_test_id');
            $table->index('medical_record_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
