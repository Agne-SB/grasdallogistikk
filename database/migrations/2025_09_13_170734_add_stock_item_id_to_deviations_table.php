<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            if (! Schema::hasColumn('deviations', 'stock_item_id')) {
                $table->unsignedBigInteger('stock_item_id')->nullable()->after('project_id');
                $table->index('stock_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            if (Schema::hasColumn('deviations', 'stock_item_id')) {
                $table->dropIndex(['stock_item_id']);
                $table->dropColumn('stock_item_id');
            }
        });
    }
};
