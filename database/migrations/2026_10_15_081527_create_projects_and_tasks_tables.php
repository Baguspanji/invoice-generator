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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('rate', 15, 2)->default(0);
            $table->enum('pricing_type', ['fixed', 'hourly'])->default('hourly');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'archived_at']);
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'user_id']);
        });

        Schema::create('task_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 50)->default('#64748b');
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'order_column']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimation', 8, 2)->nullable();
            $table->decimal('fixed_price', 15, 2)->nullable();
            $table->boolean('billable')->default(false);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'task_group_id', 'order_column']);
            $table->index(['invoice_id', 'billable']);
        });

        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('minutes')->default(0);
            $table->timestamp('timer_start')->nullable();
            $table->timestamp('timer_stop')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_groups');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }
};
