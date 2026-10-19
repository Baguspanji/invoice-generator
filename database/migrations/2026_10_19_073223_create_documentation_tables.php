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
        Schema::create('documentation_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('documentation_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('documentation_spaces')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->unique(['space_id', 'slug']);
            $table->index(['space_id', 'order_column']);
        });

        Schema::create('documentation_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('documentation_spaces')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('documentation_categories')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('documentation_pages')->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->json('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->unique(['space_id', 'slug']);
            $table->index(['space_id', 'parent_id']);
            $table->index(['space_id', 'status']);
        });

        Schema::create('documentation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('documentation_pages')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title', 255);
            $table->json('content')->nullable();
            $table->string('status', 20);
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['page_id', 'version_number']);
        });

        Schema::create('documentation_page_task', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('documentation_pages')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->primary(['page_id', 'task_id']);
        });

        Schema::create('documentation_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('documentation_pages')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 50)->default('public');
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 150)->nullable();
            $table->timestamps();

            $table->index('page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_attachments');
        Schema::dropIfExists('documentation_page_task');
        Schema::dropIfExists('documentation_versions');
        Schema::dropIfExists('documentation_pages');
        Schema::dropIfExists('documentation_categories');
        Schema::dropIfExists('documentation_spaces');
    }
};
