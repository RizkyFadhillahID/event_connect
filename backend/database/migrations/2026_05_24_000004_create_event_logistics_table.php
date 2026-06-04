<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // PIC kru lapangan
            $table->integer('quantity');
            $table->decimal('rent_cost', 12, 2)->default(0.00);
            $table->date('borrowed_at');
            $table->date('returned_at')->nullable();
            $table->string('return_status')->nullable(); // complete, incomplete, damaged
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logistics');
    }
};
