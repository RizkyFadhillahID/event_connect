<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->decimal('allocated_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_budget_allocations');
    }
};
