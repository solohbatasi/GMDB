<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'channel')) {
                $table->string('channel')->nullable()->after('channel_id');
            }

            if (! Schema::hasColumn('payments', 'provider_transaction_id')) {
                $table->string('provider_transaction_id')->nullable()->after('provider_reference');
            }

            if (! Schema::hasColumn('payments', 'authorization_url')) {
                $table->text('authorization_url')->nullable()->after('transaction_id');
            }

            if (! Schema::hasColumn('payments', 'access_code')) {
                $table->string('access_code')->nullable()->after('authorization_url');
            }

            if (! Schema::hasColumn('payments', 'gateway_response')) {
                $table->text('gateway_response')->nullable()->after('access_code');
            }

            if (! Schema::hasColumn('payments', 'failure_message')) {
                $table->text('failure_message')->nullable()->after('gateway_response');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['failure_message', 'gateway_response', 'access_code', 'authorization_url', 'provider_transaction_id', 'channel'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
