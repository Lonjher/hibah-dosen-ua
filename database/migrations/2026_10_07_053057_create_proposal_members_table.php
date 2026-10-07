<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['leader', 'member'])->default('member');
            $table->timestamps();

            // Prevent duplicate: satu user tidak boleh muncul 2x di proposal yang sama
            $table->unique(['proposal_id', 'user_id']);

            // Query optimization
            $table->index('proposal_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_members');
    }
};
