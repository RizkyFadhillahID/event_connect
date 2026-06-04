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
        Schema::create('event_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The PM who finalized it
            $table->text('evaluation_notes');
            $table->text('recommendations');
            $table->json('budget_snapshot');
            $table->json('task_snapshot');
            $table->json('logistic_snapshot');
            $table->timestamp('finalized_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reports');
    }
};
