<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('nim');
            $table->string('name');
            $table->string('program_study');
            $table->enum('role', ['leader', 'member'])->default('member');
            $table->timestamps();

            // Prevent duplicate: NIM sama tidak boleh muncul 2x di proposal yang sama
            $table->unique(['proposal_id', 'nim']);

            // Query optimization
            $table->index('proposal_id');
            $table->index('nim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_students');
    }
};
