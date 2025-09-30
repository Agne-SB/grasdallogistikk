<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add only if missing (safe in all envs)
            if (!Schema::hasColumn('projects', 'mount_started_at')) {
                $table->dateTime('mount_started_at')->nullable()->index()->after('vendor_closed_at');
            }
            if (!Schema::hasColumn('projects', 'mount_completed_at')) {
                $table->dateTime('mount_completed_at')->nullable()->index()->after('mount_started_at');
            }
            
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'mount_started_at')) {
                $table->dropColumn('mount_started_at');
            }
            if (Schema::hasColumn('projects', 'mount_completed_at')) {
                $table->dropColumn('mount_completed_at');
            }
            
        });
    }
};
