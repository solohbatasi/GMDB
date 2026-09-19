<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('orders') || $this->usesSqlite()) {
            return;
        }

        if ($this->foreignKeyExists()) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || $this->usesSqlite() || ! $this->foreignKeyExists()) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
    }

    private function usesSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function foreignKeyExists(): bool
    {
        return match (DB::getDriverName()) {
            'pgsql' => (bool) DB::selectOne(
                "select 1 from pg_constraint where conname = 'payments_order_id_foreign'"
            ),
            'mysql', 'mariadb' => (bool) DB::selectOne(
                "select 1 from information_schema.table_constraints where constraint_schema = database() and table_name = 'payments' and constraint_name = 'payments_order_id_foreign'"
            ),
            default => false,
        };
    }
};
