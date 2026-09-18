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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('public_token', 80)->unique();
            $table->string('checkout_token', 120)->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 20);
            $table->string('delivery_method');
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_county')->nullable();
            $table->unsignedBigInteger('pickup_location_id')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('payment_status')->default('unpaid');
            $table->string('order_status')->default('pending');
            $table->string('fulfillment_status')->default('pending');
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reservation_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_status', 'order_status']);
            $table->index(['delivery_method', 'fulfillment_status']);
            $table->index('reservation_expires_at');
            $table->index('pickup_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
