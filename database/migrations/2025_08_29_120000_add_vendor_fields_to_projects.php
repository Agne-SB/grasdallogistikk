<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'vendor_status')) {
                $table->string('vendor_status')->nullable()->index();
            }
            if (!Schema::hasColumn('projects', 'vendor_updated_at')) {
                $table->timestamp('vendor_updated_at')->nullable()->index();
            }
            if (!Schema::hasColumn('projects', 'vendor_closed_at')) {
                $table->timestamp('vendor_closed_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'vendor_closed_at')) {
                $table->dropIndex(['vendor_closed_at']);
                $table->dropColumn('vendor_closed_at');
            }
            if (Schema::hasColumn('projects', 'vendor_updated_at')) {
                $table->dropIndex(['vendor_updated_at']);
                $table->dropColumn('vendor_updated_at');
            }
            if (Schema::hasColumn('projects', 'vendor_status')) {
                $table->dropIndex(['vendor_status']);
                $table->dropColumn('vendor_status');
            }
        });
    }
};
