<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('serial_number')->nullable()->unique();
            $table->integer('total_quantity')->default(1);
            $table->integer('available_quantity')->default(1);
            $table->string('ownership')->default('owned'); // owned, rented
            $table->decimal('default_rent_price', 12, 2)->nullable();
            $table->string('status')->default('ready'); // ready, maintenance, damaged
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
