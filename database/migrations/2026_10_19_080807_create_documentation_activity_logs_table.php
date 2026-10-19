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
        Schema::create('documentation_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('documentation_spaces')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('documentation_pages')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('description', 255);
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'created_at']);
            $table->index('page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_activity_logs');
    }
};
