<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('payhero');
            $table->string('method');
            $table->string('source')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('payer_phone', 20)->nullable();
            $table->string('status')->default('created');
            $table->string('channel_id')->nullable();
            $table->string('external_reference')->unique();
            $table->string('payhero_reference')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_description')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['order_id', 'status']);
            $table->index(['channel_id', 'status']);
            $table->index('payhero_reference');
            $table->index('provider_reference');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
