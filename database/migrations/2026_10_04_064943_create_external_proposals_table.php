<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_research')->default(true);
            $table->string('title');
            $table->string('scheme')->nullable();              // e.g. "Penelitian Dasar Unggulan"
            $table->string('funding_source');                  // e.g. "DRTPM", "BRIN", "Industri"
            $table->enum('role', ['leader', 'member']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['ongoing', 'completed', 'cancelled']);
            $table->unsignedBigInteger('fund_amount');         // dalam Rupiah (Rp)
            $table->text('description')->nullable();
            $table->string('proposal_document_path')->nullable();   // dokumen proposal
            $table->string('report_document_path')->nullable();     // dokumen laporan
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_research']);          // query by owner + type
            $table->index(['user_id', 'status']);               // filter by status
            $table->index(['is_research', 'is_verified']);      // admin verification queue
            $table->index(['start_date', 'end_date']);          // period range
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_proposals');
    }
};
