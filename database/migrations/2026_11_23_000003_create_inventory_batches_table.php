<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('initial_quantity', 12, 3);
            $table->date('expiration_date')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['inventory_item_id', 'expiration_date']);
        });

        Schema::create('inventory_movement_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->index(['inventory_movement_id', 'inventory_batch_id'], 'movement_batch_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_batches');
        Schema::dropIfExists('inventory_batches');
    }
};
