<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_report_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('progress_report_id')
                ->constrained('progress_reports')
                ->cascadeOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 'approve' | 'revise'
            $table->string('decision', 20);

            $table->text('comment');
            $table->text('recommendation')->nullable();
            $table->timestamps();

            // Index untuk query cepat per report
            $table->index(['progress_report_id', 'created_at']);
            $table->index('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_report_notes');
    }
};
