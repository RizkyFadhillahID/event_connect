<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_status_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->foreignId('inventory_id')
                ->constrained('inventories')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete(); // PIC penanggung jawab perbaikan/pelapor
            $table->string('type'); // maintenance, damaged
            $table->integer('quantity')->default(1);
            $table->string('status')->default('active'); // active, resolved
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['inventory_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_status_actions');
    }
};
