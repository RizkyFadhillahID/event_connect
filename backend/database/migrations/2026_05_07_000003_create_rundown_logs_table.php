<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundown_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rundown_id')->constrained('event_rundowns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');                   // e.g. 'status_changed', 'started', 'delayed'
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('delay_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rundown_logs');
    }
};
