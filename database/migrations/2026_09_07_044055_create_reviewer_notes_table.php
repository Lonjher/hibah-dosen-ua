<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviewer_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('noteable_id');
            $table->string('noteable_type');
            // Morph Map: 'proposal' | 'progress_report'
            $table->unsignedBigInteger('reviewer_id');
            $table->text('comment')->nullable();
            $table->boolean('is_approved');
            $table->text('recommendation')->nullable();
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['noteable_id', 'noteable_type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviewer_notes');
    }
};
