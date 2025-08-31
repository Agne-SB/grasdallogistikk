<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('projects', function (Blueprint $t) {
            if (!Schema::hasColumn('projects','geo_lat'))  $t->decimal('geo_lat', 10, 7)->nullable()->index();
            if (!Schema::hasColumn('projects','geo_lng'))  $t->decimal('geo_lng', 10, 7)->nullable()->index();
            if (!Schema::hasColumn('projects','geocoded_at'))      $t->timestamp('geocoded_at')->nullable()->index();
            if (!Schema::hasColumn('projects','geocode_provider')) $t->string('geocode_provider', 32)->nullable();
        });
    }
    public function down(): void {
        Schema::table('projects', function (Blueprint $t) {
            if (Schema::hasColumn('projects','geocode_provider')) $t->dropColumn('geocode_provider');
            if (Schema::hasColumn('projects','geocoded_at'))      $t->dropColumn('geocoded_at');
            if (Schema::hasColumn('projects','geo_lng'))          $t->dropColumn('geo_lng');
            if (Schema::hasColumn('projects','geo_lat'))          $t->dropColumn('geo_lat');
        });
    }
};
