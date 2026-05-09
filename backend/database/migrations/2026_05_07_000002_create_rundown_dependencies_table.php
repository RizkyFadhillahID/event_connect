<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundown_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rundown_id')->constrained('event_rundowns')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['rundown_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rundown_dependencies');
    }
};
