<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_locations', function (Blueprint $table) {
            $table->text('map_url')->nullable()->after('instructions');
            $table->decimal('latitude', 10, 7)->nullable()->after('map_url');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_locations', function (Blueprint $table) {
            $table->dropColumn(['map_url', 'latitude', 'longitude']);
        });
    }
};
