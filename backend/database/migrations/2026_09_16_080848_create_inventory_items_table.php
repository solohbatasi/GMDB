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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->boolean('track_stock')->default(true);
            $table->timestamps();

            $table->index(['track_stock', 'quantity_on_hand', 'quantity_reserved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
