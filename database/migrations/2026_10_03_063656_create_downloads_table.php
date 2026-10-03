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
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['guideline', 'template', 'form', 'general'])->default('general');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_welcome')->default(true);
            $table->boolean('show_on_dashboard')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'category', 'sort_order']);
            $table->index(['show_on_welcome', 'is_active']);
            $table->index(['show_on_dashboard', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
