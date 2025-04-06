<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Mining Operations Table
        Schema::create('mining_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->bigInteger('tokens_earned');
            $table->integer('duration_seconds');
            $table->integer('mining_rate');
            $table->integer('boost_multiplier')->default(1);
            $table->timestamps();
        });

        // Boosts Table
        Schema::create('boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type');
            $table->integer('duration_seconds');
            $table->decimal('multiplier', 5, 2);
            $table->timestamp('activated_at');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Tasks Table
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('description');
            $table->bigInteger('reward');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User Task Completions Table
        Schema::create('user_task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('task_id')->constrained();
            $table->timestamp('completed_at');
            $table->timestamps();
        });

        // Token Transactions Table
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type'); // mining, task_completion, etc.
            $table->bigInteger('amount');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('token_transactions');
        Schema::dropIfExists('user_task_completions');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('boosts');
        Schema::dropIfExists('mining_operations');
    }
};
