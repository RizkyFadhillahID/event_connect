<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rundowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->date('event_date');                         // support multi-day events
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();             // e.g. technical, talent, ceremony, logistics
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->nullable();
            $table->enum('status', ['pending', 'ready', 'live', 'delayed', 'completed'])->default('pending');
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete(); // Person In Charge
            $table->string('location_note')->nullable();        // specific location within venue
            $table->text('notes')->nullable();
            $table->integer('order_number')->default(0);
            $table->timestamp('started_at')->nullable();        // actual start time
            $table->timestamp('ended_at')->nullable();          // actual end time
            $table->integer('delay_minutes')->default(0);       // delay from planned time
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rundowns');
    }
};
