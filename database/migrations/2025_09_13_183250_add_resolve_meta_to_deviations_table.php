<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            if (!Schema::hasColumn('deviations','resolution_note')) {
                $table->text('resolution_note')->nullable()->after('note');
            }
            if (!Schema::hasColumn('deviations','resolved_by')) {
                $table->string('resolved_by', 191)->nullable()->after('resolved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            if (Schema::hasColumn('deviations','resolution_note')) {
                $table->dropColumn('resolution_note');
            }
            if (Schema::hasColumn('deviations','resolved_by')) {
                $table->dropColumn('resolved_by');
            }
        });
    }
};
