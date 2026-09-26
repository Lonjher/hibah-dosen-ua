<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id');
            $table->text('summary');
            $table->string('keyword');
            $table->string('report_path');       // PDF
            $table->string('ppt_path');          // PPT
            $table->string('research_output');   // PDF/WORD
            $table->string('submission_proof');  // img
            $table->string('status')->default('pending');
            // pending, revised, accepted, rejected
            $table->foreign('proposal_id')->references('id')->on('proposals')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_reports');
    }
};
