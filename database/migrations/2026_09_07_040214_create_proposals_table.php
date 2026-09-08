<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('research_scheme_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->string('title');
            $table->text('summary');
            $table->string('keywords');
            $table->boolean('is_research');
            $table->enum('status_proposal', ['draft', 'admin_revision', 'submitted', 'under_review', 'reviewer_revision', 'accepted', 'rejected'])->default('draft');
            $table->unsignedBigInteger('period_id');
            $table->foreign('research_scheme_id')->references('id')->on('research_schemes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('period_id')->references('id')->on('periods')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
