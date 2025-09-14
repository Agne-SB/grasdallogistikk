<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('projects', function (Blueprint $t) {
            if (!Schema::hasColumn('projects','geocode_attempts')) {
                $t->unsignedSmallInteger('geocode_attempts')->default(0);
            }
            if (!Schema::hasColumn('projects','geocode_failed_at')) {
                $t->timestamp('geocode_failed_at')->nullable()->index();
            }
            if (!Schema::hasColumn('projects','geocode_last_error')) {
                $t->string('geocode_last_error', 191)->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('projects', function (Blueprint $t) {
            if (Schema::hasColumn('projects','geocode_last_error')) $t->dropColumn('geocode_last_error');
            if (Schema::hasColumn('projects','geocode_failed_at'))  $t->dropColumn('geocode_failed_at');
            if (Schema::hasColumn('projects','geocode_attempts'))   $t->dropColumn('geocode_attempts');
        });
    }
};
